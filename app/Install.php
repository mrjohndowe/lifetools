<?php
declare(strict_types=1);

final class Install
{
    private const STATE = LT_ROOT . '/storage/installation.json';

    public static function requirements(): void
    {
        if (PHP_VERSION_ID < 80100) {
            throw new Problem(ltText('lifetools_requires_php_8_1_or'));
        }
        foreach (['pdo_sqlite','sqlite3','pdo_mysql','openssl','mbstring','fileinfo','json'] as $extension) {
            if (!extension_loaded($extension)) {
                throw new Problem(ltText('enable_extension_extension_in_php_ini',['extension'=>$extension]));
            }
        }
        foreach (['config','data','backups','uploads','storage'] as $dir) {
            if (!ltWritable(LT_ROOT . '/' . $dir)) {
                throw new Problem(ltText('grant_php_write_access_to_the',['dir'=>$dir]));
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
                ? ltText('recovery_required_restore_the_original_config')
                : ($result['error'] ?? ltText('finish_onboarding_before_opening_lifetools')));
        }
        $store = new Store($result['config']);
        try {
            $store->checkInstallation($state['id']);
        } catch (PDOException) {
            throw new Problem(ltText('the_configured_sqlite_file_does_not'));
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
                throw new Problem(ltText('onboarding_is_locked_for_this_installation'));
            }
            $password = Catalog::text($input['password'] ?? '',72,true);
            if (strlen($password) < 12 || $password !== ($input['confirmation'] ?? '')) {
                throw new Problem(ltText('enter_matching_passwords_with_at_least'));
            }
            if (($input['key_saved'] ?? '') !== '1') {
                throw new Problem(ltText('confirm_that_you_saved_the_encryption'));
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
                throw new Problem(ltText('this_database_already_belongs_to_an'));
            }
            $directory = Config::secretDirectory();
            if (!is_dir($directory) && !@mkdir($directory,0700,true) && !is_dir($directory)) {
                throw new Problem(ltText('create_a_writable_private_key_directory'));
            }
            if (!ltWritable($directory) || !Config::outsideWeb($directory)) {
                throw new Problem(ltText('the_private_key_directory_must_be'));
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

