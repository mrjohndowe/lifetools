<?php
declare(strict_types=1);

final class Install
{
    private const STATE = LT_ROOT . '/storage/installation.json';

    public static function requirements(): void
    {
        if (PHP_VERSION_ID < 80100) {
            throw new Problem('LifeTools requires PHP 8.1 or later.');
        }
        foreach (['pdo_sqlite','sqlite3','pdo_mysql','openssl','mbstring','fileinfo','json'] as $extension) {
            if (!extension_loaded($extension)) {
                throw new Problem("Enable extension=$extension in php.ini, then restart Apache.");
            }
        }
        foreach (['config','data','backups','uploads','storage'] as $dir) {
            if (!ltWritable(LT_ROOT . '/' . $dir)) {
                throw new Problem("Grant PHP write access to the $dir/ directory.");
            }
        }
    }

    public static function state(array $result): array
    {
        if (!is_file(self::STATE)) {
            return ['phase'=>$result['status'] === 'MISSING' && !is_file(LT_ROOT.'/data/lifetools.sqlite') ? 'fresh' : 'incomplete'];
        }
        $state = json_decode((string)@file_get_contents(self::STATE),true);
        if (!is_array($state) || !isset($state['id'],$state['status'])) {
            return ['phase'=>'recovery'];
        }
        if ($state['status'] === 'locked') {
            return ['phase'=>$result['status'] === 'OK' ? 'configured' : 'recovery','id'=>$state['id']];
        }
        // A prepared marker precedes commit. A committed install remains locked even if publishing the final marker failed.
        if (is_file($state['database'])) {
            try {
                $db = new PDO('sqlite:'.$state['database']);
                if ($db->query("SELECT value FROM meta WHERE name='installation'")->fetchColumn()) {
                    return ['phase'=>$result['status'] === 'OK' ? 'configured' : 'recovery','id'=>$state['id']];
                }
            } catch (PDOException) {
                return ['phase'=>'recovery'];
            }
        }
        return ['phase'=>'incomplete'];
    }

    public static function boot(array $result): Store
    {
        $state = self::state($result);
        if ($state['phase'] !== 'configured') {
            throw new Problem($state['phase'] === 'recovery'
                ? 'Recovery required. Restore the original config, encryption key and SQLite database. See SECURITY.md.'
                : ($result['error'] ?? 'Finish onboarding before opening LifeTools.'));
        }
        $store = new Store($result['config']);
        try {
            $store->checkInstallation($state['id']);
        } catch (PDOException) {
            throw new Problem('The configured SQLite file does not contain this installation. Restore the original database.');
        }
        $store->migrate();
        $store->prepareMode();
        return $store;
    }

