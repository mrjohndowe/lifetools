<?php
declare(strict_types=1);

final class Views
{
    private const ICONS = [
        'Dashboard'=>'M3 3h7v7H3zm11 0h7v7h-7zm0 11h7v7h-7zm-11 0h7v7H3z',
        'Timeline'=>'M5 3v18m0-14h14M5 12h10M5 17h14',
        'Calendar'=>'M4 6h16v15H4zm0 5h16M8 3v6m8-6v6m-8 6h2m4 0h2',
        'Contacts'=>'M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-8 10v-2a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v2',
        'Vault'=>'M5 10h14v11H5zm3 0V7a4 4 0 0 1 8 0v3m-4 4v3',
        'Organizers'=>'M3 7h18v14H3zm2-4h14v4m-10 5h6',
        'Music'=>'M9 18V5l11-2v13m-11 2a3 3 0 1 1-3-3c2 0 3 1 3 3zm11-2a3 3 0 1 1-3-3c2 0 3 1 3 3z',
        'Business'=>'M4 8h16v13H4zm4 0V4h8v4m-12 5h16m-10 0v3h4v-3',
        'Life admin'=>'M5 4h14v17H5zm3 5h8m-8 4h8m-8 4h6',
        'Files'=>'M3 6h7l2 3h9v11H3z',
        'Websites'=>'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zm-9-9h18m-9-9c-4 4-4 14 0 18m0-18c4 4 4 14 0 18',
        'Settings'=>'M4 6h16M4 12h16M4 18h16m-12-3v6m8-12v6M8 3v6',
        'Search'=>'M10 17a7 7 0 1 0 0-14 7 7 0 0 0 0 14zm5-2 6 6',
        'Plus'=>'M12 4v16M4 12h16',
        'Arrow'=>'M4 12h15m-5-5 5 5-5 5',
        'Check'=>'m4 12 5 5L20 6',
        'Clock'=>'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zm0-14v5l3 2',
        'Star'=>'m12 3 3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z',
        'Layers'=>'m12 3 9 5-9 5-9-5zm-9 9 9 5 9-5M3 16l9 5 9-5',
    ];

    public static function h(mixed $text): string
    {
        return htmlspecialchars((string)$text,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');
    }

    private static function th(string $key, array $params = []): string
    {
        
        return self::h(ltText($key,$params));
    }

    public static function icon(string $name): string
    {
        return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"><path d="' .
            self::ICONS[$name] . '"/></svg>';
    }

    private static function href(array $params): string
    {
        return self::h(Web::url($params));
    }

    public static function start(string $title,string $theme = 'system'): void
    {
        $pack = ltLanguagePack();
        $browserLanguage = [
            'messages'=>array_intersect_key($pack['messages'] + LT_MESSAGES,array_flip(LT_BROWSER_KEYS)),
            'options'=>array_intersect_key($pack['options'] + LT_OPTIONS,array_flip(LT_BROWSER_OPTIONS)),
        ];
        echo '<!doctype html><html lang="'.self::h(ltLanguage()).'" data-theme="'.self::h($theme).'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">' .
            '<title>'.self::th('page_title',['title'=>$title]).'</title><link rel="stylesheet" href="assets/style.css?v=star-buttons">'.
            '<script type="application/json" id="lifetools-language">'.
            json_encode($browserLanguage,JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE).
            '</script><script src="assets/app.js?v=record-autosave" defer></script></head><body>';
    }

    public static function error(string $message): void
    {
        self::start(ltText('request_unavailable'));
        echo '<main class="standalone"><section class="panel auth"><p class="eyebrow">'.self::th('brand_name').'</p><h1>'.self::th('request_unavailable').'</h1><p role="alert">'.self::h($message).
            '</p><a class="button" href="index.php">'.self::th('return_to_lifetools').'</a>'.
            (Web::token() !== '' ? self::languageSwitch() : '').'</section></main></body></html>';
    }

    private static function csrf(): string
    {
        return '<input type="hidden" name="csrf" value="'.self::h(Web::token()).'">';
    }

    private static function languageSwitch(): string
    {
        $language = ltLanguage() === 'en' ? 'es' : 'en';
        $params = array_intersect_key($_GET,array_flip(['view','kind','id','date','mode','trash','q','p']));
        return '<form class="language-switch" method="post" action="'.self::href($params).'" aria-label="'.self::th('language').'">'.
            self::csrf().self::hidden('action','language').
            '<button type="submit" name="language" value="'.$language.'" lang="'.$language.'">'.self::h(LT_LANGUAGES[$language]).'</button></form>';
    }

    private static function hidden(string $name,mixed $value): string
    {
        return '<input type="hidden" name="'.self::h($name).'" value="'.self::h($value).'">';
    }

    private static function input(string $name,string $label,mixed $value = '',string $type = 'text',string $attributes = ''): string
    {
        return '<label class="field"><span>'.self::h($label).'</span><input name="'.self::h($name).'" type="'.self::h($type).
            '" value="'.self::h($value).'" '.$attributes.'></label>';
    }

    private static function area(string $name,string $label,string $value = '',string $attributes = ''): string
    {
        return '<label class="field wide"><span>'.self::h($label).'</span><textarea name="'.self::h($name).'" rows="4" '.$attributes.'>'.
            self::h($value).'</textarea></label>';
    }

    private static function select(string $name,string $label,array $options,mixed $value = '',bool $empty = true,bool $multi = false,bool $stars = false): string
    {
        $output = '<label class="field"><span>'.self::h($label).'</span><select name="'.self::h($name).($multi ? '[]" multiple size="4"' : '"').'>';
        if ($empty) {
            $output .= '<option value="">'.self::th('select').'</option>';
        }
        foreach ($options as $key=>$text) {
            $selected = $multi ? in_array((string)$key,(array)$value,true) : (string)$key === (string)$value;
            $output .= '<option value="'.self::h($key).'"'.($selected ? ' selected' : '').'>'.self::h($text).'</option>';
        }
        $output .= '</select></label>';
        return $stars ? '<div data-star-rating>'.$output.'</div>' : $output;
    }

    private static function checkbox(string $name,string $label,bool $checked = false): string
    {
        return '<label class="check"><input name="'.self::h($name).'" type="checkbox" value="1"'.($checked ? ' checked' : '').'>'.self::h($label).'</label>';
    }

    private static function options(array $values, ?array $defaults = null): array
    {
        $labels = [];
        foreach ($values as $value) {
            $labels[$value] = $defaults === null || in_array($value,$defaults,true) ? ltOption($value) : $value;
        }
        return $labels;
    }

    private static function timeZones(string $zone): string
    {
        return self::select('zone',ltText('time_zone'),self::options(DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC)),$zone,false);
    }

    public static function login(array $config,?string $error): void
    {
        $theme = $_COOKIE['lifetools_theme'] ?? $config['theme'];
        self::start(ltText('sign_in'),in_array($theme,['system','light','dark'],true) ? $theme : $config['theme']);
        echo '<main class="standalone"><section class="panel auth"><div class="brand"><span class="brand-icon">'.self::icon('Layers').'</span><strong>'.
            self::h($config['name']).'</strong></div><p class="eyebrow">'.self::th('private_workspace').'</p><h1>'.self::th('welcome_back').'</h1><p>'.self::th('sign_in_to_your_personal_planner').'</p>';
        if ($error) {
            echo '<p class="banner danger" role="alert">'.self::h($error).'</p>';
        }
        echo '<form method="post" action="index.php" class="stack">'.self::csrf().self::hidden('action','login').
            self::input('login',ltText('login'),'','text','required autocomplete="username" autofocus').
            self::input('password',ltText('password'),'','password','required autocomplete="current-password"').
            '<button class="primary">'.self::th('sign_in').' '.self::icon('Arrow').'</button></form>'.
            self::languageSwitch().'</section></main></body></html>';
    }

