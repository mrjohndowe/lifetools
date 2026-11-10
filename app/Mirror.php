<?php
declare(strict_types=1);

final class Mirror
{
    private const TYPES = [
        'id'=>'char(36)', 'owner'=>'char(36)', 'kind'=>'varchar(40)', 'title'=>'varchar(255)',
        'search'=>'longtext', 'starts'=>'varchar(20)', 'ends'=>'varchar(20)', 'repeat'=>'tinyint',
        'payload'=>'longtext', 'revision'=>'bigint', 'created_at'=>'varchar(20)',
        'updated_at'=>'varchar(20)', 'deleted_at'=>'varchar(20)',
    ];

    public function __construct(private readonly Store $store) {}

    public static function connect(array $c): PDO
    {
        try {
            $connection = new PDO(
                "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=utf8mb4",
                $c['user'],$c['password'],[
                    PDO::ATTR_TIMEOUT=>2, PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY=>false,
                ]);
            $connection->exec('SET SESSION innodb_lock_wait_timeout=2');
            return $connection;
        } catch (PDOException) {
            throw new Problem('MySQL is unreachable or rejected the configured credentials/database.');
        }
    }

    public function locked(callable $work): mixed
    {
        if ($this->store->config['mode'] !== 'dual') {
            throw new Problem('MySQL backup is not configured.');
        }
        $mysql = self::connect($this->store->config['mirror']);
        $lock = 'lifetools:' . substr(hash('sha256',$this->store->config['mirror']['database']),0,40);
        $stmt = $mysql->prepare('SELECT GET_LOCK(?,0)');
        $stmt->execute([$lock]);
        $acquired = $stmt->fetchColumn();
        $stmt->closeCursor();
        if ((int)$acquired !== 1) {
            throw new Problem('Another backup worker is active. Try again.');
        }
        try {
            $this->migrate($mysql);
            return $work($mysql);
        } finally {
            try {
                $stmt = $mysql->prepare('SELECT RELEASE_LOCK(?)');
                $stmt->execute([$lock]);
                $stmt->closeCursor();
            } catch (PDOException) {
                // Connection loss releases the lock on the server.
            }
        }
    }

