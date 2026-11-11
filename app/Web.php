<?php
declare(strict_types=1);

final class Web
{
    public static function main(): void
    {
        ob_start();
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header('Content-Language: '.ltLanguage());
        header("Content-Security-Policy: default-src 'self'; frame-ancestors 'none'; object-src 'none'; base-uri 'none'; form-action 'self'");
        $api = isset($_GET['api']);
        try {
            if (str_contains(strtolower($_SERVER['SERVER_SOFTWARE'] ?? ''),'apache') && getenv('LIFETOOLS_APACHE_GUARD') !== '1'
                && ($_SERVER['LIFETOOLS_APACHE_GUARD'] ?? '') !== '1') {
                throw new Problem(ltText('apache_is_ignoring_the_lifetools_htaccess'));
            }
            Install::requirements();
            self::session();
            $action = $_POST['action'] ?? '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                self::csrf();
                if ($action === 'language') {
                    $language = $_POST['language'] ?? '';
                    if (is_string($language) && isset(LT_LANGUAGES[$language])) {
                        $cookie = session_get_cookie_params();
                        setcookie('lifetools_language',$language,[
                            'expires'=>time()+31536000,'path'=>$cookie['path'],'samesite'=>'Lax',
                            'secure'=>$cookie['secure'],'httponly'=>true,
                        ]);
                    }
                    self::redirect(array_intersect_key($_GET,array_flip(['view','kind','id','date','mode','trash','q','p'])));
                }
            }
            $result = Config::detect();
            $state = Install::state($result);
            if ($state['phase'] !== 'configured') {
                if ($state['phase'] === 'recovery' || $result['status'] === 'INVALID') {
                    throw new Problem(ltText('configuration_status_at_path_error_restore',['status'=>ltOption($result['status']),'path'=>$result['path'],'error'=>$result['error'] ?? '']));
                }
                if (!in_array($_SERVER['REMOTE_ADDR'] ?? '',['127.0.0.1','::1','::ffff:127.0.0.1'],true)) {
                    http_response_code(403);
                    Views::error(ltText('open_the_installer_from_localhost_on'));
                    return;
                }
                if ($action === 'generate_key') {
                    self::json(['key'=>base64_encode(random_bytes(32))]);
                }
                if ($action === 'test_mysql') {
                    Mirror::connect([
                        'host'=>self::mysqlHost($_POST['mysql_host'] ?? ''),'port'=>self::mysqlPort($_POST['mysql_port'] ?? ''),
                        'database'=>self::mysqlDatabase($_POST['mysql_database'] ?? ''),
                        'user'=>Catalog::text($_POST['mysql_user'] ?? '',128),
                        'password'=>Catalog::text($_POST['mysql_password'] ?? '',1024,true),
                    ]);
                    self::json(['message'=>ltText('mysql_is_reachable')]);
                }
                $error = null;
                if ($action === 'install') {
                    try {
                        [$user,$c] = Install::apply($_POST);
                        $store = Install::boot(Config::detect());
                        self::signIn($user,$store);
                        self::redirect();
                    } catch (Problem $problem) {
                        $error = $problem->getMessage();
                    }
                }
                Views::setup($result,$error);
                return;
            }
            $store = Install::boot($result);
            $user = self::current($store);
            if (!$user) {
                $error = null;
                if ($action === 'login') {
                    $error = self::login($store);
                }
                if ($api) {
                    self::json(['error'=>ltText('sign_in_to_continue')],401);
                }
                Views::login($store->config,$error);
                return;
            }
            $prefs = Store::payload($user);
            $owner = $user['id'];
            $admin = $prefs['role'] === 'admin';
            if (isset($_GET['quick']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
                header('X-LifeTools-Fragment: editor');
                Views::editor($store,$owner,Catalog::text($_GET['quick'],40),$prefs,quick:true);
                return;
            }
            if ($action === 'logout') {
                $store->transaction(fn()=>$store->audit($owner,'logout'));
                $_SESSION = [];
                session_regenerate_id(true);
                self::redirect();
            }
            if (isset($_GET['setup'])) {
                http_response_code(403);
                Views::error(ltText('onboarding_is_locked_for_this_installation'));
                return;
            }
            $mirror = new Mirror($store);
            if (($_GET['api'] ?? '') === 'health') {
                self::json($mirror->status() + ['configSource'=>$result['source'],'configPath'=>$admin ? $result['path'] : null]);
            }
            if (($_GET['api'] ?? '') === 'reminders') {
                $now = new DateTimeImmutable('now',new DateTimeZone($prefs['zone']));
                $items = Planner::range($store,$owner,$now->modify('-1 day')->format('Y-m-d'),$now->modify('+31 days')->format('Y-m-d'),$prefs['zone']);
                $due = Planner::due($items,$now);
                self::json(['count'=>count($due),'events'=>array_map(static fn($event)=>[
                    'title'=>$event['title'],'url'=>self::url(['view'=>'record','kind'=>'events','id'=>$event['id']]),
                    'time'=>ltDate($event['start'],$event['allDay'] ? 'short_date' : 'short_date_time'),
                ],array_slice($due,0,8))]);
            }
            if ($action === 'sync_tick') {
                session_write_close();
                $mirror->drain(60);
                self::json($mirror->status());
            }
            if (isset($_GET['file'])) {
                self::file($store,$owner);
            }
            $page = (string)($_GET['view'] ?? 'dashboard');
            $notice = $_SESSION['notice'] ?? '';
            unset($_SESSION['notice']);
            $kind = (string)($_POST['kind'] ?? $_GET['kind'] ?? '');
            if ($action === 'save') {
                $row = Catalog::save($store,$owner,$kind,$_POST,$prefs,$_FILES['upload'] ?? null);
                if ($api) {
                    self::json(['id'=>$row['id'],'revision'=>$row['revision'],'saved'=>$row['updated_at']]);
                }
                if ($kind === 'delivery_shifts' && ($_POST['return_to_income'] ?? '') === '1') {
                    self::flash(ltText('saved'), ['view'=>'module','kind'=>'income_entries']);
                }
                self::flash(ltText('saved'), ['view'=>'record','kind'=>$kind,'id'=>$row['id']]);
            }
            if (in_array($action,['delete','restore_record','duplicate','schedule'],true)) {
                self::recordAction($store,$owner,$prefs,$kind,$action);
            }
            if ($action === 'reveal') {
                $row = $store->record($owner,(string)($_POST['id'] ?? ''),$kind);
                $field = (string)($_POST['field'] ?? '');
                if ((Catalog::fields($kind,$prefs)[$field][0] ?? '') !== 'secret') {
                    throw new Problem(ltText('this_field_is_not_available_for'));
                }
                try {
                    self::reauth($store,$user);
                } catch (Problem $error) {
                    $store->transaction(fn()=>$store->audit($owner,'reveal_denied',['record'=>$row['id'],'field'=>$field]));
                    throw $error;
                }
                $value = $store->privateValue($row,$field);
                $store->transaction(fn()=>$store->audit($owner,'reveal',['record'=>$row['id'],'field'=>$field]));
                self::json(['value'=>$value,'expiresIn'=>30]);
            }
            if ($action === 'export') {
                self::export($store,$owner,$kind);
            }
            if ($action === 'settings') {
                self::settings($store,$user,$result);
            }
            Views::shellStart($store,$user,$prefs,$page,$kind,$notice);
            match ($page) {
                'dashboard'=>Views::dashboard($store,$owner,$prefs),
                'calendar'=>Views::calendar($store,$owner,$prefs),
                'module'=>Views::listing($store,$owner,$kind,$prefs),
                'record'=>Views::record($store,$owner,$kind,$prefs),
                'search'=>Views::search($store,$owner),
                'settings'=>Views::settings($store,$user,$result),
                'banks'=>Views::banks($store,$owner),
                default=>throw new Problem(ltText('this_page_could_not_be_found')),
            };
            Views::shellEnd($prefs);
        } catch (Throwable $error) {
            ob_clean();
            $message = $error instanceof Problem ? $error->getMessage() : ltText('lifetools_could_not_complete_this_request');
            if ($api) {
                self::json(['error'=>$message],$error instanceof Problem ? 422 : 500);
            }
            http_response_code($error instanceof Problem ? 400 : 500);
            Views::error($message);
        }
    }