    public static function setup(array $result,?string $error): void
    {
        self::start(ltText('set_up_lifetools'));
        echo '<main class="standalone"><section class="panel installer"><div class="brand"><span class="brand-icon">'.self::icon('Layers').
            '</span><strong>'.self::th('brand_name').'</strong></div><p class="eyebrow">'.self::th('first_run',['status'=>ltOption($result['status'])]).
            '</p><h1>'.self::th('make_it_yours').'</h1><p>'.self::th('set_up_a_private_planner_on').'</p>';
        if ($error) {
            echo '<p class="banner danger" role="alert">'.self::h($error).'</p>';
        }
        echo '<form method="post" action="index.php" id="setup-form" class="stack" autocomplete="off">'.self::csrf().self::hidden('action','install').
            '<div class="progress" aria-hidden="true"><span></span></div><p id="setup-progress" class="eyebrow" aria-live="polite"></p>';
        $steps = [
            [ltText('welcome'),'<p>'.self::th('lifetools_creates_a_protected_configuration_a').'</p>'],
            [ltText('storage_mode'),self::select('mode',ltText('primary_and_backup'),[
                'sqlite'=>ltText('sqlite_only_recommended'),'dual'=>ltText('sqlite_with_mysql_backup')],'sqlite',false)],
            [ltText('data_location'),self::input('path',ltText('sqlite_file'),'./data/lifetools.sqlite','text','required').
                '<p>'.self::th('use_the_protected_data_directory_or').'</p>'],
            [ltText('mysql_backup'), '<div id="mysql-options" class="form-grid">'.
                self::input('mysql_host',ltText('host'),'127.0.0.1').self::input('mysql_port',ltText('port'),3306,'number','min="1" max="65535"').
                self::input('mysql_database',ltText('database'),'lifetools').self::input('mysql_user',ltText('user'),'root').
                self::input('mysql_password',ltText('mysql_password'),'','password','autocomplete="new-password"').
                '<button type="button" id="test-mysql">'.self::th('test_connection').'</button><p role="status" id="mysql-result">'.self::th('you_can_continue_while_mysql_is').'</p></div>'.
                '<p id="mysql-skipped">'.self::th('sqlite_only_installs_use_local_storage').'</p>'],
            [ltText('encryption_key'), self::input('key',ltText('32_byte_base64_key'),'','password','required autocomplete="new-password"').
                '<div class="actions"><button type="button" id="generate-key">'.self::th('generate_key').'</button><button type="button" id="copy-key" hidden>'.self::th('copy_key').'</button></div>'.
                '<output id="setup-key" class="break"></output><p>'.self::th('keep_a_separate_offline_copy_this').'</p>'.
                self::checkbox('key_saved',ltText('i_have_stored_this_key'))],
            [ltText('administrator'),self::input('login',ltText('login'),'','text','required minlength="3" maxlength="80" autocomplete="username"').
                self::input('password',ltText('app_password'),'','password','required minlength="12" maxlength="72" autocomplete="new-password"').
                self::input('confirmation',ltText('confirm_password'),'','password','required minlength="12" maxlength="72" autocomplete="new-password"').
                '<output id="password-strength" aria-live="polite">'.self::th('use_at_least_12_characters').'</output>'],
            [ltText('preferences'),'<div class="form-grid">'.self::input('name',ltText('application_name'),ltText('brand_name'),'text','required maxlength="80"').
                self::timeZones('UTC').self::input('currency',ltText('currency'),'USD','text','required pattern="[A-Z]{3}"').
                self::select('format',ltText('date_format'),['Y-m-d'=>ltText('year_month_day'),'m/d/Y'=>ltText('month_day_year'),'d/m/Y'=>ltText('day_month_year')],'Y-m-d',false).
                self::select('theme',ltText('appearance'),self::options(['system','light','dark']),'system',false).'</div>'],
            [ltText('content'),self::checkbox('seed',ltText('load_fictional_demo_content'),true).
                '<p>'.self::th('demo_data_uses_fictional_people_and').'</p>'],
            [ltText('review'),'<dl id="setup-summary" class="facts"></dl><p>'.self::th('keep_your_encryption_key_separate_from').'</p>'],
        ];
        foreach ($steps as $index=>[$title,$content]) {
            echo '<section class="setup-step stack" data-title="'.self::h($title).'"><h2>'.($index+1).'. '.self::h($title).'</h2>'.$content.'</section>';
        }
        echo '<p id="setup-feedback" role="alert"></p><div class="actions"><button type="button" id="setup-back" hidden>'.self::th('back').'</button>'.
            '<button type="button" class="primary" id="setup-next" hidden>'.self::th('continue').' '.self::icon('Arrow').'</button><button class="primary" id="setup-submit">'.self::th('apply_and_lock').'</button>'.
            '<button type="button" class="quiet" id="setup-cancel" hidden>'.self::th('cancel').'</button></div></form>'.
            self::languageSwitch().'</section></main></body></html>';
    }

    public static function shellStart(Store $store,array $user,array $prefs,string $page,string $kind,string $notice): void
    {
        $name = $store->config['name'];
        self::start($name,$prefs['theme']);
        echo '<div class="layout" data-mode="'.self::h($store->config['mode']).'"><aside class="sidebar"><a class="brand" href="index.php"><span class="brand-icon">'.
            self::icon('Layers').'</span><span><strong>'.self::h($name).'</strong><small>'.self::th('personal_planner').'</small></span></a>'.
            '<details class="nav-wrap" open><summary>'.self::th('workspace_navigation').'</summary><nav aria-label="'.self::th('workspace_2').'"><p class="nav-caption">'.self::th('workspace').'</p>';
        $navigation = ['Dashboard'=>['view'=>'dashboard'],'Timeline'=>['view'=>'module','kind'=>'events'],'Calendar'=>['view'=>'calendar']];
        foreach (Catalog::MODULES as $group=>$kinds) {
            if ($group !== 'Timeline') {
                $navigation[$group] = ['view'=>'module','kind'=>$kinds[0]];
            }
        }
        $navigation['Settings'] = ['view'=>'settings'];
        foreach ($navigation as $label=>$params) {
            $active = isset($params['kind'])
                ? (in_array($kind,Catalog::MODULES[$label],true) && in_array($page,['module','record'],true))
                : $page === $params['view'];
            echo '<a href="'.self::href($params).'"'.($active ? ' class="active" aria-current="page"' : '').'>'.self::icon($label).'<span>'.self::h(ltOption($label)).'</span></a>';
        }
        echo '</nav></details><div class="sidebar-footer"><span class="online-dot"></span> '.self::th('local_workspace').'</div></aside><div class="workspace">'.
            '<header class="topbar"><form class="searchbar" action="index.php" method="get">'.self::hidden('view','search').
            '<label>'.self::icon('Search').'<input name="q" type="search" aria-label="'.self::th('search_workspace').'" placeholder="'.self::th('search_workspace_2').'"></label></form>'.
            '<div class="header-actions"><form method="post" action="'.self::href(['view'=>'settings']).'" data-theme-form>'.
            self::csrf().self::hidden('action','settings').self::hidden('operation','theme').
            '<label class="theme-select"><span class="sr-only">'.self::th('appearance').'</span><select name="theme" aria-label="'.self::th('appearance').'" title="'.self::th('appearance').'">';
        foreach (['system'=>ltText('system'),'light'=>ltText('light'),'dark'=>ltText('dark')] as $key=>$label) {
            echo '<option value="'.$key.'"'.($prefs['theme']===$key ? ' selected' : ''). '>'.self::h($label).'</option>';
        }
        echo '</select></label><button class="no-js">'.self::th('apply').'</button></form><button type="button" class="primary quick-button" data-open="quick-add">'.
            self::icon('Plus').'<span>'.self::th('quick_add').'</span></button><details class="account-menu"><summary aria-label="'.self::th('account_menu').'">'.
            self::h(strtoupper(substr($user['title'],0,2))).'</summary><div class="panel account-pop"><strong>'.self::h($user['title']).'</strong>'.
            '<a href="'.self::href(['view'=>'settings']).'">'.self::th('settings').'</a>'.self::languageSwitch().'<form method="post" action="index.php">'.self::csrf().
            self::hidden('action','logout').'<button>'.self::th('sign_out').'</button></form></div></details></div></header><main id="main" class="content">';
        $status = (new Mirror($store))->status();
        echo '<p id="mirror-banner" class="banner" role="status"'.($status['mode'] !== 'dual' || $status['mysql'] === 'reachable' ? ' hidden' : '').
            '>'.self::th('mysql_backup_is_awaiting_sync_your').'</p>';
        if ($notice !== '') {
            echo '<p class="banner success" role="status">'.self::h($notice).'</p>';
        }
    }

    public static function shellEnd(array $prefs): void
    {
        echo '</main></div></div><dialog id="quick-add"><div class="dialog-head"><h2>'.self::th('quick_add').'</h2><button type="button" data-close aria-label="'.self::th('close').'">×</button></div>'.
            self::select('quick_kind',ltText('record_type'),['events'=>ltText('event'),'contacts'=>ltText('contact'),'notes'=>ltText('note'),'list_items'=>ltText('list_item'),'journal_entries'=>ltText('journal_entry')],'events',false).
            '<div id="quick-body" class="stack"></div></dialog><dialog id="reveal"><div class="dialog-head"><h2>'.self::th('reveal_protected_value').'</h2>'.
            '<button type="button" data-close aria-label="'.self::th('close').'">×</button></div><form id="reveal-form" class="stack">'.self::csrf().
            self::hidden('action','reveal').self::hidden('kind','').self::hidden('id','').self::hidden('field','').
            self::input('password',ltText('application_password'),'','password','required autocomplete="current-password"').
            '<button class="primary">'.self::th('reveal_for_30_seconds').'</button><output aria-live="polite" id="reveal-status"></output></form>'.
            '<div id="reveal-value" hidden><p class="secret-output break" id="secret-output"></p><div class="actions"><button type="button" id="copy-secret">'.self::th('copy').'</button>'.
            '<button type="button" data-close>'.self::th('hide').'</button></div></div></dialog></body></html>';
    }

    private static function heading(string $eyebrow,string $title,string $sub = '',string $actions = ''): void
    {
        echo '<div class="heading"><div><p class="eyebrow">'.self::h($eyebrow).'</p><h1>'.self::h($title).'</h1>'.
            ($sub === '' ? '' : '<p>'.self::h($sub).'</p>').'</div><div class="actions">'.$actions.'</div></div>';
    }

    private static function button(string $text,array $params,bool $primary = false): string
    {
        return '<a class="button'.($primary ? ' primary' : '').'" href="'.self::href($params).'">'.self::h($text).'</a>';
    }

    private static function today(array $prefs): DateTimeImmutable
    {
        return new DateTimeImmutable('today',new DateTimeZone($prefs['zone']));
    }