    private function migrate(PDO $mysql): void
    {
        $mysql->exec('CREATE TABLE IF NOT EXISTS lt_migrations
            (version INT NOT NULL PRIMARY KEY, applied_at VARCHAR(20) NOT NULL) ENGINE=InnoDB');
        $query = $mysql->query('SELECT COALESCE(MAX(version),0) FROM lt_migrations');
        $version = (int)$query->fetchColumn();
        $query->closeCursor();
        if ($version > Store::VERSION) {
            throw new Problem('MySQL schema is newer than this release.');
        }
        if ($version === 0) {
            $columns = [];
            foreach (self::TYPES as $field=>$type) {
                $columns[] = chr(96) . $field . chr(96) . ' ' . $type . ($field === 'deleted_at' ? ' NULL' : ' NOT NULL');
            }
            $mysql->exec('CREATE TABLE IF NOT EXISTS lt_records (' . implode(',',$columns) .
                ', PRIMARY KEY(id), INDEX lt_owner(owner,kind,deleted_at,starts,id), INDEX lt_updated(updated_at),
                 INDEX lt_deleted(deleted_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
            $mysql->exec('CREATE TABLE IF NOT EXISTS lt_identity
                (slot INT NOT NULL PRIMARY KEY, fingerprint VARCHAR(128) NOT NULL) ENGINE=InnoDB');
        }
        $query = $mysql->query("SELECT COLUMN_NAME,DATA_TYPE,CHARACTER_MAXIMUM_LENGTH,IS_NULLABLE,COLUMN_KEY
            FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='lt_records' ORDER BY ORDINAL_POSITION");
        $columns = $query->fetchAll();
        if (array_column($columns,'COLUMN_NAME') !== array_keys(self::TYPES)) {
            throw new Problem('MySQL schema drift: backup columns differ.');
        }
        foreach ($columns as $column) {
            $field = $column['COLUMN_NAME'];
            $type = $column['DATA_TYPE'];
            if (in_array($type,['char','varchar'],true)) {
                $type .= '(' . $column['CHARACTER_MAXIMUM_LENGTH'] . ')';
            }
            if ($type !== self::TYPES[$field]
                || $column['IS_NULLABLE'] !== ($field === 'deleted_at' ? 'YES' : 'NO')
                || ($column['COLUMN_KEY'] === 'PRI') !== ($field === 'id')) {
                throw new Problem("MySQL schema drift: column $field differs.");
            }
        }
        $query = $mysql->query("SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='lt_records'");
        $table = $query->fetch();
        $query->closeCursor();
        if ($table['ENGINE'] !== 'InnoDB' || $table['TABLE_COLLATION'] !== 'utf8mb4_unicode_ci') {
            throw new Problem('MySQL schema drift: engine or collation differs.');
        }
        $fingerprint = $this->store->meta('installation');
        if ($version === 0) {
            $stmt = $mysql->prepare('INSERT IGNORE INTO lt_identity VALUES (1,?)');
            $stmt->execute([$fingerprint]);
            $stmt = $mysql->prepare('INSERT INTO lt_migrations VALUES (?,?)');
            $stmt->execute([Store::VERSION,ltNow()]);
        }
        $query = $mysql->query('SELECT fingerprint FROM lt_identity WHERE slot=1');
        $found = $query->fetchColumn();
        $query->closeCursor();
        if ($found !== $fingerprint) {
            throw new Problem('This MySQL database belongs to another installation.');
        }
    }

    public function drain(int $limit = 100, bool $force = false): int
    {
        if ($this->store->config['mode'] !== 'dual') {
            return 0;
        }
        return ltLock(LT_ROOT . '/storage/worker.lock', function () use ($limit,$force): int {
            $store = $this->store;
            $now = time();
            if (!$force && (int)$store->meta('sync_next') > $now) {
                return 0;
            }
            $limit = max(1,min(1000,$limit));
            $batch = $store->run('SELECT q.id,q.attempts FROM sync_queue q WHERE q.next_at<=? ORDER BY q.next_at,q.id LIMIT ' . $limit,
                [$force ? PHP_INT_MAX : $now])->fetchAll();
            if (!$batch && !$force && (int)$store->meta('sync_probe') > $now-30) {
                return 0;
            }
            $done = 0;
            try {
                $this->locked(function (PDO $mysql) use ($batch,$store,&$done): void {
                    $upsert = self::upsert($mysql);
                    $current = $mysql->prepare('SELECT revision FROM lt_records WHERE id=? FOR UPDATE');
                    foreach ($batch as $job) {
                        // Fetch the newest SQLite row while holding the MySQL writer lock.
                        $row = $store->run('SELECT * FROM records WHERE id=?',[$job['id']])->fetch();
                        if (!$row) {
                            continue;
                        }
                        $mysql->beginTransaction();
                        try {
                            $current->execute([$row['id']]);
                            $previous = $current->fetchColumn();
                            $current->closeCursor();
                            $upsert->execute(array_map(static fn($field)=>$row[$field],Store::FIELDS));
                            $mysql->commit();
                        } catch (Throwable $error) {
                            if ($mysql->inTransaction()) {
                                $mysql->rollBack();
                            }
                            throw $error;
                        }
                        $store->transaction(function () use ($store,$row,$previous): void {
                            if ((int)$previous > (int)$row['revision']) {
                                $store->run('INSERT INTO sync_drift VALUES (?,?,?,?) ON CONFLICT(id) DO UPDATE SET
                                    detected_at=excluded.detected_at,primary_revision=excluded.primary_revision,mirror_revision=excluded.mirror_revision',
                                    [$row['id'],ltNow(),$row['revision'],$previous]);
                            }
                            $store->run('DELETE FROM sync_queue WHERE id=? AND revision=?',[$row['id'],$row['revision']]);
                        });
                        $done++;
                    }
                });
                $store->transaction(function () use ($store,$now): void {
                    $store->meta('sync_result','reachable');
                    $store->meta('sync_probe',(string)$now);
                    $store->meta('sync_success',ltNow());
                    $store->meta('sync_error','');
                    $store->meta('sync_next','0');
                    $store->meta('sync_failures','0');
                });
            } catch (Throwable $error) {
                $message = $error instanceof Problem ? $error->getMessage() : 'MySQL backup operation failed.';
                $store->transaction(function () use ($store,$now,$message): void {
                    $failures = min(10,1+(int)$store->meta('sync_failures'));
                    $next = $now + min(30,2 ** ($failures-1));
                    $store->meta('sync_failures',(string)$failures);
                    $store->meta('sync_next',(string)$next);
                    $store->meta('sync_result','degraded');
                    $store->meta('sync_error',$message);
                    $store->run('UPDATE sync_queue SET attempts=attempts+1,next_at=? WHERE next_at<=?',[$next,$now]);
                });
                if ($force) {
                    throw new Problem($message);
                }
            }
            return $done;
        },false) ?? 0;
    }

    private static function upsert(PDO $mysql): PDOStatement
    {
        // VALUES() is supported by both MySQL and MariaDB.
        $quote = static fn($field)=>chr(96).$field.chr(96);
        $fields = implode(',',array_map($quote,Store::FIELDS));
        $slots = implode(',',array_fill(0,count(Store::FIELDS),'?'));
        $assignments = implode(',',array_map(static fn($field)=>"$field=VALUES($field)",array_map($quote,array_slice(Store::FIELDS,1))));
        return $mysql->prepare("INSERT INTO lt_records ($fields) VALUES ($slots) ON DUPLICATE KEY UPDATE $assignments");
    }

    public function verify(bool $repair = false, string $actor = ''): array
    {
        $store = $this->store;
        if ($store->config['mode'] !== 'dual') {
            return ['ok'=>true,'message'=>'MySQL backup is not configured.'];
        }
        $primary = [];
        $counts = ['sqlite'=>[],'mysql'=>[]];
        foreach ($store->run('SELECT * FROM records') as $row) {
            $primary[$row['id']] = Store::hash($row);
            $counts['sqlite'][$row['kind']] = 1+($counts['sqlite'][$row['kind']] ?? 0);
        }
        return $this->locked(function (PDO $mysql) use ($primary,$counts,$store,$repair,$actor): array {
            $remaining = $primary;
            $stale = $extras = [];
            foreach ($mysql->query('SELECT * FROM lt_records') as $row) {
                $id = $row['id'];
                $counts['mysql'][$row['kind']] = 1+($counts['mysql'][$row['kind']] ?? 0);
                if (!isset($remaining[$id])) {
                    $extras[] = $id;
                } elseif ($remaining[$id] !== Store::hash($row)) {
                    $stale[] = $id;
                }
                unset($remaining[$id]);
            }
            $missing = array_keys($remaining);
            $ok = !$missing && !$stale && !$extras;
            if ($repair && !$ok) {
                $store->transaction(function () use ($store,$missing,$stale,$extras,$mysql,$actor): void {
                    foreach ([...$missing,...$stale] as $id) {
                        $revision = $store->run('SELECT revision FROM records WHERE id=?',[$id])->fetchColumn();
                        if ($revision !== false) {
                            $store->queue($id,(int)$revision);
                        }
                    }
                    $remove = $mysql->prepare('DELETE FROM lt_records WHERE id=?');
                    foreach ($extras as $id) {
                        if (!$store->run('SELECT 1 FROM records WHERE id=?',[$id])->fetchColumn()) {
                            $remove->execute([$id]);
                        }
                    }
                    $store->meta('sync_next','0');
                    $store->audit($actor,'sync_repair',['queued'=>count($missing)+count($stale),'extras'=>count($extras)]);
                });
            }
            $history = $store->run('SELECT * FROM sync_drift ORDER BY detected_at DESC LIMIT 100')->fetchAll();
            return compact('ok','counts','missing','stale','extras','history') + ['repairQueued'=>$repair && !$ok];
        });
    }

    public function status(): array
    {
        $store = $this->store;
        $dual = $store->config['mode'] === 'dual';
        return [
            'mode'=>$store->config['mode'],
            'mysql'=>$dual ? ($store->meta('sync_result') ?? 'pending') : 'not configured',
            'queueDepth'=>$dual ? (int)$store->run('SELECT COUNT(*) FROM sync_queue')->fetchColumn() : 0,
            'lastSync'=>$dual ? ($store->meta('sync_success') ?: null) : null,
            'lastError'=>$dual ? ($store->meta('sync_error') ?: null) : null,
        ];
    }
}

