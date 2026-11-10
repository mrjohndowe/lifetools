<?php
declare(strict_types=1);

final class Config
{
    public const FLAGS = [
        'db-mode'=>'DB_MODE', 'sqlite-path'=>'SQLITE_PATH', 'mysql-host'=>'MYSQL_HOST',
        'mysql-port'=>'MYSQL_PORT', 'mysql-database'=>'MYSQL_DATABASE', 'mysql-user'=>'MYSQL_USER',
        'mysql-password'=>'MYSQL_PASSWORD', 'encryption-key'=>'APP_ENCRYPTION_KEY',
        'session-secret'=>'SESSION_SECRET', 'port'=>'PORT', 'time-zone'=>'TZ',
    ];

    public static function env(string $name): ?string
    {
        $value = getenv($name);
        if ($value !== false) {
            return $value;
        }
        static $local;
        if ($local === null) {
            $local = [];
            $path = LT_ROOT . '/.env';
            if (is_file($path)) {
                foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
                    if (preg_match('/^\s*([A-Z_][A-Z_0-9]*)\s*=(.*)$/', $line, $parts)) {
                        $local[$parts[1]] = trim($parts[2]);
                    }
                }
            }
        }
        return $local[$name] ?? null;
    }

    public static function path(string $value): string
    {
        if (str_starts_with($value, '/') || str_starts_with($value, '\\') || preg_match('~^[A-Za-z]:[/\\\\]~', $value)) {
            return $value;
        }
        return LT_ROOT . '/' . $value;
    }

    public static function detect(array $flags = []): array
    {
        $home = self::env('XDG_CONFIG_HOME') ?: (PHP_OS_FAMILY === 'Windows' ? self::env('APPDATA') : null);
        $home ??= (self::env('HOME') ?: LT_ROOT) . '/.config';
        $candidates = [
            'cli'=>$flags['config'] ?? null,
            'env'=>self::env('LIFETOOLS_CONFIG'),
            'root'=>'lifetools.config.json',
            'config'=>'config/lifetools.config.json',
            'user'=>$home . '/lifetools/config.json',
        ];
        foreach ($candidates as $source => $candidate) {
            if (!$candidate) {
                continue;
            }
            $path = self::path($candidate);
            if (!file_exists($path)) {
                continue;
            }
            try {
                $raw = @file_get_contents($path);
                $data = $raw === false ? null : json_decode($raw, true);
                if (!is_array($data) || array_is_list($data)) {
                    throw new Problem(ltText('config_expected_a_readable_json_object'));
                }
                return ['status'=>'OK', 'source'=>$source, 'path'=>$path, 'config'=>self::validate($data, $flags)];
            } catch (Problem $e) {
                return ['status'=>'INVALID', 'source'=>$source, 'path'=>$path, 'error'=>$e->getMessage()];
            }
        }
        $mode = $flags['db-mode'] ?? self::env('DB_MODE');
        if ($mode !== null && $mode !== '' && !in_array($mode,['sqlite','dual'],true)) {
            return ['status'=>'INVALID','source'=>isset($flags['db-mode']) ? 'cli' : 'env',
                'path'=>self::path('config/lifetools.config.json'),
                'error'=>ltText('dbmode_expected_sqlite_or_dual_sqlite')];
        }
        return ['status'=>'MISSING', 'source'=>'none', 'path'=>self::path('config/lifetools.config.json'),
            'error'=>ltText('open_lifetools_on_localhost_to_run')];
    }

    public static function secretDirectory(): string
    {
        $directory = self::env('LIFETOOLS_KEY_DIR');
        if (!$directory) {
            $parent = self::env(PHP_OS_FAMILY === 'Windows' ? 'LOCALAPPDATA' : 'XDG_CONFIG_HOME');
            $parent ??= PHP_OS_FAMILY === 'Windows'
                ? dirname($_SERVER['DOCUMENT_ROOT'] ?? LT_ROOT)
                : ((self::env('HOME') ?: dirname(LT_ROOT)) . '/.config');
            $directory = $parent . '/LifeTools-private';
        }
        return self::path($directory);
    }

    public static function outsideWeb(string $path): bool
    {
        $path = realpath($path);
        if ($path === false) {
            return false;
        }
        $normalize = static function (string $value): string {
            $value = rtrim(str_replace('\\', '/', $value), '/');
            return PHP_OS_FAMILY === 'Windows' ? strtolower($value) : $value;
        };
        $path = $normalize($path);
        foreach ([LT_ROOT, $_SERVER['DOCUMENT_ROOT'] ?? ''] as $root) {
            $root = $root === '' ? false : realpath($root);
            if ($root !== false) {
                $root = $normalize($root);
                if ($path === $root || str_starts_with($path, $root . '/')) {
                    return false;
                }
            }
        }
        return true;
    }

    private static function resolve(mixed $value, string $name): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        if (str_starts_with($value, 'env:')) {
            return self::env(substr($value, 4));
        }
        if (str_starts_with($value, 'secret:')) {
            $path = substr($value, 7);
            if (self::outsideWeb($path) && is_file($path)) {
                $read = @file_get_contents($path);
                return $read === false ? null : rtrim($read, "\r\n");
            }
            throw new Problem(ltText('name_expected_a_readable_secret_file',['name'=>$name]));
        }
        return $value;
    }

    public static function validate(array $raw, array $flags = []): array
    {
        if (($raw['version'] ?? null) !== 1) {
            throw new Problem(ltText('version_expected_integer_1'));
        }
        $mysql = $raw['mysql'] ?? [];
        $app = $raw['app'] ?? [];
        if (!is_array($mysql) || !is_array($app)) {
            throw new Problem(ltText('mysql_and_app_expected_objects'));
        }
        $flagValues = [];
        foreach (self::FLAGS as $flag => $variable) {
            if (isset($flags[$flag])) {
                $flagValues[$variable] = $flags[$flag];
            }
        }
        $pick = static function (string $name, mixed $file, mixed $default = null) use ($flagValues): mixed {
            $env = self::env($name);
            $value = $flagValues[$name] ?? ($env === '' ? null : $env) ?? $file ?? $default;
            return self::resolve($value, $name);
        };
        $mode = $pick('DB_MODE', $raw['dbMode'] ?? null, 'sqlite');
        if (!in_array($mode, ['sqlite','dual'], true)) {
            throw new Problem(ltText('dbmode_expected_sqlite_or_dual_sqlite'));
        }
        $path = $pick('SQLITE_PATH', $raw['sqlitePath'] ?? null, './data/lifetools.sqlite');
        if (!is_string($path) || trim($path) === '' || str_contains($path, "\0")) {
            throw new Problem(ltText('sqlitepath_expected_a_database_file_path'));
        }
        $path = self::path($path);
        $parent = realpath(dirname($path));
        $data = realpath(LT_ROOT . '/data');
        $protected = $parent !== false && $data !== false
            && ($parent === $data || str_starts_with($parent, $data . DIRECTORY_SEPARATOR));
        if ($parent === false || !ltWritable($parent) || is_dir($path)
            || (file_exists($path) && (!is_file($path) || !ltWritable($path)))
            || (!$protected && !self::outsideWeb($parent))) {
            throw new Problem(ltText('sqlitepath_expected_a_writable_file_in'));
        }
        $path = $parent . DIRECTORY_SEPARATOR . basename($path);
        $key = $pick('APP_ENCRYPTION_KEY', $app['encryptionKey'] ?? null);
        $key = is_string($key) ? base64_decode($key, true) : false;
        if ($key === false || strlen($key) !== 32) {
            throw new Problem(ltText('app_encryptionkey_expected_exactly_32_bytes'));
        }
        $session = $pick('SESSION_SECRET', $app['sessionSecret'] ?? null);
        if (!is_string($session) || strlen($session) < 32) {
            throw new Problem(ltText('app_sessionsecret_expected_at_least_32'));
        }
        $port = self::port($pick('PORT', $app['port'] ?? null, 3000), 'app.port');
        $zone = $pick('TZ', $app['timeZone'] ?? null, 'UTC');
        $theme = $app['theme'] ?? 'system';
        $name = $app['name'] ?? 'LifeTools';
        $currency = $app['currency'] ?? 'USD';
        $format = $app['dateFormat'] ?? 'Y-m-d';
        if (!in_array($zone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
            throw new Problem(ltText('app_timezone_expected_an_iana_time'));
        }
        if (!in_array($theme, ['system','light','dark'], true)) {
            throw new Problem(ltText('app_theme_expected_system_light_or'));
        }
        if (!is_string($name) || trim($name) === '' || strlen($name) > 80) {
            throw new Problem(ltText('app_name_expected_1_80_characters'));
        }
        if (!is_string($currency) || !preg_match('/^[A-Z]{3}$/D', $currency)) {
            throw new Problem(ltText('app_currency_expected_a_three_letter'));
        }
        if (!in_array($format, ['Y-m-d','m/d/Y','d/m/Y'], true)) {
            throw new Problem(ltText('app_dateformat_expected_y_m_d'));
        }
        $mirror = [];
        if ($mode === 'dual') {
            foreach (['host'=>'127.0.0.1','database'=>'lifetools','user'=>'lifetools','password'=>''] as $nameKey => $default) {
                $value = $pick('MYSQL_' . strtoupper($nameKey), $mysql[$nameKey] ?? null, $default);
                $pattern = match ($nameKey) {
                    'host'=>'/^[A-Za-z0-9_.:-]+$/D',
                    'database'=>'/^[A-Za-z0-9_]+$/D',
                    default=>null,
                };
                if (!is_string($value) || ($nameKey !== 'password' && trim($value) === '')
                    || ($pattern !== null && !preg_match($pattern, $value))) {
                    throw new Problem(ltText('mysql_namekey_invalid_connection_setting',['nameKey'=>$nameKey]));
                }
                $mirror[$nameKey] = $value;
            }
            $mirror['port'] = self::port($pick('MYSQL_PORT', $mysql['port'] ?? null, 3306), 'mysql.port');
        }
        return compact('mode','path','key','session','port','zone','theme','name','currency','format','mirror');
    }

    private static function port(mixed $value, string $field): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>65535]]);
        if ($number === false) {
            throw new Problem(ltText('field_expected_an_integer_from_1',['field'=>$field]));
        }
        return $number;
    }

    public static function updateMirror(array $result, array $input): array
    {
        $path = $result['path'];
        return ltLock(LT_ROOT . '/storage/setup.lock', static function () use ($path,$input): array {
            $raw = json_decode((string)@file_get_contents($path),true);
            if (!is_array($raw)) {
                throw new Problem(ltText('restore_the_protected_configuration_before_changing'));
            }
            $mode = $input['mode'] ?? '';
            if (!in_array($mode,['sqlite','dual'],true)) {
                throw new Problem(ltText('choose_sqlite_only_or_sqlite_with'));
            }
            $raw['dbMode'] = $mode;
            $password = Catalog::text($input['mysql_password'] ?? '',1024,true);
            $replace = $password !== '' || ($input['clear_mysql_password'] ?? '') === '1';
            if ($mode === 'dual') {
                $raw['mysql'] ??= [];
                foreach (['host','port','database','user'] as $field) {
                    $raw['mysql'][$field] = Catalog::text($input['mysql_'.$field] ?? '',253);
                }
                if ($replace) {
                    $raw['mysql']['password'] = $password;
                }
            }
            $effective = self::validate($raw);
            $secretPath = null;
            try {
                if ($mode === 'dual' && $replace) {
                    $directory = self::secretDirectory();
                    if ((!is_dir($directory) && !@mkdir($directory,0700,true))
                        || !self::outsideWeb($directory) || !ltWritable($directory)) {
                        throw new Problem(ltText('the_private_key_directory_must_be'));
                    }
                    $secretPath = realpath($directory) . '/' . ltUuid() . '-mysql.key';
                    ltAtomic($secretPath,$password);
                    $raw['mysql']['password'] = 'secret:' . $secretPath;
                }
                ltAtomic($path,ltJson($raw) . "\n");
            } catch (Throwable $e) {
                if ($secretPath && is_file($secretPath)) {
                    unlink($secretPath);
                }
                throw $e;
            }
            return ['message'=>ltText('configuration_saved_restart_apache_and_the'),
                'configuredMode'=>$mode,'effectiveMode'=>$effective['mode']];
        });
    }

    public static function public(array $result): array
    {
        $config = $result['config'] ?? null;
        unset($result['config']);
        if ($config) {
            $config['key'] = $config['session'] = '***';
            if ($config['mirror']) {
                $config['mirror']['password'] = '***';
            }
            $result['config'] = $config;
        }
        return $result;
    }
}