    public static function dashboard(Store $store,string $owner,array $prefs): void
    {
        $today = self::today($prefs);
        $date = $today->format('Y-m-d');
        $next = $today->modify('+7 days')->format('Y-m-d');
        $items = Planner::range($store,$owner,$today->modify('-1 day')->format('Y-m-d'),$today->modify('+31 days')->format('Y-m-d'),$prefs['zone']);
        $todayItems = $upcoming = [];
        $due = Planner::due($items,new DateTimeImmutable('now',new DateTimeZone($prefs['zone'])));
        foreach ($items as $event) {
            $start = $event['start']->format('Y-m-d');
            if ($start <= $date && $event['end']->format('Y-m-d') >= $date) {
                $todayItems[] = $event;
            } elseif ($start > $date && $start <= $next) {
                $upcoming[] = $event;
            }
        }
        $birthdays = [];
        foreach ($store->run("SELECT id,title,json_extract(payload,'$.dob') AS dob FROM records
            WHERE owner=? AND kind='contacts' AND deleted_at IS NULL AND json_extract(payload,'$.dob')<>''",[$owner]) as $contact) {
            $when = Web::birthday($contact['dob'],$today);
            if ($when <= $today->modify('+30 days')) {
                $birthdays[] = ['id'=>$contact['id'],'title'=>$contact['title'],'meta'=>ltDate($when,'short_date'),'date'=>$when->format('Y-m-d')];
            }
        }
        usort($birthdays,static fn($a,$b)=>$a['date'] <=> $b['date']);
        $chores = self::dashboardRows($store,$owner,'chores',"AND json_extract(payload,'$.done')=0",'due');
        $stock = self::dashboardRows($store,$owner,'business_inventory',"AND json_extract(payload,'$.quantity')<=json_extract(payload,'$.threshold')",'quantity');
        $journal = self::dashboardRows($store,$owner,'journal_entries','','');
        $status = (new Mirror($store))->status();
        self::heading(ltText('your_overview'),ltText('dashboard'),ltDate($today,'full_date'),self::button(ltText('open_calendar'),['view'=>'calendar']));
        echo '<section class="hero"><div><p class="eyebrow">'.self::th('the_personal_planner').'</p><h2>'.self::th('make_room_for_what_matters').'</h2>'.
            '<p>'.self::th('plan_whats_ahead_find_what_youve').'</p>'.
            '<a class="button hero-action" href="'.self::href(['view'=>'module','kind'=>'events']).'">'.self::th('explore_timeline').' '.self::icon('Arrow').'</a></div>'.
            '<div class="hero-art" aria-hidden="true"><i></i><i></i><i></i><span>'.self::icon('Layers').'</span></div></section>';
        echo '<div class="stats">';
        foreach ([
            ['Calendar',ltText('today'),count($todayItems),ltText('scheduled_events')],
            ['Timeline',ltText('next_7_days'),count($upcoming),ltText('upcoming_events')],
            ['Clock',ltText('due_reminders'),count($due),ltText('ready_for_review')],
            ['Star',ltText('birthdays'),count($birthdays),ltText('within_30_days')],
        ] as [$icon,$label,$total,$caption]) {
            echo '<article class="panel stat"><div>'.self::icon($icon).'<span>'.self::h($label).'</span></div><strong'.
                ($icon === 'Clock' ? ' id="due-count"' : '').'>'.self::h($total).
                '</strong><small>'.self::h($caption).'</small></article>';
        }
        echo '</div><div class="dashboard-grid">';
        self::agendaCard(ltText('todays_agenda'),$todayItems,ltText('nothing_scheduled_today_add_an_event'));
        self::agendaCard(ltText('next_7_days'),$upcoming,ltText('your_upcoming_plans_will_appear_here'));
        self::agendaCard(ltText('due_reminders'),$due,ltText('youre_caught_up_on_reminders'),true);
        self::miniCard(ltText('upcoming_birthdays'),'contacts',$birthdays,ltText('add_a_birthday_to_a_contact'));
        self::miniCard(ltText('open_chores'),'chores',$chores,ltText('add_a_chore_to_start_your'));
        self::miniCard(ltText('low_stock'),'business_inventory',$stock,ltText('no_items_have_reached_their_low'));
        self::miniCard(ltText('recent_journal_entries'),'journal_entries',$journal,ltText('write_your_first_journal_entry'));
        echo '<section class="panel widget"><div class="widget-head"><h2>'.self::th('storage_sync').'</h2><span class="pill success"><span class="online-dot"></span> '.self::th('sqlite_primary').'</span></div>'.
            '<dl class="facts" id="sync-facts"><dt>'.self::th('mode').'</dt><dd>'.self::h(ltOption($status['mode'])).'</dd><dt>'.self::th('mysql').'</dt><dd data-health="mysql">'.self::h(ltOption($status['mysql'])).
            '</dd><dt>'.self::th('pending').'</dt><dd data-health="queueDepth">'.self::h($status['queueDepth']).'</dd><dt>'.self::th('last_sync').'</dt><dd data-health="lastSync">'.
            self::h($status['lastSync'] ?? '—').'</dd><dt>'.self::th('last_error').'</dt><dd data-health="lastError">'.self::h($status['lastError'] ?? '—').
            '</dd></dl></section></div>';
    }

    private static function dashboardRows(Store $store,string $owner,string $kind,string $where,string $meta): array
    {
        $rows = $store->run("SELECT id,title,payload FROM records WHERE owner=? AND kind=? AND deleted_at IS NULL $where ORDER BY updated_at DESC LIMIT 5",[$owner,$kind])->fetchAll();
        return array_map(static fn($row)=>['id'=>$row['id'],'title'=>$row['title'],'meta'=>Store::payload($row)[$meta] ?? ''],$rows);
    }

    private static function miniCard(string $title,string $kind,array $items,string $empty): void
    {
        echo '<section class="panel widget"><div class="widget-head"><h2>'.self::h($title).'</h2><a href="'.self::href(['view'=>'module','kind'=>$kind]).
            '" aria-label="'.self::th('open_title',['title'=>$title]).'">'.self::icon('Arrow').'</a></div>';
        if (!$items) {
            echo '<p class="empty-inline">'.self::h($empty).'</p>';
        }
        foreach (array_slice($items,0,5) as $item) {
            echo '<a class="mini-row" href="'.self::href(['view'=>'record','kind'=>$kind,'id'=>$item['id']]).'"><span>'.self::h($item['title']).
                '</span><small>'.self::h($item['meta']).'</small></a>';
        }
        echo '</section>';
    }

    private static function agendaCard(string $title,array $items,string $empty,bool $due = false): void
    {
        echo '<section class="panel widget"><div class="widget-head"><h2>'.self::h($title).'</h2><a href="'.self::href(['view'=>'calendar']).
            '" aria-label="'.self::th('open_calendar').'">'.self::icon('Arrow').'</a></div><div'.($due ? ' id="due-list" aria-live="polite"' : '').'>';
        if (!$items) {
            echo '<p class="empty-inline">'.self::h($empty).'</p>';
        }
        foreach (array_slice($items,0,8) as $event) {
            self::eventLink($event,true);
        }
        echo '</div></section>';
    }

    private static function eventLink(array $event,bool $detail = false): void
    {
        echo '<a class="event-chip'.($event['status'] === 'cancelled' ? ' cancelled' : '').'" href="'.
            self::href(['view'=>'record','kind'=>'events','id'=>$event['id']]).'"><span>'.self::h($event['title']).'</span>'.
            ($detail ? '<small>'.self::h(ltDate($event['start'],$event['allDay'] ? 'short_date_all_day' : 'short_date_time')).'</small>' : '').'</a>';
    }

    public static function calendar(Store $store,string $owner,array $prefs): void
    {
        $today = self::today($prefs)->format('Y-m-d');
        $anchor = Planner::date(Catalog::text($_GET['date'] ?? $today,10));
        $mode = $_GET['mode'] ?? 'month';
        if (!in_array($mode,['month','week','agenda'],true)) {
            $mode = 'month';
        }
        $gridStart = $mode === 'month' ? $anchor->modify('first day of this month')->modify('monday this week')
            : ($mode === 'week' ? $anchor->modify('monday this week') : $anchor);
        $gridEnd = $mode === 'month' ? $anchor->modify('last day of this month')->modify('sunday this week')
            : $gridStart->modify($mode === 'week' ? '+6 days' : '+30 days');
        $items = Planner::range($store,$owner,$gridStart->format('Y-m-d'),$gridEnd->format('Y-m-d'),$prefs['zone']);
        $step = $mode === 'month' ? '1 month' : ($mode === 'week' ? '7 days' : '30 days');
        $base = $mode === 'month' ? $anchor->modify('first day of this month') : $anchor;
        $params = ['view'=>'calendar','mode'=>$mode];
        self::heading(ltText('planner'),ltText('calendar'),ltDate($anchor,'month_year'),self::button(ltText('add_event'),['view'=>'record','kind'=>'events','date'=>$anchor->format('Y-m-d')],true));
        echo '<div class="calendar-controls"><div class="actions">'.
            self::button(ltText('previous'),$params+['date'=>$base->modify('-'.$step)->format('Y-m-d')]).
            self::button(ltText('today'),$params+['date'=>$today]).
            self::button(ltText('next'),$params+['date'=>$base->modify('+'.$step)->format('Y-m-d')]).'</div><nav class="tabs" aria-label="'.self::th('calendar_view').'">';
        foreach (['month'=>ltText('month'),'week'=>ltText('week'),'agenda'=>ltText('agenda')] as $key=>$label) {
            echo '<a class="'.($key === $mode ? 'selected' : '').'" href="'.self::href(['view'=>'calendar','mode'=>$key,'date'=>$anchor->format('Y-m-d')]).'">'.self::h($label).'</a>';
        }
        echo '</nav></div>';
        if ($mode === 'agenda') {
            self::agendaCard(ltText('upcoming_agenda'),$items,ltText('no_events_in_this_range'));
            return;
        }
        $byDate = [];
        foreach ($items as $event) {
            $first = max($gridStart,Planner::date($event['start']->format('Y-m-d')));
            $last = min($gridEnd,Planner::date($event['end']->format('Y-m-d')));
            for ($day=$first;$day<=$last;$day=$day->modify('+1 day')) {
                $byDate[$day->format('Y-m-d')][] = $event;
            }
        }
        echo '<section class="panel calendar-grid" aria-label="'.self::th('calendar_days').'">';
        foreach ([ltText('mon'),ltText('tue'),ltText('wed'),ltText('thu'),ltText('fri'),ltText('sat'),ltText('sun')] as $day) {
            echo '<div class="weekday">'.self::h($day).'</div>';
        }
        for ($day=$gridStart;$day<=$gridEnd;$day=$day->modify('+1 day')) {
            $key = $day->format('Y-m-d');
            echo '<div class="calendar-day'.($key === $today ? ' current' : '').($day->format('m') !== $anchor->format('m') ? ' outside' : '').
                '"><a class="day-number" aria-label="'.self::th('add_event_on_date',['date'=>$key]).'" href="'.self::href(['view'=>'record','kind'=>'events','date'=>$key]).
                '">'.self::h($day->format('j')).'</a>';
            foreach ($byDate[$key] ?? [] as $event) {
                self::eventLink($event);
            }
            echo '</div>';
        }
        echo '</section>';
    }


    private static function group(string $kind): string
    {
        foreach (Catalog::MODULES as $group=>$kinds) {
            if (in_array($kind,$kinds,true)) {
                return $group;
            }
        }
        throw new Problem(ltText('unknown_module'));
    }

    private static function tabs(string $group,string $kind): void
    {
        if (count(Catalog::MODULES[$group]) < 2) {
            return;
        }
        echo '<nav class="tabs module-tabs" aria-label="'.self::th('modules').'">';
        foreach (Catalog::MODULES[$group] as $module) {
            if ($module === 'delivery_shifts') {
                continue;
            }
            $selected = $module === ($kind === 'delivery_shifts' ? 'income_entries' : $kind);
            echo '<a'.($selected ? ' class="selected" aria-current="page"' : '').' href="'.
                self::href(['view'=>'module','kind'=>$module]).'">'.self::h(Catalog::label($module)).'</a>';
        }
        if ($group === 'Vault') {
            echo '<a href="'.self::href(['view'=>'banks']).'">'.self::th('compare_banks').'</a>';
        }
        echo '</nav>';
    }

    private static function localKey(string $value,array $prefs): string
    {
        return strlen($value) <= 10 ? $value : (new DateTimeImmutable($value))->setTimezone(new DateTimeZone($prefs['zone']))->format('Y-m-d\TH:i');
    }

    private static function dateLabel(string $value,array $prefs): string
    {
        if ($value === '') {
            return '';
        }
        if (strlen($value) === 10) {
            return Planner::date($value)->format($prefs['format']);
        }
        return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone($prefs['zone']))->format($prefs['format'].' H:i');
    }