    public static function apply(array $input, array $flags = []): array
    {
        return ltLock(LT_ROOT . '/storage/setup.lock', function () use ($input,$flags): array {
            self::requirements();
            $found = Config::detect($flags);
            if (in_array(self::state($found)['phase'],['configured','recovery'],true)) {
                throw new Problem('Onboarding is locked for this installation.');
            }
            $password = Catalog::text($input['password'] ?? '',72,true);
            if (strlen($password) < 12 || $password !== ($input['confirmation'] ?? '')) {
                throw new Problem('Enter matching passwords with at least 12 characters.');
            }
            if (($input['key_saved'] ?? '') !== '1') {
                throw new Problem('Confirm that you saved the encryption key.');
            }
            $secret = Catalog::text($input['key'] ?? '',100,true);
            $raw = [
                'version'=>1,'dbMode'=>$input['mode'] ?? 'sqlite','sqlitePath'=>$input['path'] ?? './data/lifetools.sqlite',
                'mysql'=>[
                    'host'=>$input['mysql_host'] ?? '127.0.0.1','port'=>$input['mysql_port'] ?? '3306',
                    'database'=>$input['mysql_database'] ?? 'lifetools','user'=>$input['mysql_user'] ?? 'root',
                    'password'=>$input['mysql_password'] ?? '',
                ],
                'app'=>[
                    'encryptionKey'=>$secret,'sessionSecret'=>bin2hex(random_bytes(32)),
                    'port'=>$input['port'] ?? 3000,'theme'=>$input['theme'] ?? 'system',
                    'timeZone'=>$input['zone'] ?? 'UTC','name'=>$input['name'] ?? 'LifeTools',
                    'currency'=>$input['currency'] ?? 'USD','dateFormat'=>$input['format'] ?? 'Y-m-d',
                ],
            ];
            $c = Config::validate($raw,$flags);
            $store = new Store($c,true);
            $store->migrate();
            if ($store->meta('installation') !== null
                || $store->run("SELECT 1 FROM records WHERE kind='users' LIMIT 1")->fetchColumn()) {
                throw new Problem('This database already belongs to an installation. Follow the recovery instructions.');
            }
            $directory = Config::secretDirectory();
            if (!is_dir($directory) && !@mkdir($directory,0700,true) && !is_dir($directory)) {
                throw new Problem('Create a writable private key directory outside the web root, or set LIFETOOLS_KEY_DIR.');
            }
            if (!ltWritable($directory) || !Config::outsideWeb($directory)) {
                throw new Problem('The private key directory must be writable and outside the web root.');
            }
            $directory = realpath($directory);
            $id = ltUuid();
            $secrets = [
                'key'=>base64_encode($c['key']),'session'=>$c['session'],
            ];
            if ($c['mode'] === 'dual') {
                $secrets['mysql'] = $c['mirror']['password'];
            }
            $files = [];
            $configPath = $found['source'] !== 'none' ? $found['path'] : Config::path('config/lifetools.config.json');
            $previousConfig = is_file($configPath) ? file_get_contents($configPath) : null;
            $published = false;
            $committed = false;
            try {
                foreach ($secrets as $name=>$value) {
                    $path = $directory . '/' . $id . '-' . $name . '.key';
                    ltAtomic($path,$value);
                    $files[$name] = $path;
                }
                $raw['app']['encryptionKey'] = 'secret:' . $files['key'];
                $raw['app']['sessionSecret'] = 'secret:' . $files['session'];
                $raw['mysql']['password'] = isset($files['mysql']) ? 'secret:' . $files['mysql'] : 'env:MYSQL_PASSWORD';
                $marker = ['id'=>$id,'status'=>'prepared','database'=>$c['path']];
                ltAtomic(self::STATE,ltJson($marker));
                $user = $store->transaction(function () use ($store,$raw,$c,$input,$id,$configPath,&$published): array {
                    $store->meta('installation',$id . ':' . hash_hmac('sha256',$id,$c['key']));
                    $store->meta('mode',$c['mode']);
                    $store->meta('mirror_target',$c['mode'] === 'dual' ? hash('sha256',ltJson($c['mirror'])) : '');
                    $user = $store->createUser(Catalog::text($input['login'] ?? '',80),$input['password'],'admin',$c);
                    if (($input['seed'] ?? '') === '1') {
                        Maintenance::seedInTransaction($store,$user['id'],Store::payload($user));
                    }
                    $store->audit($user['id'],'onboarding_complete',['mode'=>$c['mode']]);
                    ltAtomic($configPath,ltJson($raw) . "\n");
                    $published = true;
                    return $user;
                });
                $committed = true;
                $marker['status'] = 'locked';
                ltAtomic(self::STATE,ltJson($marker));
                return [$user,$c];
            } finally {
                if (!$committed) {
                    if ($published) {
                        if (is_string($previousConfig)) {
                            ltAtomic($configPath,$previousConfig);
                        } else {
                            unlink($configPath);
                        }
                    }
                    foreach ($files as $path) {
                        unlink($path);
                    }
                }
            }
        });
    }
}

