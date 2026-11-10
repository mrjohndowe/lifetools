<?php
declare(strict_types=1);

final class Store
{
    public const VERSION = 1;
    public const FIELDS = [
        'id', 'owner', 'kind', 'title', 'search', 'starts', 'ends', 'repeat', 'payload',
        'revision', 'created_at', 'updated_at', 'deleted_at',
    ];
    public PDO $db;

    public function __construct(public readonly array $config, bool $create = false)
    {
        if (!$create && !is_file($config['path'])) {
            throw new Problem(ltText('the_sqlite_database_is_missing_restore'));
        }
        $this->db = new PDO('sqlite:' . $config['path'], null, null, [
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        ]);
        $this->db->exec('PRAGMA busy_timeout=4000; PRAGMA foreign_keys=ON');
        if ($create) {
            chmod($config['path'], 0600);
            $this->db->exec('PRAGMA journal_mode=WAL');
        }
    }

    public function run(string $sql, array $parameters = []): PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($parameters);
        return $statement;
    }

    public function transaction(callable $callback): mixed
    {
        // Reserve the writer before any reads that determine the next revision.
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            $result = $callback();
            $this->db->exec('COMMIT');
            return $result;
        } catch (Throwable $e) {
            $this->db->exec('ROLLBACK');
            throw $e;
        }
    }

    public function migrate(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS migrations (version INTEGER PRIMARY KEY, applied_at TEXT NOT NULL)');
        $version = (int)$this->db->query('SELECT COALESCE(MAX(version),0) FROM migrations')->fetchColumn();
        if ($version > self::VERSION) {
            throw new Problem(ltText('this_database_was_created_by_a'));
        }
        if ($version === self::VERSION) {
            return;
        }
        $this->transaction(function (): void {
            if ($this->db->query('SELECT MAX(version) FROM migrations')->fetchColumn()) {
                return;
            }
            $this->db->exec(<<<'SQL'
                CREATE TABLE records (
                    id TEXT PRIMARY KEY, owner TEXT NOT NULL, kind TEXT NOT NULL,
                    title TEXT NOT NULL, search TEXT NOT NULL, starts TEXT NOT NULL, ends TEXT NOT NULL,
                    repeat INTEGER NOT NULL, payload TEXT NOT NULL, revision INTEGER NOT NULL,
                    created_at TEXT NOT NULL, updated_at TEXT NOT NULL, deleted_at TEXT
                );
                CREATE INDEX records_timeline ON records(owner,kind,deleted_at,starts,id);
                CREATE INDEX records_repeat ON records(owner,kind,deleted_at,repeat,starts);
                CREATE INDEX records_updated ON records(updated_at);
                CREATE INDEX records_tombstones ON records(deleted_at);
                CREATE UNIQUE INDEX users_login ON records(title) WHERE kind='users' AND deleted_at IS NULL;
                CREATE TABLE links (
                    source TEXT NOT NULL REFERENCES records(id),
                    target TEXT NOT NULL REFERENCES records(id),
                    field TEXT NOT NULL,
                    PRIMARY KEY(source,target,field)
                );
                CREATE INDEX links_target ON links(target,source);
                CREATE TABLE sync_queue (
                    id TEXT PRIMARY KEY REFERENCES records(id), revision INTEGER NOT NULL,
                    attempts INTEGER NOT NULL DEFAULT 0, next_at INTEGER NOT NULL DEFAULT 0
                );
                CREATE INDEX sync_ready ON sync_queue(next_at,id);
                CREATE TABLE sync_drift (id TEXT PRIMARY KEY, detected_at TEXT NOT NULL, primary_revision INTEGER, mirror_revision INTEGER);
                CREATE TABLE meta (name TEXT PRIMARY KEY, value TEXT NOT NULL);
                CREATE TABLE throttle (key TEXT PRIMARY KEY, count INTEGER NOT NULL, expires INTEGER NOT NULL);
                CREATE TABLE save_receipts (
                    owner TEXT NOT NULL, request TEXT NOT NULL, kind TEXT NOT NULL, id TEXT NOT NULL,
                    revision INTEGER NOT NULL, updated_at TEXT NOT NULL, expires INTEGER NOT NULL,
                    PRIMARY KEY(owner,request)
                );
                CREATE INDEX save_receipts_expiry ON save_receipts(expires);
                SQL);
            $this->run('INSERT INTO migrations VALUES (?,?)', [self::VERSION,ltNow()]);
        });
    }

    public function meta(string $name, ?string $value = null): ?string
    {
        if ($value !== null) {
            $this->run('INSERT INTO meta VALUES (?,?) ON CONFLICT(name) DO UPDATE SET value=excluded.value', [$name,$value]);
            return $value;
        }
        $result = $this->run('SELECT value FROM meta WHERE name=?', [$name])->fetchColumn();
        return $result === false ? null : $result;
    }

    public function checkInstallation(string $id): void
    {
        $expected = $id . ':' . hash_hmac('sha256', $id, $this->config['key']);
        if (!hash_equals($expected, $this->meta('installation') ?? '')) {
            throw new Problem(ltText('the_configured_database_or_encryption_key'));
        }
    }

    public function prepareMode(): void
    {
        $mode = $this->config['mode'];
        $target = $mode === 'dual' ? hash('sha256', ltJson($this->config['mirror'])) : '';
        if ($this->meta('mode') === $mode && $this->meta('mirror_target') === $target) {
            return;
        }
        $this->transaction(function () use ($mode, $target): void {
            if ($this->meta('mode') === $mode && $this->meta('mirror_target') === $target) {
                return;
            }
            if ($mode === 'dual') {
                $this->queueAll();
                $this->meta('sync_result', 'pending');
                $this->meta('sync_next', '0');
                $this->meta('sync_failures', '0');
                $this->meta('sync_success', '');
            }
            $this->meta('mode', $mode);
            $this->meta('mirror_target', $target);
            $this->audit('', 'mode_change', ['mode'=>$mode]);
        });
    }

    public function queueAll(): void
    {
        $this->db->exec('INSERT INTO sync_queue(id,revision) SELECT id,revision FROM records WHERE 1
            ON CONFLICT(id) DO UPDATE SET revision=excluded.revision,attempts=0,next_at=0');
    }

    public function queue(string $id, int $revision): void
    {
        $this->run('INSERT INTO sync_queue(id,revision) VALUES (?,?)
            ON CONFLICT(id) DO UPDATE SET revision=excluded.revision,attempts=0,next_at=0', [$id,$revision]);
    }

    public function put(array $row): void
    {
        $fields = implode(',', self::FIELDS);
        $slots = implode(',', array_fill(0, count(self::FIELDS), '?'));
        $assignments = implode(',', array_map(static fn($f)=>"$f=excluded.$f", array_slice(self::FIELDS,1)));
        $this->run("INSERT INTO records ($fields) VALUES ($slots) ON CONFLICT(id) DO UPDATE SET $assignments",
            array_map(static fn($f)=>$row[$f], self::FIELDS));
    }

    public static function payload(array $row): array
    {
        return json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
    }

    public static function hash(array $row): string
    {
        return hash('sha256', ltJson(array_map(static fn($field)=>$row[$field] === null ? null : (string)$row[$field], self::FIELDS)));
    }

    public function record(string $owner, string $id, ?string $kind = null, bool $trash = false): array
    {
        $row = $this->run('SELECT * FROM records WHERE id=? AND owner=?' .
            ($trash ? '' : ' AND deleted_at IS NULL'), [$id,$owner])->fetch();
        if (!$row || ($kind !== null && $row['kind'] !== $kind)) {
            throw new Problem(ltText('record_could_not_be_found'));
        }
        return $row;
    }

    public function privateValue(array $row, string $field): string
    {
        $encrypted = self::payload($row)['_private'][$field] ?? '';
        return $encrypted === '' ? '' : ltOpen($encrypted, $this->config['key'],
            implode(':', [$row['owner'],$row['kind'],$row['id'],$field]));
    }

    // All callers write through a transaction, including installation, seeding and audit.
    public function save(string $owner, string $kind, string $title, array $payload, array $private = [],
        ?array $old = null, ?string $id = null, bool $deleted = false): array
    {
        $id = $old['id'] ?? $id ?? ltUuid();
        $now = ltNow();
        $secrets = $old ? (self::payload($old)['_private'] ?? []) : [];
        foreach ($private as $field => $plain) {
            if ($plain === '') {
                unset($secrets[$field]);
            } else {
                $secrets[$field] = ltSeal($plain, $this->config['key'], "$owner:$kind:$id:$field");
            }
        }
        if ($secrets) {
            $payload['_private'] = $secrets;
        }
        $search = in_array($kind, ['users','audit_log','intimacy_events','accounts','bank_accounts','cards','personal_profile'], true)
            ? $title : implode(' ', [$title, ...array_filter($payload, 'is_string')]);
        if ($kind === 'intimacy_events') {
            $search = '';
        }
        $row = [
            'id'=>$id, 'owner'=>$owner, 'kind'=>$kind, 'title'=>$title, 'search'=>mb_strtolower($search),
            'starts'=>$payload['_starts'] ?? '', 'ends'=>$payload['_ends'] ?? '',
            'repeat'=>empty($payload['frequency']) ? 0 : 1,
            'payload'=>ltJson($payload), 'revision'=>(int)($old['revision'] ?? 0)+1,
            'created_at'=>$old['created_at'] ?? $now, 'updated_at'=>$now, 'deleted_at'=>$deleted ? $now : null,
        ];
        $this->put($row);
        $this->indexLinks($row);
        if ($this->config['mode'] === 'dual') {
            $this->queue($id, $row['revision']);
        }
        return $row;
    }

    public function indexLinks(array $row): void
    {
        $this->run('DELETE FROM links WHERE source=?', [$row['id']]);
        if ($row['deleted_at'] !== null) {
            return;
        }
        foreach (self::payload($row)['_links'] ?? [] as $field => $ids) {
            foreach ($ids as $id) {
                $this->run('INSERT INTO links VALUES (?,?,?)', [$row['id'],$id,$field]);
            }
        }
    }

    public function audit(string $actor, string $action, array $metadata = []): void
    {
        $this->save($actor, 'audit_log', $action, $metadata);
    }

    public function createUser(string $login, string $password, string $role, array $prefs): array
    {
        $login = strtolower(trim($login));
        if (!preg_match('/^[a-z0-9][a-z0-9_.@-]{2,79}$/D', $login)) {
            throw new Problem(ltText('use_3_80_letters_numbers_or'));
        }
        if (strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0")) {
            throw new Problem(ltText('use_a_password_between_12_and'));
        }
        if ($this->user($login)) {
            throw new Problem(ltText('this_login_is_already_in_use'));
        }
        $id = ltUuid();
        $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $options = $algorithm === PASSWORD_BCRYPT ? ['cost'=>12] : [];
        return $this->save($id, 'users', $login, [
            'role'=>$role, 'theme'=>$prefs['theme'], 'zone'=>$prefs['zone'], 'currency'=>$prefs['currency'],
            'format'=>$prefs['format'], 'passwordHash'=>password_hash($password,$algorithm,$options),
        ], id:$id);
    }

    public function user(string $login): array|false
    {
        return $this->run("SELECT * FROM records WHERE kind='users' AND title=? AND deleted_at IS NULL",
            [strtolower(trim($login))])->fetch();
    }

    public function allowAttempt(string $key, int $limit = 8): bool
    {
        return $this->transaction(function () use ($key,$limit): bool {
            $now = time();
            $this->run('DELETE FROM throttle WHERE expires<=?', [$now]);
            $count = (int)$this->run('SELECT count FROM throttle WHERE key=?', [$key])->fetchColumn();
            if ($count >= $limit) {
                return false;
            }
            $this->run('INSERT INTO throttle VALUES (?,1,?) ON CONFLICT(key) DO UPDATE SET count=count+1',
                [$key,$now+300]);
            return true;
        });
    }
}