    private static function linkOptions(Store $store,string $owner,array $kinds,array $selected = []): array
    {
        $slots = implode(',',array_fill(0,count($kinds),'?'));
        $labels = [];
        foreach ($store->run("SELECT id,kind,title FROM records WHERE owner=? AND deleted_at IS NULL AND kind IN ($slots)
            ORDER BY title LIMIT 1000",[$owner,...$kinds]) as $row) {
            $labels[$row['id']] = count($kinds) > 1 ? $row['title'].'Line:478 · '.Catalog::label($row['kind']) : $row['title'];
        }
        foreach ($selected as $id) {
            if (!isset($labels[$id])) {
                try {
                    $row = $store->record($owner,$id);
                    if (in_array($row['kind'],$kinds,true)) {
                        $labels[$id] = $row['title'];
                    }
                } catch (Problem) {
                    // A deleted target remains in history.
                }
            }
        }
        return $labels;
    }

    private static function highlight(string $text,string $query): string
    {
        if ($query === '') {
            return self::h($text);
        }
        $pieces = preg_split('/('.preg_quote($query,'/').')/iu',$text,-1,PREG_SPLIT_DELIM_CAPTURE);
        return !$pieces ? self::h($text) : implode('',array_map(
            static fn($i,$part)=>$i%2 ? '<mark>'.self::h($part).'</mark>' : self::h($part),array_keys($pieces),$pieces));
    }

    public static function listing(Store $store,string $owner,string $kind,array $prefs): void
    {
        $group = self::group($kind);
        $timeline = $kind === 'events';
        $private = in_array($kind,['journal_entries','intimacy_events','daily_logs'],true);
        $trash = ($_GET['trash'] ?? '') === '1';
        $filter = $private ? $_POST : $_GET;
        $showAllIncomeEntries = $kind === 'income_entries' && !$trash && ($_GET['all_entries'] ?? '') === '1';
        $page = max(1,min(10000,(int)($filter['p'] ?? 1)));
        $take = 30;
        $offset = ($page-1)*$take;
        $q = Catalog::text($filter['q'] ?? '',180);
        $linked = Catalog::text($filter['linked'] ?? '',36);
        $rating = (int)($filter['rating'] ?? 0);
        $named = ($filter['sort'] ?? '') === 'name';
        $kindFilter = $kind === 'income_entries' ? "r.kind IN (?,'delivery_shifts')" : 'r.kind=?';
        $where = 'r.owner=? AND '.$kindFilter.' AND r.deleted_at IS '.($trash ? 'NOT NULL' : 'NULL');
        $params = [$owner,$kind];
        if ($kind === 'income_entries' && !$trash && !$showAllIncomeEntries) {
            $weekStart = self::today($prefs)->modify('monday this week');
            $where .= " AND COALESCE(NULLIF(substr(json_extract(r.payload,'$.shift_end'),1,10),''),r.starts)>=?
                AND COALESCE(NULLIF(substr(json_extract(r.payload,'$.shift_end'),1,10),''),r.starts)<?";
            $params[] = $weekStart->format('Y-m-d');
            $params[] = $weekStart->modify('+7 days')->format('Y-m-d');
        }
        if ($q !== '' && !$private) {
            $where .= ' AND instr(r.search,?)>0';
            $params[] = mb_strtolower($q);
        }
        $categoryField = match ($kind) {
            'contacts'=>'relationship','music_tracks'=>'genre','intimacy_events'=>'type','work_orders'=>'status',
            default=>'category',
        };
        $fields = Catalog::fields($kind,$prefs);
        $defaults = Catalog::fields($kind);
        $facets = array_intersect_key($fields,array_flip([$categoryField,'tags',...($kind==='music_tracks' ? ['artist','album'] : [])]));
        foreach ($facets as $field=>$_) {
            $value = Catalog::text($filter[$field] ?? '',150);
            if ($value !== '') {
                $where .= " AND instr(lower(json_extract(r.payload,'$.$field')),lower(?))>0";
                $params[] = $value;
            }
        }
        $favorite = ($filter['favorite'] ?? '') === '1';
        if ($kind === 'music_tracks' && $favorite) {
            $where .= " AND json_extract(r.payload,'$.favorite')=1";
        }
        foreach (['from'=>'>=','to'=>'<'] as $key=>$op) {
            $value = Catalog::text($filter[$key] ?? '',10);
            if ($value !== '') {
                $date = Planner::date($value);
                $where .= " AND r.starts$op?";
                $params[] = ($key === 'to' ? $date->modify('+1 day') : $date)->format('Y-m-d');
            }
        }
        if ($linked !== '') {
            $store->record($owner,$linked);
            $where .= $kind === 'music_tracks'
                ? " AND EXISTS(SELECT 1 FROM links t JOIN links p ON p.source=t.source AND p.field='playlist'
                    JOIN records pi ON pi.id=t.source AND pi.deleted_at IS NULL AND pi.owner=r.owner
                    WHERE t.target=r.id AND t.field='track' AND p.target=?)"
                : ' AND EXISTS(SELECT 1 FROM links WHERE source=r.id AND target=?)';
            $params[] = $linked;
        }
        $order = match (true) {
            $timeline=>'r.starts,r.id',
            in_array($kind,['list_items','playlist_items'],true)=>"CAST(json_extract(r.payload,'$.position') AS INTEGER),r.id",
            $named=>'r.title COLLATE NOCASE,r.id',
            in_array($kind,['income_entries','delivery_shifts'],true)=>"COALESCE(NULLIF(json_extract(r.payload,'$.shift_end'),''),NULLIF(r.starts,'')) ASC NULLS LAST,r.id",
            $private=>'r.starts DESC,r.id',
            default=>'r.updated_at DESC,r.id',
        };
        $sql = "SELECT r.* FROM records r WHERE $where ORDER BY $order";
        if ($private && ($q !== '' || $rating > 0)) {
            $records = [];
            $matched = 0;
            foreach ($store->run($sql,$params) as $row) {
                if ($q !== '' && mb_stripos($row['title'].' '.$store->privateValue($row,'content').' '.$store->privateValue($row,'notes'),$q) === false) {
                    continue;
                }
                if ($kind === 'intimacy_events' && $rating > 0 && (int)$store->privateValue($row,'rating_1') !== $rating) {
                    continue;
                }
                if ($matched++ >= $offset) {
                    $records[] = $row;
                }
                if (count($records)>$take) {
                    break;
                }
            }
        } else {
            $records = $store->run($sql.' LIMIT '.($take+1).' OFFSET '.$offset,$params)->fetchAll();
        }
        $next = count($records)>$take;
        if ($next) {
            array_pop($records);
        }
        $route = ['view'=>'module','kind'=>$kind]+($trash ? ['trash'=>'1'] : []);
        $incomeToggle = '';
        if ($kind === 'income_entries' && !$trash) {
            $toggleParams = $_GET;
            unset($toggleParams['p']);
            if ($showAllIncomeEntries) {
                unset($toggleParams['all_entries']);
            } else {
                $toggleParams['all_entries'] = '1';
            }
            $incomeToggle = self::button(
                ltText($showAllIncomeEntries ? 'show_current_week' : 'show_other_entries'),
                ['view'=>'module','kind'=>$kind]+$toggleParams
            );
        }
        $module = $timeline ? ltText('timeline') : Catalog::label($kind);
        self::heading(mb_strtoupper(ltOption($group)),
            $trash ? ltText('trash_module',['module'=>$module]) : $module,
            $timeline ? ltText('a_chronological_record_of_your_plans') : ltText('records_in_your_private_workspace'),
            self::button($trash ? ltText('back_to_records') : ltText('trash'),['view'=>'module','kind'=>$kind]+($trash ? [] : ['trash'=>'1'])).
            $incomeToggle.
            (!$trash ? self::button(ltText('add_record'),['view'=>'record','kind'=>$kind],true) : '').
            ($kind === 'work_order_deliveries' ? self::button(ltText('open_delivery_income'),['view'=>'module','kind'=>'income_entries']) : ''));
        self::tabs($group,$kind);
        if (in_array($kind,['business_inventory','income_entries','work_orders'],true)) {
            self::businessSummary($store,$owner,$prefs);
        }
        if ($kind === 'income_entries' && !$trash) {
            self::deliverySummary($store,$owner,$prefs);
            self::editor($store,$owner,'delivery_shifts',$prefs,inlineDelivery:true);
        }
        echo '<form class="panel filters" method="'.($private ? 'post' : 'get').'" action="'.self::href($private ? $route : []).'">'.
            ($private ? self::csrf() : self::hidden('view','module').self::hidden('kind',$kind).($trash ? self::hidden('trash','1') : '').
                ($showAllIncomeEntries ? self::hidden('all_entries','1') : '')).
            self::input('q',$private ? ltText('search_private_entries') : ltText('search_this_module'),$q,'search');
        foreach ($facets as $field=>[$type,$label]) {
            echo $type==='select' ? self::select($field,$label,self::options($fields[$field][2],$defaults[$field][2]),$filter[$field] ?? '')
                : self::input($field,$label,$filter[$field] ?? '');
        }
        if ($timeline || $private || $kind==='meal_plans') {
            echo self::input('from',ltText('from'),$filter['from'] ?? '','date').self::input('to',ltText('through'),$filter['to'] ?? '','date');
        }
        $targets = match ($kind) {
            'events'=>['contacts','vehicles','accounts','documents','work_orders'],
            'intimacy_events','journal_entries'=>['contacts'],
            'list_items'=>['lists'],
            'playlist_items','music_tracks'=>['playlists'],
            'work_order_items','work_order_deliveries'=>['work_orders'],
            default=>[],
        };
        if ($targets) {
            echo self::select('linked',ltText('linked_record'),self::linkOptions($store,$owner,$targets,$linked ? [$linked] : []),$linked);
        }
        if ($kind==='contacts') {
            echo self::select('sort',ltText('sort'),['recent'=>ltText('recently_updated'),'name'=>ltText('name_a_z')],$named ? 'name' : 'recent',false);
        }
        if ($kind==='intimacy_events') {
            echo self::select('rating',ltText('overall_rating'),array_combine(range(1,10),range(1,10)),$rating);
        }
        if ($kind==='music_tracks') {
            echo self::checkbox('favorite',ltText('favorites_only'),$favorite);
        }
        echo '<div class="actions"><button>'.self::th('apply_filters').'</button>'.self::button(ltText('clear'),$route);
        if ($private && $page>1) {
            echo '<button name="p" value="'.($page-1).'">'.self::th('previous_page').'</button>';
        }
        if ($private && $next) {
            echo '<button name="p" value="'.($page+1).'">'.self::th('next_page').'</button>';
        }
        echo '</div></form>';
        if (!$records) {
            echo '<div class="panel empty">'.self::icon($group).'<h2>'.self::th('no_records_to_show').'</h2><p>'.
                ($trash ? self::th('deleted_records_appear_here') : self::th('try_different_filters_add_a_record')).
                '</p>'.($trash ? '' : self::button(ltText('add_record'),['view'=>'record','kind'=>$kind],true).' '.self::button(ltText('open_settings'),['view'=>'settings'])).'</div>';
        }
        $month = '';
        $today = self::today($prefs)->format('Y-m-d');
        foreach ($records as $row) {
            $p = Store::payload($row);
            $local = self::localKey($row['starts'],$prefs);
            $newMonth = substr($local,0,7);
            if ($timeline && $newMonth !== '' && $newMonth !== $month) {
                $month = $newMonth;
                echo '<h2 class="month-label">'.self::h(ltDate(new DateTimeImmutable($month.'-01'),'month_year')).'</h2>';
            }
            $labelField = isset($p[$categoryField]) ? $categoryField : 'type';
            $label = $p[$labelField] ?? '';
            if (($fields[$labelField][0] ?? '') === 'select' && in_array($label,$defaults[$labelField][2],true)) {
                $label = ltOption($label);
            }
            $details = [self::dateLabel(!empty($p['shift_end']) ? substr($p['shift_end'],0,10) : $row['starts'],$prefs),
                $label,$p['tags'] ?? ''];
            foreach (['doordash_pay'=>'delivery_pay','customer_tips'=>'delivery_tips'] as $field=>$caption) {
                if (isset($p[$field]['minor'])) {
                    $details[] = ltText($caption).' '.$p[$field]['currency'].' '.Catalog::decimal($p[$field]);
                }
            }
            if (isset($p['doordash_pay']['minor'],$p['customer_tips']['minor']) &&
                $p['doordash_pay']['currency'] === $p['customer_tips']['currency'] &&
                $p['doordash_pay']['scale'] === $p['customer_tips']['scale']) {
                $pay = $p['doordash_pay'];
                $tips = $p['customer_tips'];
                $details[] = ltText('delivery_total').' '.$pay['currency'].' '.
                    Catalog::decimal(['minor'=>$pay['minor'] + $tips['minor'],'scale'=>$pay['scale']]);
            }
            echo '<article class="panel record-row"><div class="record-icon">'.self::icon($group).'</div><div class="row-content"><a class="row-title" href="'.
                self::href(['view'=>'record','kind'=>$row['kind'],'id'=>$row['id']]+($trash ? ['trash'=>'1'] : [])).'">'.self::highlight($row['title'],$q).
                '</a><small>'.self::h(implode(' · ',array_filter($details))).'</small></div>';
            if ($timeline) {
                echo '<span class="pill">'.(substr($local,0,10)<$today ? self::th('past') : self::th('upcoming')).' Line:678 · '.self::h(ltOption($p['status'] ?? '')).'</span>';
            }
            if (!empty($p['last_four'])) {
                echo '<span class="pill">•••• '.self::h($p['last_four']).'</span>';
            }
            if ($kind==='attachments' && !$trash && str_starts_with($p['mime'],'image/')) {
                echo '<img class="thumbnail" src="'.self::href(['file'=>$row['id']]).'" alt="" loading="lazy">';
            }
            if ($kind==='websites' && !$trash && $p['url']) {
                echo '<a href="'.self::h($p['url']).'" target="_blank" rel="noopener noreferrer" class="pill">'.self::th('open_site').'</a>';
            }
            echo '</article>';
        }
        if (!$private) {
            $pagination = $_GET;
            unset($pagination['p']);
            echo '<nav class="actions pager" aria-label="'.self::th('pagination').'">'.
                ($page>1 ? self::button(ltText('previous'),$pagination+['p'=>$page-1]) : '').
                ($next ? self::button(ltText('next'),$pagination+['p'=>$page+1]) : '').'</nav>';
        }
        if (!$private && !$trash) {
            echo '<form class="pager" method="post" action="index.php">'.self::csrf().self::hidden('action','export').self::hidden('kind',$kind).
                '<button>'.self::th('export_module_csv').'</button></form>';
        }
    }