    private static function mysqlHost(mixed $value): string
    {
        $host = Catalog::text($value,253);
        if (!preg_match('/^[A-Za-z0-9_.:-]+$/D',$host)) {
            throw new Problem(ltText('enter_a_valid_mysql_host'));
        }
        return $host;
    }

    private static function mysqlPort(mixed $value): int
    {
        $port = filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
        if ($port === false) {
            throw new Problem(ltText('enter_a_mysql_port_from_1'));
        }
        return $port;
    }

    private static function mysqlDatabase(mixed $value): string
    {
        $database = Catalog::text($value,64);
        if (!preg_match('/^[A-Za-z0-9_]+$/D',$database)) {
            throw new Problem(ltText('enter_a_mysql_database_name_using'));
        }
        return $database;
    }

    private static function session(): void
    {
        ini_set('session.use_strict_mode','1');
        ini_set('session.use_only_cookies','1');
        $path = rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')),'/') . '/';
        session_name('lifetools_' . substr(hash('sha256',LT_ROOT),0,10));
        session_set_cookie_params([
            'lifetime'=>0,'path'=>$path,'httponly'=>true,'samesite'=>'Lax',
            'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    private static function csrf(): void
    {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'],$_POST['csrf'])) {
            throw new Problem(ltText('your_form_expired_reload_the_page'));
        }
    }

    public static function token(): string
    {
        return $_SESSION['csrf'] ?? '';
    }

    private static function signature(Store $store, string $id): string
    {
        return hash_hmac('sha256',session_id().':'.$id.':'.$store->meta('session_generation'),$store->config['session']);
    }

    private static function signIn(array $user, Store $store): void
    {
        session_regenerate_id(true);
        $_SESSION = ['csrf'=>bin2hex(random_bytes(32)),'owner'=>$user['id'],
            'signature'=>self::signature($store,$user['id']),'seen'=>time()];
        self::themeCookie(Store::payload($user)['theme']);
    }

    private static function themeCookie(string $theme): void
    {
        $cookie = session_get_cookie_params();
        setcookie('lifetools_theme',$theme,['path'=>$cookie['path'],'samesite'=>'Lax',
            'secure'=>$cookie['secure'],'httponly'=>true]);
    }

    private static function current(Store $store): ?array
    {
        $id = $_SESSION['owner'] ?? '';
        if ($id === '' || ($_SESSION['seen'] ?? 0) < time()-43200 ||
            !hash_equals(self::signature($store,$id),$_SESSION['signature'] ?? '')) {
            return null;
        }
        $_SESSION['seen'] = time();
        return $store->run("SELECT * FROM records WHERE id=? AND kind='users' AND deleted_at IS NULL",[$id])->fetch() ?: null;
    }

    private static function login(Store $store): string
    {
        $identity = Catalog::text($_POST['login'] ?? '',80);
        $password = Catalog::text($_POST['password'] ?? '',72,true);
        $throttle = 'login:' . hash_hmac('sha256',$_SERVER['REMOTE_ADDR'] ?? 'local',$store->config['session']);
        if (!$store->allowAttempt($throttle)) {
            return ltText('too_many_attempts_wait_five_minutes');
        }
        $user = $store->user($identity);
        $hash = $user ? Store::payload($user)['passwordHash'] : '$2y$12$iNwO1CVKyD0ASj3jPTZxTeLb6zVywC92v37R8YYVH6xzjR7R6GqgS';
        if (!password_verify($password,$hash) || !$user) {
            $store->transaction(fn()=>$store->audit($user['id'] ?? '','login_failed'));
            return ltText('the_login_or_password_is_incorrect');
        }
        $store->transaction(function () use ($store,$user,$throttle): void {
            $store->run('DELETE FROM throttle WHERE key=?',[$throttle]);
            $store->audit($user['id'],'login');
        });
        self::signIn($user,$store);
        self::redirect();
    }

    private static function reauth(Store $store,array $user): void
    {
        if (!$store->allowAttempt('reauth:' . $user['id'])) {
            throw new Problem(ltText('too_many_verification_attempts_try_again'));
        }
        if (!password_verify(Catalog::text($_POST['password'] ?? '',72,true),Store::payload($user)['passwordHash'])) {
            throw new Problem(ltText('the_application_password_is_incorrect'));
        }
        $store->run('DELETE FROM throttle WHERE key=?',['reauth:' . $user['id']]);
    }

    private static function file(Store $store,string $owner): never
    {
        $row = $store->record($owner,(string)$_GET['file'],'attachments');
        $p = Store::payload($row);
        $bytes = $store->privateValue($row,'file');
        if (!hash_equals($p['sha256'],hash('sha256',$bytes))) {
            throw new Problem(ltText('the_file_checksum_did_not_match'));
        }
        header('Content-Type: ' . $p['mime']);
        header('Content-Length: ' . strlen($bytes));
        $extension = ['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'][$p['mime']];
        header('Content-Disposition: ' . ($extension === 'pdf' ? 'attachment' : 'inline') . '; filename="lifetools-file.' . $extension . '"');
        echo $bytes;
        exit;
    }

    private static function recordAction(Store $store,string $owner,array $prefs,string $kind,string $action): never
    {
        if (!isset(Catalog::LABELS[$kind])) {
            throw new Problem(ltText('unknown_module'));
        }
        $row = $store->transaction(function () use ($store,$owner,$kind,$action,$prefs): array {
            $row = $store->record($owner,(string)($_POST['id'] ?? ''),$kind,$action === 'restore_record');
            if ((string)$row['revision'] !== (string)($_POST['revision'] ?? '')) {
                throw new Problem(ltText('this_record_changed_in_another_view_reload'));
            }
            $p = Store::payload($row);
            unset($p['_private']);
            if ($action === 'duplicate' && $kind === 'events') {
                return $store->save($owner,$kind,mb_substr($row['title'],0,167).' (copy)',$p);
            }
            if ($action === 'schedule' && in_array($kind,['vehicle_documents','documents','appointments','work_shifts','chores','contacts'],true)) {
                $start = match ($kind) {
                    'vehicle_documents'=>$p['date'],
                    'documents'=>$p['expires'],
                    'chores'=>$p['due'],
                    'contacts'=>$p['dob'],
                    default=>$p['time'],
                };
                if (!$start) {
                    throw new Problem(ltText('save_a_date_before_adding_this'));
                }
                $allDay = strlen($start) === 10;
                if ($kind === 'contacts') {
                    $today = new DateTimeImmutable('today',new DateTimeZone($prefs['zone']));
                    $start = self::birthday($start,$today)->format('Y-m-d');
                }
                $zone = new DateTimeZone($prefs['zone']);
                $event = [
                    'title'=>$kind === 'contacts' ? ltText('birthday_name',['name'=>$row['title']]) : $row['title'],
                    'start'=>$allDay ? $start : (new DateTimeImmutable($start))->setTimezone($zone)->format('Y-m-d\TH:i'),
                    'all_day'=>$allDay ? '1' : '0','category'=>in_array($kind,['appointments','work_shifts'],true) ? 'work' : 'personal',
                    'contact'=>$kind === 'contacts' ? $row['id'] : '',
                    'frequency'=>$kind === 'contacts' ? 'yearly' : ($kind === 'chores' ? $p['frequency'] : ''),
                    'interval'=>$kind === 'chores' ? $p['interval'] : '',
                    'related'=>$kind === 'documents' ? [$row['id']] : ($p['_links']['vehicle'] ?? []),'reminders'=>'1440',
                ];
                if ($kind === 'work_shifts' && $p['end']) {
                    $event['end'] = (new DateTimeImmutable($p['end']))->setTimezone($zone)->format('Y-m-d\TH:i');
                }
                return Catalog::write($store,$owner,'events',$event,$prefs);
            }
            if (!in_array($action,['delete','restore_record'],true)) {
                throw new Problem(ltText('this_action_is_unavailable_for_the'));
            }
            return $store->save($owner,$kind,$row['title'],$p,old:$row,deleted:$action === 'delete');
        });
        if ($action === 'delete') {
            self::flash(ltText('moved_to_trash'), ['view'=>'module','kind'=>$kind]);
        }
        self::flash(ltText('saved'), ['view'=>'record','kind'=>$row['kind'],'id'=>$row['id']]);
    }

    public static function birthday(string $dob, DateTimeImmutable $today): DateTimeImmutable
    {
        $year = (int)$today->format('Y');
        $month = (int)substr($dob,5,2);
        $day = (int)substr($dob,8,2);
        do {
            $date = $today->setDate($year,$month,min($day,cal_days_in_month(CAL_GREGORIAN,$month,$year)));
            $year++;
        } while ($date < $today);
        return $date;
    }

    private static function export(Store $store,string $owner,string $kind): never
    {
        if ($kind !== '' && (!isset(Catalog::LABELS[$kind]) || in_array($kind,['intimacy_events','journal_entries','daily_logs'],true))) {
            throw new Problem(ltText('this_module_is_excluded_from_general'));
        }
        $store->transaction(fn()=>$store->audit($owner,'export',['kind'=>$kind ?: 'all']));
        $rows = $store->run("SELECT * FROM records WHERE owner=? AND deleted_at IS NULL
            AND kind NOT IN ('users','audit_log','journal_entries','intimacy_events','daily_logs')" .
            ($kind === '' ? '' : ' AND kind=?'),$kind === '' ? [$owner] : [$owner,$kind]);
        $clean = static function (array $row): array {
            $p = Store::payload($row);
            unset($p['_private']);
            return ['id'=>$row['id'],'kind'=>$row['kind'],'title'=>$row['title'],'created_at'=>$row['created_at'],
                'updated_at'=>$row['updated_at'],'data'=>$p];
        };
        if ($kind === '') {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="lifetools-export.json"');
            echo '[';
            $separator = '';
            foreach ($rows as $row) {
                echo $separator . ltJson($clean($row));
                $separator = ',';
            }
            echo ']';
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="lifetools-' . $kind . '.csv"');
            $out = fopen('php://output','wb');
            $fields = array_keys(Catalog::fields($kind));
            fputcsv($out,['id','title','created_at','updated_at',...$fields],',','"','');
            foreach ($rows as $row) {
                $item = $clean($row);
                $values = [$item['id'],$item['title'],$item['created_at'],$item['updated_at']];
                foreach ($fields as $field) {
                    $value = $item['data'][$field] ?? $item['data']['_links'][$field] ?? '';
                    $values[] = is_array($value) ? ltJson($value) : (string)$value;
                }
                $values = array_map(static fn($v)=>preg_match('/^[\s]*[=+\-@]/',$v) ? "'".$v : $v,$values);
                fputcsv($out,$values,',','"','');
            }
        }
        exit;
    }

    private static function settings(Store $store,array $user,array $result): never
    {
        $operation = $_POST['operation'] ?? '';
        $prefs = Store::payload($user);
        $owner = $user['id'];
        if ($operation === 'preferences' || $operation === 'theme') {
            $theme = $_POST['theme'] ?? '';
            if (!in_array($theme,['system','light','dark'],true)) {
                throw new Problem(ltText('choose_an_available_theme'));
            }
            $store->transaction(function () use ($store,$owner,$operation,$theme): void {
                $current = $store->record($owner,$owner,'users');
                $p = Store::payload($current);
                $p['theme'] = $theme;
                if ($operation === 'preferences') {
                    if (!in_array($_POST['zone'] ?? '',DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC),true)
                        || !preg_match('/^[A-Z]{3}$/D',(string)($_POST['currency'] ?? ''))
                        || !in_array($_POST['format'] ?? '',['Y-m-d','m/d/Y','d/m/Y'],true)) {
                        throw new Problem(ltText('check_the_time_zone_currency_and'));
                    }
                    foreach (['zone','currency','format','relationships','account_types','intimacy_types','intimacy_questions'] as $name) {
                        $p[$name] = Catalog::text($_POST[$name] ?? '',1500);
                    }
                }
                $store->save($owner,'users',$current['title'],$p,old:$current);
            });
            self::themeCookie($theme);
            if (isset($_GET['api'])) {
                self::json(['theme'=>$theme]);
            }
            self::flash(ltText('preferences_saved'),['view'=>'settings']);
        }
        if ($operation === 'seed') {
            $year = Maintenance::seed($store,$owner,$prefs);
            self::flash(ltText('synthetic_records_loaded_for_year',['year'=>$year]),['view'=>'settings']);
        }
        if ($prefs['role'] !== 'admin') {
            throw new Problem(ltText('administrator_access_is_required'));
        }
        self::reauth($store,$user);
        $mirror = new Mirror($store);
        $output = match ($operation) {
            'create_user'=>$store->transaction(function () use ($store,$owner): array {
                $role = $_POST['role'] ?? 'standard';
                if (!in_array($role,['admin','standard'],true)) {
                    throw new Problem(ltText('select_an_available_role'));
                }
                $new = $store->createUser(Catalog::text($_POST['login'] ?? '',80),
                    Catalog::text($_POST['new_password'] ?? '',72,true),$role,$store->config);
                $store->audit($owner,'user_created',['id'=>$new['id'],'role'=>$role]);
                return ['message'=>ltText('user_created')];
            }),
            'verify'=>$mirror->verify(),
            'repair'=>$mirror->verify(true,$owner),
            'drain'=>['mirrored'=>$mirror->drain(1000,true)],
            'migrate'=>self::migrate($store,$mirror),
            'backup'=>Maintenance::backup($store,$owner),
            'restore'=>Maintenance::restore($store,Catalog::text($_POST['backup'] ?? '',40),($_POST['from_mirror'] ?? '') === '1'),
            'reconfigure'=>self::reconfigure($result),
            'mirror_config'=>Config::updateMirror($result,$_POST),
            default=>throw new Problem(ltText('choose_a_maintenance_action')),
        };
        $_SESSION['maintenance'] = $output;
        self::flash(ltText('maintenance_action_finished'),['view'=>'settings']);
    }

    private static function migrate(Store $store,Mirror $mirror): array
    {
        $store->migrate();
        if ($store->config['mode'] === 'dual') {
            $mirror->locked(static fn()=>null);
        }
        return ['schemaVersion'=>Store::VERSION];
    }

    private static function reconfigure(array $result): array
    {
        return ['message'=>ltText('edit_the_listed_protected_config_file'),
            'config'=>Config::public($result)];
    }

    public static function url(array $params = []): string
    {
        return 'index.php' . ($params ? '?' . http_build_query($params) : '');
    }

    public static function flash(string $message,array $params): never
    {
        $_SESSION['notice'] = $message;
        self::redirect($params);
    }

    public static function redirect(array $params = []): never
    {
        header('Location: '.self::url($params),true,303);
        exit;
    }

    public static function json(array $data,int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo ltJson($data);
        exit;
    }
}