    public static function record(Store $store,string $owner,string $kind,array $prefs): void
    {
        self::group($kind);
        $id = (string)($_GET['id'] ?? '');
        $row = $id === '' ? null : $store->record($owner,$id,$kind,($_GET['trash'] ?? '')==='1');
        $p = $row ? Store::payload($row) : [];
        self::heading(Catalog::label($kind),$row['title'] ?? ltText('new_record'),
            $row ? ltText('updated_date',['date'=>self::dateLabel($row['updated_at'],$prefs)]) : ltText('add_an_entry_to_your_workspace'),
            self::button(ltText('back_to_records'),['view'=>'module','kind'=>$kind === 'delivery_shifts' ? 'income_entries' : $kind]).
            ($kind === 'work_order_deliveries' ? self::button(ltText('open_delivery_income'),['view'=>'module','kind'=>'income_entries']) : ''));
        if ($kind === 'work_order_deliveries' && $id === '') {
            self::deliverySummary($store,$owner,$prefs);
            self::editor($store,$owner,'delivery_shifts',$prefs,inlineDelivery:true);
            echo '<h2>'.self::th('work_order_deliveries').'</h2>';
        }
        if ($row && $row['deleted_at'] !== null) {
            echo '<section class="panel widget"><p>'.self::th('this_record_is_in_the_trash').'</p>'.
                self::actionForm('restore_record',ltText('restore_record'),$row).'</section>';
            return;
        }
        if ($kind === 'intimacy_events') {
            echo '<p class="banner">'.self::th('all_questionnaire_answers_and_ratings_are').'</p>';
        }
        if ($kind==='contacts' && !empty($p['dob'])) {
            $birth = Planner::date($p['dob']);
            $now = Planner::date(self::today($prefs)->format('Y-m-d'));
            if ($birth <= $now) {
                echo '<p class="pill">'.self::th('age_age',['age'=>$birth->diff($now)->y]).'</p>';
            }
        }
        if ($kind==='attachments' && $row) {
            echo '<section class="panel widget">'.self::button(ltText('open_file'),['file'=>$id]).
                (str_starts_with($p['mime'],'image/') ? '<img class="preview" alt="'.self::th('uploaded_image').'" src="'.self::href(['file'=>$id]).'">' : '').
                '<p>'.self::h($p['mime']).' Line:738 · '.self::th('size_bytes',['size'=>$p['size']]).'</p></section>';
        }
        if ($row && in_array($kind,['journal_entries','daily_logs'],true)) {
            $body = $store->privateValue($row,'content');
            if ($body !== '') {
                $safe = self::h($body);
                $safe = preg_replace('/\*\*([^*\r\n]+)\*\*/','<strong>$1</strong>',$safe);
                $safe = preg_replace('/(?<!\*)\*([^*\r\n]+)\*(?!\*)/','<em>$1</em>',$safe);
                echo '<section class="panel widget"><h2>'.self::th('entry_preview').'</h2><p class="journal-text">'.$safe.'</p></section>';
            }
        }
        self::editor($store,$owner,$kind,$prefs,$row);
        if (!$row) {
            return;
        }
        echo '<div class="actions record-actions">';
        if ($kind === 'events') {
            echo self::actionForm('duplicate',ltText('duplicate_event'),$row);
        }
        if (in_array($kind,['vehicle_documents','documents','appointments','work_shifts','chores','contacts'],true)) {
            echo self::actionForm('schedule',$kind==='contacts' ? ltText('schedule_birthday_reminder') : ltText('add_to_planner'),$row);
        }
        echo self::actionForm('delete',ltText('move_to_trash'),$row,true).'</div>';
        if (in_array($kind,['contacts','vehicles','accounts','documents','playlists','lists','work_orders'],true)) {
            $related = $store->run('SELECT DISTINCT r.id,r.kind,r.title,r.starts FROM links l JOIN records r ON r.id=l.source
                WHERE l.target=? AND r.owner=? AND r.deleted_at IS NULL ORDER BY r.starts DESC LIMIT 30',[$id,$owner])->fetchAll();
            echo '<section class="panel widget"><h2>'.self::th('linked_records').'</h2>';
            foreach ($related as $link) {
                echo '<a class="mini-row" href="'.self::href(['view'=>'record','kind'=>$link['kind'],'id'=>$link['id']]).'">'.
                    self::h($link['title']).'<small>'.self::h(Catalog::label($link['kind']).' Line:767 · '.self::dateLabel($link['starts'],$prefs)).'</small></a>';
            }
            if (!$related) {
                echo '<p>'.self::th('no_records_link_here_yet').'</p>';
            }
            echo '</section>';
        }
    }

    private static function actionForm(string $action,string $label,array $row,bool $confirm = false): string
    {
        return '<form method="post" action="index.php" data-record-action'.($confirm ? ' data-confirm="'.self::th('move_this_record_to_the_trash').'"' : '').'>'.
            self::csrf().self::hidden('action',$action).self::hidden('kind',$row['kind']).self::hidden('id',$row['id']).
            self::hidden('revision',$row['revision']).'<button'.($confirm ? ' class="danger-button"' : '').'>'.self::h($label).'</button></form>';
    }

    public static function editor(Store $store,string $owner,string $kind,array $prefs,?array $row = null,bool $quick = false,bool $inlineDelivery = false): void
    {
        $p = $row ? Store::payload($row) : [];
        $fields = Catalog::fields($kind,$prefs);
        $defaults = Catalog::fields($kind);
        $auto = !$quick;
        if ($quick) {
            $keys = match ($kind) {
                'events'=>['category','all_day','start','end','zone'],
                'contacts'=>['first','last','phone','email'],
                'notes'=>['content'],
                'list_items'=>['list','quantity'],
                'journal_entries'=>['time','content','rating'],
                default=>throw new Problem(ltText('quick_add_is_unavailable_for_this')),
            };
            $fields = array_intersect_key($fields,array_flip($keys));
        }
        echo '<form class="'.($quick ? '' : 'panel ').'editor" method="post" action="index.php" enctype="multipart/form-data"'.
            ($quick || $auto ? ' data-editor="'.($quick ? 'quick' : 'auto').'"' : '').'>'.
            self::csrf().self::hidden('action','save').self::hidden('kind',$kind).
            self::hidden('id',$row['id'] ?? '').self::hidden('revision',$row['revision'] ?? '').
            ($inlineDelivery ? self::hidden('return_to_income','1') : '').'<div class="form-grid">';
        if ($inlineDelivery) {
            echo '<h2 class="wide">'.self::th('delivery_shift').'</h2>';
        }
        if (!in_array($kind,['intimacy_events','delivery_shifts'],true)) {
            $label = match ($kind) {
                'contacts'=>ltText('display_name_or_enter_first_and'),'attachments'=>ltText('file_title'),
                'music_tracks'=>ltText('song_title'),'personal_profile'=>ltText('preferred_name'),default=>ltText('title'),
            };
            echo self::input('title',$label,$row['title'] ?? '','text','maxlength="180" '.
                (!in_array($kind,['contacts','income_entries'],true) ? 'required' : ''));
        }
        foreach ($fields as $field=>[$type,$label]) {
            $options = $fields[$field][2] ?? [];
            $value = $p[$field] ?? '';
            if (in_array($kind,['income_entries','delivery_shifts'],true) && in_array($field,['pickup','doordash_pay','customer_tips'],true)) {
                if ($field === 'pickup') {
                    self::deliveryPickups($store,$owner,$prefs,$p);
                }
                continue;
            }
            if ($kind === 'income_entries' && $field === 'shift_start') {
                echo '<h2 class="wide">'.self::th('delivery_rideshare').'</h2>';
            }
            if ($type === 'zone' && $value === '') {
                $value = $prefs['zone'];
            }
            if ($type === 'eventtime' && !$row && $field === 'start') {
                $value = isset($_GET['date']) ? Planner::date((string)$_GET['date'])->format('Y-m-d') : self::today($prefs)->format('Y-m-d');
            }
            if ($type === 'datetime' && $value !== '') {
                $value = self::localKey($value,$prefs);
            }
            if ($kind === 'intimacy_events' && in_array($field,['question_1','rating_1'],true)) {
                echo '<h2 class="wide">'.($field === 'question_1' ? self::th('optional_questionnaire') : self::th('optional_ratings')).'</h2>';
            }
            if ($type === 'secret') {
                $stored = isset($p['_private'][$field]);
                echo '<div class="secret-field">'.self::input($field,$label,'','password','autocomplete="new-password" placeholder="'.
                    ($stored ? '••••••••' : self::th('add_protected_value')).'".').'<div class="actions">';
                if ($stored) {
                    echo '<button type="button" data-reveal data-kind="'.self::h($kind).'" data-id="'.self::h($row['id']).'" data-field="'.
                        self::h($field).'">'.self::th('reveal').'</button>'.self::checkbox('clear_'.$field,ltText('clear_on_save'));
                }
                if ($field === 'password') {
                    echo '<button type="button" data-password-generator>'.self::th('generate_password').'</button>';
                }
                echo '</div></div>';
                continue;
            }
            if (Catalog::privateType($type)) {
                $value = $row ? $store->privateValue($row,$field) : '';
            }
            if ($type === 'link' || $type === 'links') {
                $selected = $p['_links'][$field] ?? [];
                echo self::select($field,$label,self::linkOptions($store,$owner,$options,$selected),
                    $type === 'links' ? $selected : ($selected[0] ?? ''),$type !== 'links',$type === 'links');
                continue;
            }
            echo match ($type) {
                'area','private'=>self::area($field,$label.($field==='content' && in_array($kind,['journal_entries','daily_logs'],true)
                    ? ltText('use_italic_or_bold') : ''),$value,'maxlength="24000"'),
                'select','private-choice'=>self::select($field,$label,self::options($options,$defaults[$field][2]),$value),
                'rating','private-rating'=>self::select($field,$label,array_combine(range(1,10),range(1,10)),$value,
                    stars:$type === 'private-rating'),
                'check'=>self::checkbox($field,$label,$field === 'all_day' && !$row ? true : (bool)$value),
                'money'=>self::input($field,$label.' ('.($value ? $value['currency'] : $prefs['currency']).')',
                    $value ? Catalog::decimal($value) : '','text','inputmode="decimal"'),
                'eventtime'=>self::input($field,$label,$value,($p['all_day'] ?? 1) ? 'date' : 'datetime-local',
                    'data-event-time '.($field === 'start' ? 'required' : '')),
                'zone'=>self::timeZones($value),
                'date','month','email','url'=>self::input($field,$label,$value,$type),
                'datetime'=>self::input($field,$label,$value,'datetime-local'),
                'shift-time'=>self::input($field,$label,$value,'datetime-local',
                    ($kind === 'delivery_shifts' ? 'required ' : '').
                    (($prefs['delivery_clock'] ?? '') === '24' ? 'data-clock-24' : '')),
                'duration'=>self::input($field,$label,self::durationLabel($value),'text',
                    'placeholder="1 hrs 34 mins" pattern="[0-9]+(:[0-5][0-9]| hrs [0-5]?[0-9] mins)?"'),
                'computed'=>self::input($field,$label,self::durationLabel($value),'text','readonly'),
                'suggestion'=>self::suggestion($store,$owner,$field,$label,$value),
                'clock'=>self::input($field,$label,$value,'time'),
                'positive','number'=>self::input($field,$label,$value,'number','min="'.($type==='positive'?1:0).'"'),
                'upload'=>self::input('upload',$label,'','file','accept="image/png,image/jpeg,image/gif,image/webp,application/pdf" '.(!$row ? 'required' : '')),
                default=>self::input($field,$label,$value),
            };
        }
        echo '</div><div class="actions editor-actions"><button class="primary" type="submit">'.self::th('save_record').'</button>'.
            '<output aria-live="polite" class="save-status">'.($auto ? self::th('edits_save_automatically') : '').'</output></div></form>';
    }

    private static function durationLabel(int|string $minutes): string
    {
        return $minutes === '' ? '' : ltText('delivery_duration_label',[
            'hours'=>intdiv((int)$minutes,60),'minutes'=>(int)$minutes % 60,
        ]);
    }

    private static function deliveryPickups(Store $store,string $owner,array $prefs,array $payload): void
    {
        $currency = $payload['doordash_pay']['currency'] ?? $prefs['currency'];
        $pickupRow = static function (array $row) use ($currency): string {
            return '<div class="form-grid" data-pickup-row>'.
                self::input('pickup_locations[]',ltText('delivery_pickup'),$row['pickup'] ?? '','text','list="delivery-pickup-options" maxlength="500"').
                self::input('pickup_pay[]',ltText('delivery_pay').' ('.$currency.')',
                    isset($row['doordash_pay']['minor']) ? Catalog::decimal($row['doordash_pay']) : '','text','inputmode="decimal"').
                self::input('pickup_tips[]',ltText('delivery_tips').' ('.$currency.')',
                    isset($row['customer_tips']['minor']) ? Catalog::decimal($row['customer_tips']) : '','text','inputmode="decimal"').
                '<button type="button" data-remove-pickup>'.self::th('delivery_remove_pickup').'</button></div>';
        };
        $choices = $store->run("WITH shifts AS (
                SELECT payload FROM records WHERE owner=? AND kind IN ('income_entries','delivery_shifts') AND deleted_at IS NULL
            ), locations AS (
                SELECT json_extract(payload,'$.pickup') AS location FROM shifts
                UNION
                SELECT json_extract(item.value,'$.pickup') FROM shifts, json_each(shifts.payload,'$.pickup_rows') item
            )
            SELECT location FROM locations WHERE location <> '' ORDER BY location COLLATE NOCASE LIMIT 100",[$owner]);
        echo '<div class="wide stack" data-delivery-pickups><div class="stack" data-pickup-rows>';
        foreach (($payload['pickup_rows'] ?? []) ?: [$payload] as $row) {
            echo $pickupRow($row);
        }
        echo '</div><datalist id="delivery-pickup-options">';
        foreach ($choices as $choice) {
            echo '<option value="'.self::h($choice['location']).'"></option>';
        }
        echo '</datalist><template>'.$pickupRow([]).'</template>'.
            '<div class="actions"><button type="button" data-add-pickup>+ '.self::th('delivery_add_pickup').'</button></div></div>';
    }

    private static function suggestion(Store $store,string $owner,string $field,string $label,string $value): string
    {
        $id = 'suggestions-'.$field;
        $path = '$.'.$field;
        $choices = $store->run("SELECT DISTINCT json_extract(payload,?) AS choice FROM records
            WHERE owner=? AND kind IN ('income_entries','delivery_shifts') AND deleted_at IS NULL
            AND json_extract(payload,?) <> '' ORDER BY choice COLLATE NOCASE LIMIT 100",[$path,$owner,$path]);
        $output = self::input($field,$label,$value,'text','list="'.self::h($id).'"').'<datalist id="'.self::h($id).'">';
        foreach ($choices as $choice) {
            $output .= '<option value="'.self::h($choice['choice']).'"></option>';
        }
        return $output.'</datalist>';
    }

    private static function deliverySummary(Store $store,string $owner,array $prefs): void
    {
        $monday = self::today($prefs)->modify('monday this week');
        $rows = $store->run("SELECT payload FROM records WHERE owner=? AND kind IN ('income_entries','delivery_shifts')
            AND json_type(payload,'$.doordash_pay') = 'object'
            AND deleted_at IS NULL
            AND COALESCE(NULLIF(substr(json_extract(payload,'$.shift_end'),1,10),''),starts) >= ?
            AND COALESCE(NULLIF(substr(json_extract(payload,'$.shift_end'),1,10),''),starts) < ?",
            [$owner,$monday->format('Y-m-d'),$monday->modify('+7 days')->format('Y-m-d')]);
        $totals = [];
        foreach ($rows as $row) {
            $payload = Store::payload($row);
            $pay = $payload['doordash_pay'];
            $currency = $pay['currency'];
            $totals[$currency] ??= ['scale'=>$pay['scale'],'pay'=>0,'tips'=>0,'count'=>0];
            $totals[$currency]['pay'] += $pay['minor'];
            $totals[$currency]['tips'] += $payload['customer_tips']['minor'];
            $totals[$currency]['count'] += (int)$payload['deliveries'];
        }
        if (!$totals) {
            $currency = $prefs['currency'];
            $totals[$currency] = ['scale'=>Catalog::money('0',$currency)['scale'],'pay'=>0,'tips'=>0,'count'=>0];
        }
        $values = ['delivery_week_pay'=>[],'delivery_tips'=>[],'delivery_total'=>[],'delivery_average'=>[]];
        foreach ($totals as $currency=>$total) {
            $earned = $total['pay'] + $total['tips'];
            $amounts = [
                'delivery_week_pay'=>$total['pay'],'delivery_tips'=>$total['tips'],'delivery_total'=>$earned,
                'delivery_average'=>$total['count'] ? (int)round($earned / $total['count']) : null,
            ];
            foreach ($amounts as $label=>$minor) {
                $values[$label][] = $minor === null ? '—' : $currency.' '.Catalog::decimal(['minor'=>$minor,'scale'=>$total['scale']]);
            }
        }
        echo '<h2>'.self::th('delivery_week').'</h2><p>'.self::h($monday->format($prefs['format']).' – '.
            $monday->modify('+6 days')->format($prefs['format'])).'</p><div class="panel business-summary">';
        foreach ($values as $label=>$amounts) {
            echo '<div><strong>'.self::th($label).'</strong><span>'.self::h(implode(' Line:986 · ',$amounts)).'</span></div>';
        }
        echo '</div>';
    }

    private static function businessSummary(Store $store,string $owner,array $prefs): void
    {
        $month = self::today($prefs)->format('Y-m');
        $totals = $store->run("SELECT json_extract(payload,'$.amount.currency') AS currency,
            SUM(CAST(json_extract(payload,'$.amount.minor') AS INTEGER)) AS minor,
            MAX(CAST(json_extract(payload,'$.amount.scale') AS INTEGER)) AS scale FROM records
            WHERE owner=? AND kind='income_entries' AND deleted_at IS NULL AND starts LIKE ?
            AND json_extract(payload,'$.status')='paid' AND json_extract(payload,'$.amount.currency') IS NOT NULL
            GROUP BY currency",[$owner,$month.'-%'])->fetchAll();
        $open = $store->run("SELECT COUNT(*) FROM records WHERE owner=? AND kind='work_orders' AND deleted_at IS NULL
            AND json_extract(payload,'$.status') IN ('quoted','paid','in progress')",[$owner])->fetchColumn();
        $low = $store->run("SELECT COUNT(*) FROM records WHERE owner=? AND kind='business_inventory' AND deleted_at IS NULL
            AND CAST(json_extract(payload,'$.quantity') AS INTEGER)<=CAST(json_extract(payload,'$.threshold') AS INTEGER)",[$owner])->fetchColumn();
        echo '<div class="panel business-summary"><div><strong>'.self::th('paid_this_month').'</strong><span>'.
            self::h($totals ? implode(' line: 1005 · ',array_map(static fn($a)=>$a['currency'].' '.Catalog::decimal($a),$totals)) : '—').
            '</span></div><div><strong>'.self::th('outstanding_work').'</strong><span>'.self::h($open).
            '</span></div><div><strong>'.self::th('low_stock_items').'</strong><span>'.self::h($low).'</span></div></div>';
    }

    public static function search(Store $store,string $owner): void
    {
        $q = Catalog::text($_GET['q'] ?? '',180);
        $kind = Catalog::text($_GET['kind'] ?? '',40);
        $kinds = array_values(array_diff(array_keys(Catalog::LABELS),[
            'intimacy_events','daily_logs','accounts','bank_accounts','cards','personal_profile','phone_history','email_history',
        ]));
        $choices = in_array($kind,$kinds,true) ? [$kind] : $kinds;
        self::heading(ltText('workspace'),ltText('search'),ltText('find_records_across_your_workspace_search'));
        echo '<form class="panel filters" method="get" action="index.php">'.self::hidden('view','search').
            self::input('q',ltText('search_text'),$q,'search','required maxlength="180"').
            self::select('kind',ltText('module'),array_intersect_key(Catalog::labels(),array_flip($kinds)),$kind).'<button class="primary">'.self::th('search').'</button></form>';
        if ($q === '') {
            return;
        }
        $slots = implode(',',array_fill(0,count($choices),'?'));
        $rows = $store->run("SELECT id,kind,title,search FROM records WHERE owner=? AND deleted_at IS NULL AND kind IN ($slots)
            AND instr(search,?)>0 ORDER BY kind,updated_at DESC LIMIT 100",[$owner,...$choices,mb_strtolower($q)])->fetchAll();
        if (!$rows) {
            echo '<div class="panel empty"><h2>'.self::th('no_matches').'</h2><p>'.self::th('try_a_different_phrase_or_module').'</p></div>';
        }
        $last = '';
        foreach ($rows as $row) {
            if ($row['kind'] !== $last) {
                echo '<h2 class="month-label">'.self::h(Catalog::label($row['kind'])).'</h2>';
                $last = $row['kind'];
            }
            $index = mb_stripos($row['search'],$q);
            echo '<div class="panel record-row"><div class="row-content"><a class="row-title" href="'.
                self::href(['view'=>'record','kind'=>$row['kind'],'id'=>$row['id']]).'">'.self::highlight($row['title'],$q).
                '</a><small>'.self::highlight(mb_substr($row['search'],max(0,(int)$index-24),90),$q).'</small></div></div>';
        }
    }

    public static function banks(Store $store,string $owner): void
    {
        self::heading(ltText('vault'),ltText('bank_comparison'),ltText('see_which_access_fields_are_on'));
        self::tabs('Vault','');
        $groups = [];
        foreach ($store->run("SELECT * FROM records WHERE owner=? AND kind IN ('bank_accounts','cards') AND deleted_at IS NULL",[$owner]) as $row) {
            $groups[Store::payload($row)['institution'] ?: ltText('institution_unspecified')][] = $row;
        }
        ksort($groups,SORT_NATURAL | SORT_FLAG_CASE);
        if (!$groups) {
            echo '<div class="panel empty"><h2>'.self::th('no_banking_records').'</h2>'.self::button(ltText('add_a_bank_record'),['view'=>'record','kind'=>'bank_accounts'],true).'</div>';
        }
        foreach ($groups as $institution=>$rows) {
            echo '<h2 class="month-label">'.self::h($institution).'</h2>';
            foreach ($rows as $row) {
                $p = Store::payload($row);
                $access = array_intersect_key(['username'=>ltText('username'),'password'=>ltText('password'),'pin'=>ltText('pin'),'number'=>ltText('account_number'),'routing'=>ltText('routing_number')],
                    $p['_private'] ?? []);
                echo '<article class="panel record-row"><div class="row-content"><a class="row-title" href="'.
                    self::href(['view'=>'record','kind'=>$row['kind'],'id'=>$row['id']]).'">'.self::h($row['title']).
                    '</a><small>'.self::h(implode(' Line:1064 · ',array_filter([ltOption($p['subtype'] ?? ''),$p['expiry'],implode(', ',$access)]))).'</small></div>'.
                    '<span class="pill">'.self::h(!empty($p['last_four']) ? '•••• '.$p['last_four'] : ltText('no_card_digits')).'</span></article>';
            }
        }
    }

    public static function settings(Store $store,array $user,array $result): void
    {
        $p = Store::payload($user);
        self::heading(ltText('workspace'),ltText('settings'),ltText('preferences_portability_and_installation_tools'));
        echo '<section class="panel widget"><h2>'.self::th('personal_preferences').'</h2><form method="post" action="index.php">'.
            self::csrf().self::hidden('action','settings').self::hidden('operation','preferences').'<div class="form-grid">'.
            self::select('theme',ltText('appearance'),self::options(['system','light','dark']),$p['theme'],false).self::timeZones($p['zone']).
            self::input('currency',ltText('currency'),$p['currency'],'text','required pattern="[A-Z]{3}"').
            self::select('format',ltText('date_format'),self::options(['Y-m-d','m/d/Y','d/m/Y']),$p['format'],false).
            self::select('delivery_clock',ltText('delivery_clock'),[
                'browser'=>ltText('delivery_clock_browser'),'24'=>ltText('delivery_clock_24'),
            ],$p['delivery_clock'] ?? 'browser',false);
        foreach (['relationships'=>ltText('additional_contact_relationships'),'account_types'=>ltText('additional_account_types'),
            'intimacy_types'=>ltText('additional_intimacy_event_types'),'intimacy_questions'=>ltText('additional_private_questionnaire_prompts')] as $key=>$label) {
            echo self::area($key,$label.ltText('one_per_line'),$p[$key] ?? '');
        }
        echo '</div><div class="actions editor-actions"><button class="primary">'.self::th('save_preferences').'</button></div></form></section>'.
            '<section class="panel widget"><h2>'.self::th('data_portability').'</h2><p>'.self::th('general_exports_omit_private_journal_and').'</p><div class="actions">'.
            '<form method="post" action="index.php">'.self::csrf().self::hidden('action','export').'<button>'.self::th('export_json').'</button></form>'.
            '<form method="post" action="index.php">'.self::csrf().self::hidden('action','settings').self::hidden('operation','seed').
            '<button>'.self::th('load_fictional_demo').'</button></form></div></section>';
        if ($p['role'] !== 'admin') {
            return;
        }
        $output = $_SESSION['maintenance'] ?? null;
        unset($_SESSION['maintenance']);
        echo '<section class="panel widget"><h2>'.self::th('installation_backup').'</h2><p>'.self::th('configuration_is_active_restart_apache_after').'</p>'.
            '<details><summary>'.self::th('show_redacted_configuration').'</summary><pre>'.self::h(json_encode(Config::public($result),JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)).'</pre></details>';
        if ($output !== null) {
            echo '<pre role="status">'.self::h(json_encode($output,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)).'</pre>';
        }
        echo '<form class="form-grid" method="post" action="index.php" data-maintenance>'.self::csrf().self::hidden('action','settings').
            self::select('operation',ltText('action'),['verify'=>ltText('verify_backup'),'repair'=>ltText('queue_repair'),'drain'=>ltText('drain_backup_queue'),'migrate'=>ltText('apply_migrations'),
                'backup'=>ltText('create_backup'),'restore'=>ltText('restore_backup'),'reconfigure'=>ltText('reconfiguration_instructions')],'verify',false).
            self::input('backup',ltText('backup_identifier'),'','text','placeholder="YYYYMMDDTHHMMSSZ-abcdef"').
            self::checkbox('from_mirror',ltText('restore_from_the_mysql_dump')).
            self::input('password',ltText('confirm_application_password'),'','password','required autocomplete="current-password"').
            '<div class="actions"><button>'.self::th('run_action').'</button></div></form></section>';
        $mirror = $store->config['mirror'];
        echo '<section class="panel widget"><h2>'.self::th('change_backup_mode').'</h2><p>'.self::th('your_sqlite_database_and_existing_users').'</p>'.
            '<form class="form-grid" method="post" action="index.php">'.self::csrf().self::hidden('action','settings').self::hidden('operation','mirror_config').
            self::select('mode',ltText('storage'),['sqlite'=>ltText('sqlite_only'),'dual'=>ltText('sqlite_with_mysql_backup')],$store->config['mode'],false).
            self::input('mysql_host',ltText('mysql_host'),$mirror['host'] ?? '127.0.0.1').
            self::input('mysql_port',ltText('mysql_port'),$mirror['port'] ?? 3306,'number','min="1" max="65535"').
            self::input('mysql_database',ltText('mysql_database'),$mirror['database'] ?? 'lifetools').
            self::input('mysql_user',ltText('mysql_user'),$mirror['user'] ?? 'lifetools').
            self::input('mysql_password',ltText('new_mysql_password_leave_empty_to'),'','password','autocomplete="new-password"').
            self::checkbox('clear_mysql_password',ltText('use_an_empty_mysql_password')).
            self::input('password',ltText('confirm_application_password'),'','password','required autocomplete="current-password"').
            '<div class="actions"><button>'.self::th('save_backup_mode').'</button></div></form></section>'.
            '<section class="panel widget"><h2>'.self::th('create_an_application_user').'</h2>'.
            '<p>'.self::th('each_user_gets_a_separate_workspace').'</p><form class="form-grid" method="post" action="index.php">'.
            self::csrf().self::hidden('action','settings').self::hidden('operation','create_user').
            self::input('login',ltText('new_login'),'','text','required autocomplete="off"').
            self::select('role',ltText('role'),['standard'=>ltText('standard_user'),'admin'=>ltText('administrator')],'standard',false).
            self::input('new_password',ltText('new_user_password'),'','password','required minlength="12" maxlength="72" autocomplete="new-password"').
            self::input('password',ltText('your_application_password'),'','password','required autocomplete="current-password"').
            '<div class="actions"><button>'.self::th('create_user').'</button></div></form></section>';
    }
}
