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
        echo '<!doctype html><html lang="en" data-theme="'.self::h($theme).'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">' .
            '<title>'.self::h($title).' · LifeTools</title><link rel="stylesheet" href="assets/style.css"><script src="assets/app.js" defer></script></head><body>';
    }

    public static function error(string $message): void
    {
        self::start('Request unavailable');
        echo '<main class="standalone"><section class="panel auth"><p class="eyebrow">LifeTools</p><h1>Request unavailable</h1><p role="alert">'.self::h($message).
            '</p><a class="button" href="index.php">Return to LifeTools</a></section></main></body></html>';
    }

    private static function csrf(): string
    {
        return '<input type="hidden" name="csrf" value="'.self::h(Web::token()).'">';
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

    private static function select(string $name,string $label,array $options,mixed $value = '',bool $empty = true,bool $multi = false): string
    {
        $output = '<label class="field"><span>'.self::h($label).'</span><select name="'.self::h($name).($multi ? '[]" multiple size="4"' : '"').'>';
        if ($empty) {
            $output .= '<option value="">Select…</option>';
        }
        foreach ($options as $key=>$text) {
            $selected = $multi ? in_array((string)$key,(array)$value,true) : (string)$key === (string)$value;
            $output .= '<option value="'.self::h($key).'"'.($selected ? ' selected' : '').'>'.self::h($text).'</option>';
        }
        return $output.'</select></label>';
    }

    private static function checkbox(string $name,string $label,bool $checked = false): string
    {
        return '<label class="check"><input name="'.self::h($name).'" type="checkbox" value="1"'.($checked ? ' checked' : '').'>'.self::h($label).'</label>';
    }

    private static function options(array $values): array
    {
        return array_combine($values,$values);
    }

    private static function timeZones(string $zone): string
    {
        return self::select('zone','Time zone',self::options(DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC)),$zone,false);
    }

    public static function login(array $config,?string $error): void
    {
        $theme = $_COOKIE['lifetools_theme'] ?? $config['theme'];
        self::start('Sign in',in_array($theme,['system','light','dark'],true) ? $theme : $config['theme']);
        echo '<main class="standalone"><section class="panel auth"><div class="brand"><span class="brand-icon">'.self::icon('Layers').'</span><strong>'.
            self::h($config['name']).'</strong></div><p class="eyebrow">PRIVATE WORKSPACE</p><h1>Welcome back.</h1><p>Sign in to your personal planner.</p>';
        if ($error) {
            echo '<p class="banner danger" role="alert">'.self::h($error).'</p>';
        }
        echo '<form method="post" action="index.php" class="stack">'.self::csrf().self::hidden('action','login').
            self::input('login','Login','','text','required autocomplete="username" autofocus').
            self::input('password','Password','','password','required autocomplete="current-password"').
            '<button class="primary">Sign in '.self::icon('Arrow').'</button></form></section></main></body></html>';
    }

    public static function setup(array $result,?string $error): void
    {
        self::start('Set up LifeTools');
        echo '<main class="standalone"><section class="panel installer"><div class="brand"><span class="brand-icon">'.self::icon('Layers').
            '</span><strong>LifeTools</strong></div><p class="eyebrow">FIRST RUN · '.self::h($result['status']).
            '</p><h1>Make it yours.</h1><p>Set up a private planner on this machine.</p>';
        if ($error) {
            echo '<p class="banner danger" role="alert">'.self::h($error).'</p>';
        }
        echo '<form method="post" action="index.php" id="setup-form" class="stack" autocomplete="off">'.self::csrf().self::hidden('action','install').
            '<div class="progress" aria-hidden="true"><span></span></div><p id="setup-progress" class="eyebrow" aria-live="polite"></p>';
        $steps = [
            ['Welcome','<p>LifeTools creates a protected configuration, a local SQLite database and an administrator. You can plan, write and organize offline.</p>'],
            ['Storage mode',self::select('mode','Primary and backup',[
                'sqlite'=>'SQLite only · recommended','dual'=>'SQLite with MySQL backup'],'sqlite',false)],
            ['Data location',self::input('path','SQLite file','./data/lifetools.sqlite','text','required').
                '<p>Use the protected data directory or a writable directory outside the web root.</p>'],
            ['MySQL backup', '<div id="mysql-options" class="form-grid">'.
                self::input('mysql_host','Host','127.0.0.1').self::input('mysql_port','Port',3306,'number','min="1" max="65535"').
                self::input('mysql_database','Database','lifetools').self::input('mysql_user','User','root').
                self::input('mysql_password','MySQL password','','password','autocomplete="new-password"').
                '<button type="button" id="test-mysql">Test connection</button><p role="status" id="mysql-result">You can continue while MySQL is unavailable.</p></div>'.
                '<p id="mysql-skipped">SQLite-only installs use local storage.</p>'],
            ['Encryption key', self::input('key','32-byte base64 key','','password','required autocomplete="new-password"').
                '<div class="actions"><button type="button" id="generate-key">Generate key</button><button type="button" id="copy-key" hidden>Copy key</button></div>'.
                '<output id="setup-key" class="break"></output><p>Keep a separate offline copy. This key unlocks your encrypted data.</p>'.
                self::checkbox('key_saved','I have stored this key')],
            ['Administrator',self::input('login','Login','','text','required minlength="3" maxlength="80" autocomplete="username"').
                self::input('password','App password','','password','required minlength="12" maxlength="72" autocomplete="new-password"').
                self::input('confirmation','Confirm password','','password','required minlength="12" maxlength="72" autocomplete="new-password"').
                '<output id="password-strength" aria-live="polite">Use at least 12 characters.</output>'],
            ['Preferences','<div class="form-grid">'.self::input('name','Application name','LifeTools','text','required maxlength="80"').
                self::timeZones('UTC').self::input('currency','Currency','USD','text','required pattern="[A-Z]{3}"').
                self::select('format','Date format',['Y-m-d'=>'Year-month-day','m/d/Y'=>'Month/day/year','d/m/Y'=>'Day/month/year'],'Y-m-d',false).
                self::select('theme','Appearance',self::options(['system','light','dark']),'system',false).'</div>'],
            ['Content',self::checkbox('seed','Load fictional demo content',true).
                '<p>Demo data uses fictional people and credentials. You can also add the demo later from Settings.</p>'],
            ['Review','<dl id="setup-summary" class="facts"></dl><p>Keep your encryption key separate from backups. Applying setup locks this installer.</p>'],
        ];
        foreach ($steps as $index=>[$title,$content]) {
            echo '<section class="setup-step stack" data-title="'.self::h($title).'"><h2>'.($index+1).'. '.self::h($title).'</h2>'.$content.'</section>';
        }
        echo '<p id="setup-feedback" role="alert"></p><div class="actions"><button type="button" id="setup-back" hidden>Back</button>'.
            '<button type="button" class="primary" id="setup-next" hidden>Continue '.self::icon('Arrow').'</button><button class="primary" id="setup-submit">Apply and lock</button>'.
            '<button type="button" class="quiet" id="setup-cancel" hidden>Cancel</button></div></form></section></main></body></html>';
    }

    public static function shellStart(Store $store,array $user,array $prefs,string $page,string $kind,string $notice): void
    {
        $name = $store->config['name'];
        self::start($name,$prefs['theme']);
        echo '<div class="layout" data-mode="'.self::h($store->config['mode']).'"><aside class="sidebar"><a class="brand" href="index.php"><span class="brand-icon">'.
            self::icon('Layers').'</span><span><strong>'.self::h($name).'</strong><small>PERSONAL PLANNER</small></span></a>'.
            '<details class="nav-wrap" open><summary>Workspace navigation</summary><nav aria-label="Workspace"><p class="nav-caption">WORKSPACE</p>';
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
            echo '<a href="'.self::href($params).'"'.($active ? ' class="active" aria-current="page"' : '').'>'.self::icon($label).'<span>'.self::h($label).'</span></a>';
        }
        echo '</nav></details><div class="sidebar-footer"><span class="online-dot"></span> Local workspace</div></aside><div class="workspace">'.
            '<header class="topbar"><form class="searchbar" action="index.php" method="get">'.self::hidden('view','search').
            '<label>'.self::icon('Search').'<input name="q" type="search" aria-label="Search workspace" placeholder="Search workspace…"></label></form>'.
            '<div class="header-actions"><form method="post" action="'.self::href(['view'=>'settings']).'" data-theme-form>'.
            self::csrf().self::hidden('action','settings').self::hidden('operation','theme').
            '<label class="theme-select"><span class="sr-only">Appearance</span><select name="theme" aria-label="Appearance" title="Appearance">';
        foreach (['system'=>'◐ System','light'=>'☼ Light','dark'=>'☾ Dark'] as $key=>$label) {
            echo '<option value="'.$key.'"'.($prefs['theme']===$key ? ' selected' : '').'>'.$label.'</option>';
        }
        echo '</select></label><button class="no-js">Apply</button></form><button type="button" class="primary quick-button" data-open="quick-add">'.
            self::icon('Plus').'<span>Quick add</span></button><details class="account-menu"><summary aria-label="Account menu">'.
            self::h(strtoupper(substr($user['title'],0,2))).'</summary><div class="panel account-pop"><strong>'.self::h($user['title']).'</strong>'.
            '<a href="'.self::href(['view'=>'settings']).'">Settings</a><form method="post" action="index.php">'.self::csrf().
            self::hidden('action','logout').'<button>Sign out</button></form></div></details></div></header><main id="main" class="content">';
        $status = (new Mirror($store))->status();
        echo '<p id="mirror-banner" class="banner" role="status"'.($status['mode'] !== 'dual' || $status['mysql'] === 'reachable' ? ' hidden' : '').
            '>MySQL backup is awaiting sync. Your changes are saved in SQLite.</p>';
        if ($notice !== '') {
            echo '<p class="banner success" role="status">'.self::h($notice).'</p>';
        }
    }

    public static function shellEnd(array $prefs): void
    {
        echo '</main></div></div><dialog id="quick-add"><div class="dialog-head"><h2>Quick add</h2><button type="button" data-close aria-label="Close">×</button></div>'.
            self::select('quick_kind','Record type',['events'=>'Event','contacts'=>'Contact','notes'=>'Note','list_items'=>'List item','journal_entries'=>'Journal entry'],'events',false).
            '<div id="quick-body" class="stack"></div></dialog><dialog id="reveal"><div class="dialog-head"><h2>Reveal protected value</h2>'.
            '<button type="button" data-close aria-label="Close">×</button></div><form id="reveal-form" class="stack">'.self::csrf().
            self::hidden('action','reveal').self::hidden('kind','').self::hidden('id','').self::hidden('field','').
            self::input('password','Application password','','password','required autocomplete="current-password"').
            '<button class="primary">Reveal for 30 seconds</button><output aria-live="polite" id="reveal-status"></output></form>'.
            '<div id="reveal-value" hidden><p class="secret-output break" id="secret-output"></p><div class="actions"><button type="button" id="copy-secret">Copy</button>'.
            '<button type="button" data-close>Hide</button></div></div></dialog></body></html>';
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
                $birthdays[] = ['id'=>$contact['id'],'title'=>$contact['title'],'meta'=>$when->format('M j'),'date'=>$when->format('Y-m-d')];
            }
        }
        usort($birthdays,static fn($a,$b)=>$a['date'] <=> $b['date']);
        $chores = self::dashboardRows($store,$owner,'chores',"AND json_extract(payload,'$.done')=0",'due');
        $stock = self::dashboardRows($store,$owner,'business_inventory',"AND json_extract(payload,'$.quantity')<=json_extract(payload,'$.threshold')",'quantity');
        $journal = self::dashboardRows($store,$owner,'journal_entries','','');
        $status = (new Mirror($store))->status();
        self::heading('YOUR OVERVIEW','Dashboard',$today->format('l, F j, Y'),self::button('Open calendar',['view'=>'calendar']));
        echo '<section class="hero"><div><p class="eyebrow">THE PERSONAL PLANNER</p><h2>Make room for what matters.</h2>'.
            '<p>Plan what’s ahead, find what you’ve saved, and keep the details of life in one private workspace.</p>'.
            '<a class="button hero-action" href="'.self::href(['view'=>'module','kind'=>'events']).'">Explore timeline '.self::icon('Arrow').'</a></div>'.
            '<div class="hero-art" aria-hidden="true"><i></i><i></i><i></i><span>'.self::icon('Layers').'</span></div></section>';
        echo '<div class="stats">';
        foreach ([
            ['Calendar','Today',count($todayItems),'Scheduled events'],
            ['Timeline','Next 7 days',count($upcoming),'Upcoming events'],
            ['Clock','Due reminders',count($due),'Ready for review'],
            ['Star','Birthdays',count($birthdays),'Within 30 days'],
        ] as [$icon,$label,$total,$caption]) {
            echo '<article class="panel stat"><div>'.self::icon($icon).'<span>'.self::h($label).'</span></div><strong'.
                ($icon === 'Clock' ? ' id="due-count"' : '').'>'.self::h($total).
                '</strong><small>'.self::h($caption).'</small></article>';
        }
        echo '</div><div class="dashboard-grid">';
        self::agendaCard('Today’s agenda',$todayItems,'Nothing scheduled today. Add an event when you’re ready.');
        self::agendaCard('Next 7 days',$upcoming,'Your upcoming plans will appear here.');
        self::agendaCard('Due reminders',$due,'You’re caught up on reminders.',true);
        self::miniCard('Upcoming birthdays','contacts',$birthdays,'Add a birthday to a contact to see it here.');
        self::miniCard('Open chores','chores',$chores,'Add a chore to start your cleaning schedule.');
        self::miniCard('Low stock','business_inventory',$stock,'No items have reached their low-stock threshold.');
        self::miniCard('Recent journal entries','journal_entries',$journal,'Write your first journal entry.');
        echo '<section class="panel widget"><div class="widget-head"><h2>Storage & sync</h2><span class="pill success"><span class="online-dot"></span> SQLite primary</span></div>'.
            '<dl class="facts" id="sync-facts"><dt>Mode</dt><dd>'.self::h($status['mode']).'</dd><dt>MySQL</dt><dd data-health="mysql">'.self::h($status['mysql']).
            '</dd><dt>Pending</dt><dd data-health="queueDepth">'.self::h($status['queueDepth']).'</dd><dt>Last sync</dt><dd data-health="lastSync">'.
            self::h($status['lastSync'] ?? '—').'</dd><dt>Last error</dt><dd data-health="lastError">'.self::h($status['lastError'] ?? '—').
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
            '" aria-label="Open '.self::h($title).'">'.self::icon('Arrow').'</a></div>';
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
            '" aria-label="Open calendar">'.self::icon('Arrow').'</a></div><div'.($due ? ' id="due-list" aria-live="polite"' : '').'>';
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
            ($detail ? '<small>'.self::h($event['start']->format($event['allDay'] ? 'M j · \A\l\l \d\a\y' : 'M j · H:i')).'</small>' : '').'</a>';
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
        self::heading('PLANNER','Calendar',$anchor->format('F Y'),self::button('Add event',['view'=>'record','kind'=>'events','date'=>$anchor->format('Y-m-d')],true));
        echo '<div class="calendar-controls"><div class="actions">'.
            self::button('← Previous',$params+['date'=>$base->modify('-'.$step)->format('Y-m-d')]).
            self::button('Today',$params+['date'=>$today]).
            self::button('Next →',$params+['date'=>$base->modify('+'.$step)->format('Y-m-d')]).'</div><nav class="tabs" aria-label="Calendar view">';
        foreach (['month'=>'Month','week'=>'Week','agenda'=>'Agenda'] as $key=>$label) {
            echo '<a class="'.($key === $mode ? 'selected' : '').'" href="'.self::href(['view'=>'calendar','mode'=>$key,'date'=>$anchor->format('Y-m-d')]).'">'.$label.'</a>';
        }
        echo '</nav></div>';
        if ($mode === 'agenda') {
            self::agendaCard('Upcoming agenda',$items,'No events in this range.');
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
        echo '<section class="panel calendar-grid" aria-label="Calendar days">';
        foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day) {
            echo '<div class="weekday">'.$day.'</div>';
        }
        for ($day=$gridStart;$day<=$gridEnd;$day=$day->modify('+1 day')) {
            $key = $day->format('Y-m-d');
            echo '<div class="calendar-day'.($key === $today ? ' current' : '').($day->format('m') !== $anchor->format('m') ? ' outside' : '').
                '"><a class="day-number" aria-label="Add event on '.self::h($key).'" href="'.self::href(['view'=>'record','kind'=>'events','date'=>$key]).
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
        throw new Problem('Unknown module.');
    }

    private static function tabs(string $group,string $kind): void
    {
        if (count(Catalog::MODULES[$group]) < 2) {
            return;
        }
        echo '<nav class="tabs module-tabs" aria-label="Modules">';
        foreach (Catalog::MODULES[$group] as $module) {
            echo '<a'.($module===$kind ? ' class="selected" aria-current="page"' : '').' href="'.
                self::href(['view'=>'module','kind'=>$module]).'">'.self::h(Catalog::LABELS[$module]).'</a>';
        }
        if ($group === 'Vault') {
            echo '<a href="'.self::href(['view'=>'banks']).'">Compare banks</a>';
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
            $labels[$row['id']] = count($kinds) > 1 ? $row['title'].' · '.Catalog::LABELS[$row['kind']] : $row['title'];
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
        $page = max(1,min(10000,(int)($filter['p'] ?? 1)));
        $take = 30;
        $offset = ($page-1)*$take;
        $q = Catalog::text($filter['q'] ?? '',180);
        $linked = Catalog::text($filter['linked'] ?? '',36);
        $rating = (int)($filter['rating'] ?? 0);
        $named = ($filter['sort'] ?? '') === 'name';
        $where = 'r.owner=? AND r.kind=? AND r.deleted_at IS '.($trash ? 'NOT NULL' : 'NULL');
        $params = [$owner,$kind];
        if ($q !== '' && !$private) {
            $where .= ' AND instr(r.search,?)>0';
            $params[] = mb_strtolower($q);
        }
        $categoryField = match ($kind) {
            'contacts'=>'relationship','music_tracks'=>'genre','intimacy_events'=>'type','work_orders'=>'status',
            default=>'category',
        };
        $fields = Catalog::fields($kind,$prefs);
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
        self::heading(strtoupper($group),($trash ? 'Trash · ' : '').($timeline ? 'Timeline' : Catalog::LABELS[$kind]),
            $timeline ? 'A chronological record of your plans and milestones.' : 'Records in your private workspace.',
            self::button($trash ? 'Back to records' : 'Trash',['view'=>'module','kind'=>$kind]+($trash ? [] : ['trash'=>'1'])).
            (!$trash ? self::button('Add record',['view'=>'record','kind'=>$kind],true) : ''));
        self::tabs($group,$kind);
        if (in_array($kind,['business_inventory','income_entries','work_orders'],true)) {
            self::businessSummary($store,$owner,$prefs);
        }
        echo '<form class="panel filters" method="'.($private ? 'post' : 'get').'" action="'.self::href($private ? $route : []).'">'.
            ($private ? self::csrf() : self::hidden('view','module').self::hidden('kind',$kind).($trash ? self::hidden('trash','1') : '')).
            self::input('q',$private ? 'Search private entries' : 'Search this module',$q,'search');
        foreach ($facets as $field=>[$type,$label]) {
            echo $type==='select' ? self::select($field,$label,self::options($fields[$field][2]),$filter[$field] ?? '')
                : self::input($field,$label,$filter[$field] ?? '');
        }
        if ($timeline || $private || $kind==='meal_plans') {
            echo self::input('from','From',$filter['from'] ?? '','date').self::input('to','Through',$filter['to'] ?? '','date');
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
            echo self::select('linked','Linked record',self::linkOptions($store,$owner,$targets,$linked ? [$linked] : []),$linked);
        }
        if ($kind==='contacts') {
            echo self::select('sort','Sort',['recent'=>'Recently updated','name'=>'Name A–Z'],$named ? 'name' : 'recent',false);
        }
        if ($kind==='intimacy_events') {
            echo self::select('rating','Overall rating',array_combine(range(1,10),range(1,10)),$rating);
        }
        if ($kind==='music_tracks') {
            echo self::checkbox('favorite','Favorites only',$favorite);
        }
        echo '<div class="actions"><button>Apply filters</button>'.self::button('Clear',$route);
        if ($private && $page>1) {
            echo '<button name="p" value="'.($page-1).'">Previous page</button>';
        }
        if ($private && $next) {
            echo '<button name="p" value="'.($page+1).'">Next page</button>';
        }
        echo '</div></form>';
        if (!$records) {
            echo '<div class="panel empty">'.self::icon($group).'<h2>No records to show</h2><p>'.
                ($trash ? 'Deleted records appear here.' : 'Try different filters, add a record, or load the fictional demo in Settings.').
                '</p>'.($trash ? '' : self::button('Add record',['view'=>'record','kind'=>$kind],true).' '.self::button('Open Settings',['view'=>'settings'])).'</div>';
        }
        $month = '';
        $today = self::today($prefs)->format('Y-m-d');
        foreach ($records as $row) {
            $p = Store::payload($row);
            $local = self::localKey($row['starts'],$prefs);
            $newMonth = substr($local,0,7);
            if ($timeline && $newMonth !== '' && $newMonth !== $month) {
                $month = $newMonth;
                echo '<h2 class="month-label">'.self::h((new DateTimeImmutable($month.'-01'))->format('F Y')).'</h2>';
            }
            $label = $p[$categoryField] ?? $p['type'] ?? '';
            echo '<article class="panel record-row"><div class="record-icon">'.self::icon($group).'</div><div class="row-content"><a class="row-title" href="'.
                self::href(['view'=>'record','kind'=>$kind,'id'=>$row['id']]+($trash ? ['trash'=>'1'] : [])).'">'.self::highlight($row['title'],$q).
                '</a><small>'.self::h(implode(' · ',array_filter([self::dateLabel($row['starts'],$prefs),$label,$p['tags'] ?? '']))).'</small></div>';
            if ($timeline) {
                echo '<span class="pill">'.(substr($local,0,10)<$today ? 'Past' : 'Upcoming').' · '.self::h($p['status']).'</span>';
            }
            if (!empty($p['last_four'])) {
                echo '<span class="pill">•••• '.self::h($p['last_four']).'</span>';
            }
            if ($kind==='attachments' && !$trash && str_starts_with($p['mime'],'image/')) {
                echo '<img class="thumbnail" src="'.self::href(['file'=>$row['id']]).'" alt="" loading="lazy">';
            }
            if ($kind==='websites' && !$trash && $p['url']) {
                echo '<a href="'.self::h($p['url']).'" target="_blank" rel="noopener noreferrer" class="pill">Open site ↗</a>';
            }
            echo '</article>';
        }
        if (!$private) {
            $pagination = $_GET;
            unset($pagination['p']);
            echo '<nav class="actions pager" aria-label="Pagination">'.
                ($page>1 ? self::button('← Previous',$pagination+['p'=>$page-1]) : '').
                ($next ? self::button('Next →',$pagination+['p'=>$page+1]) : '').'</nav>';
        }
        if (!$private && !$trash) {
            echo '<form class="pager" method="post" action="index.php">'.self::csrf().self::hidden('action','export').self::hidden('kind',$kind).
                '<button>Export module CSV</button></form>';
        }
    }


    public static function record(Store $store,string $owner,string $kind,array $prefs): void
    {
        self::group($kind);
        $id = (string)($_GET['id'] ?? '');
        $row = $id === '' ? null : $store->record($owner,$id,$kind,($_GET['trash'] ?? '')==='1');
        $p = $row ? Store::payload($row) : [];
        self::heading(Catalog::LABELS[$kind],$row['title'] ?? 'New record',
            $row ? 'Updated '.self::dateLabel($row['updated_at'],$prefs) : 'Add an entry to your workspace.',
            self::button('Back to records',['view'=>'module','kind'=>$kind]));
        if ($row && $row['deleted_at'] !== null) {
            echo '<section class="panel widget"><p>This record is in the trash.</p>'.
                self::actionForm('restore_record','Restore record',$row).'</section>';
            return;
        }
        if ($kind === 'intimacy_events') {
            echo '<p class="banner">All questionnaire answers and ratings are optional. An unanswered consent question stays unanswered.</p>';
        }
        if ($kind==='contacts' && !empty($p['dob'])) {
            $birth = Planner::date($p['dob']);
            $now = Planner::date(self::today($prefs)->format('Y-m-d'));
            if ($birth <= $now) {
                echo '<p class="pill">Age '.self::h($birth->diff($now)->y).'</p>';
            }
        }
        if ($kind==='attachments' && $row) {
            echo '<section class="panel widget">'.self::button('Open file',['file'=>$id]).
                (str_starts_with($p['mime'],'image/') ? '<img class="preview" alt="Uploaded image" src="'.self::href(['file'=>$id]).'">' : '').
                '<p>'.self::h($p['mime']).' · '.self::h($p['size']).' bytes</p></section>';
        }
        if ($row && in_array($kind,['journal_entries','daily_logs'],true)) {
            $body = $store->privateValue($row,'content');
            if ($body !== '') {
                $safe = self::h($body);
                $safe = preg_replace('/\*\*([^*\r\n]+)\*\*/','<strong>$1</strong>',$safe);
                $safe = preg_replace('/(?<!\*)\*([^*\r\n]+)\*(?!\*)/','<em>$1</em>',$safe);
                echo '<section class="panel widget"><h2>Entry preview</h2><p class="journal-text">'.$safe.'</p></section>';
            }
        }
        self::editor($store,$owner,$kind,$prefs,$row);
        if (!$row) {
            return;
        }
        echo '<div class="actions record-actions">';
        if ($kind === 'events') {
            echo self::actionForm('duplicate','Duplicate event',$row);
        }
        if (in_array($kind,['vehicle_documents','documents','appointments','work_shifts','chores','contacts'],true)) {
            echo self::actionForm('schedule',$kind==='contacts' ? 'Schedule birthday reminder' : 'Add to planner',$row);
        }
        echo self::actionForm('delete','Move to trash',$row,true).'</div>';
        if (in_array($kind,['contacts','vehicles','accounts','documents','playlists','lists','work_orders'],true)) {
            $related = $store->run('SELECT DISTINCT r.id,r.kind,r.title,r.starts FROM links l JOIN records r ON r.id=l.source
                WHERE l.target=? AND r.owner=? AND r.deleted_at IS NULL ORDER BY r.starts DESC LIMIT 30',[$id,$owner])->fetchAll();
            echo '<section class="panel widget"><h2>Linked records</h2>';
            foreach ($related as $link) {
                echo '<a class="mini-row" href="'.self::href(['view'=>'record','kind'=>$link['kind'],'id'=>$link['id']]).'">'.
                    self::h($link['title']).'<small>'.self::h(Catalog::LABELS[$link['kind']].' · '.self::dateLabel($link['starts'],$prefs)).'</small></a>';
            }
            if (!$related) {
                echo '<p>No records link here yet.</p>';
            }
            echo '</section>';
        }
    }

    private static function actionForm(string $action,string $label,array $row,bool $confirm = false): string
    {
        return '<form method="post" action="index.php" data-record-action'.($confirm ? ' data-confirm="Move this record to the trash?"' : '').'>'.
            self::csrf().self::hidden('action',$action).self::hidden('kind',$row['kind']).self::hidden('id',$row['id']).
            self::hidden('revision',$row['revision']).'<button'.($confirm ? ' class="danger-button"' : '').'>'.self::h($label).'</button></form>';
    }

    public static function editor(Store $store,string $owner,string $kind,array $prefs,?array $row = null,bool $quick = false): void
    {
        $p = $row ? Store::payload($row) : [];
        $fields = Catalog::fields($kind,$prefs);
        $auto = $row && !in_array($kind,['attachments','accounts','bank_accounts','cards','personal_profile','vehicles'],true);
        if ($quick) {
            $keys = match ($kind) {
                'events'=>['category','all_day','start','end','zone'],
                'contacts'=>['first','last','phone','email'],
                'notes'=>['content'],
                'list_items'=>['list','quantity'],
                'journal_entries'=>['time','content','rating'],
                default=>throw new Problem('Quick add is unavailable for this module.'),
            };
            $fields = array_intersect_key($fields,array_flip($keys));
        }
        echo '<form class="'.($quick ? '' : 'panel ').'editor" method="post" action="index.php" enctype="multipart/form-data"'.
            ($quick || $auto ? ' data-editor="'.($quick ? 'quick' : 'auto').'"' : '').'>'.
            self::csrf().self::hidden('action','save').self::hidden('kind',$kind).
            self::hidden('id',$row['id'] ?? '').self::hidden('revision',$row['revision'] ?? '').'<div class="form-grid">';
        if ($kind !== 'intimacy_events') {
            $label = match ($kind) {
                'contacts'=>'Display name (or enter first and last names)','attachments'=>'File title',
                'music_tracks'=>'Song title','personal_profile'=>'Preferred name',default=>'Title',
            };
            echo self::input('title',$label,$row['title'] ?? '','text','maxlength="180" '.($kind !== 'contacts' ? 'required' : ''));
        }
        foreach ($fields as $field=>[$type,$label]) {
            $options = $fields[$field][2] ?? [];
            $value = $p[$field] ?? '';
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
                echo '<h2 class="wide">'.($field === 'question_1' ? 'Optional questionnaire' : 'Optional ratings').'</h2>';
            }
            if ($type === 'secret') {
                $stored = isset($p['_private'][$field]);
                echo '<div class="secret-field">'.self::input($field,$label,'','password','autocomplete="new-password" placeholder="'.
                    ($stored ? '••••••••' : 'Add protected value').'".').'<div class="actions">';
                if ($stored) {
                    echo '<button type="button" data-reveal data-kind="'.self::h($kind).'" data-id="'.self::h($row['id']).'" data-field="'.
                        self::h($field).'">Reveal</button>'.self::checkbox('clear_'.$field,'Clear on save');
                }
                if ($field === 'password') {
                    echo '<button type="button" data-password-generator>Generate password</button>';
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
                    ? ' · use *italic* or **bold**' : ''),$value,'maxlength="24000"'),
                'select','private-choice'=>self::select($field,$label,self::options($options),$value),
                'rating','private-rating'=>self::select($field,$label,array_combine(range(1,10),range(1,10)),$value),
                'check'=>self::checkbox($field,$label,$field === 'all_day' && !$row ? true : (bool)$value),
                'money'=>self::input($field,$label.' ('.($value ? $value['currency'] : $prefs['currency']).')',
                    $value ? Catalog::decimal($value) : '','text','inputmode="decimal"'),
                'eventtime'=>self::input($field,$label,$value,($p['all_day'] ?? 1) ? 'date' : 'datetime-local',
                    'data-event-time '.($field === 'start' ? 'required' : '')),
                'zone'=>self::timeZones($value),
                'date','month','email','url'=>self::input($field,$label,$value,$type),
                'datetime'=>self::input($field,$label,$value,'datetime-local'),
                'clock'=>self::input($field,$label,$value,'time'),
                'positive','number'=>self::input($field,$label,$value,'number','min="'.($type==='positive'?1:0).'"'),
                'upload'=>self::input('upload',$label,'','file','accept="image/png,image/jpeg,image/gif,image/webp,application/pdf" '.(!$row ? 'required' : '')),
                default=>self::input($field,$label,$value),
            };
        }
        echo '</div><div class="actions editor-actions"><button class="primary" type="submit">Save record</button>'.
            '<output aria-live="polite" class="save-status">'.($auto ? 'Edits save automatically.' : '').'</output></div></form>';
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
        echo '<div class="panel business-summary"><div><strong>Paid this month</strong><span>'.
            self::h($totals ? implode(' · ',array_map(static fn($a)=>$a['currency'].' '.Catalog::decimal($a),$totals)) : '—').
            '</span></div><div><strong>Outstanding work</strong><span>'.self::h($open).
            '</span></div><div><strong>Low-stock items</strong><span>'.self::h($low).'</span></div></div>';
    }

    public static function search(Store $store,string $owner): void
    {
        $q = Catalog::text($_GET['q'] ?? '',180);
        $kind = Catalog::text($_GET['kind'] ?? '',40);
        $kinds = array_values(array_diff(array_keys(Catalog::LABELS),[
            'intimacy_events','daily_logs','accounts','bank_accounts','cards','personal_profile','phone_history','email_history',
        ]));
        $choices = in_array($kind,$kinds,true) ? [$kind] : $kinds;
        self::heading('WORKSPACE','Search','Find records across your workspace. Search private entry text within its journal.');
        echo '<form class="panel filters" method="get" action="index.php">'.self::hidden('view','search').
            self::input('q','Search text',$q,'search','required maxlength="180"').
            self::select('kind','Module',array_intersect_key(Catalog::LABELS,array_flip($kinds)),$kind).'<button class="primary">Search</button></form>';
        if ($q === '') {
            return;
        }
        $slots = implode(',',array_fill(0,count($choices),'?'));
        $rows = $store->run("SELECT id,kind,title,search FROM records WHERE owner=? AND deleted_at IS NULL AND kind IN ($slots)
            AND instr(search,?)>0 ORDER BY kind,updated_at DESC LIMIT 100",[$owner,...$choices,mb_strtolower($q)])->fetchAll();
        if (!$rows) {
            echo '<div class="panel empty"><h2>No matches</h2><p>Try a different phrase or module.</p></div>';
        }
        $last = '';
        foreach ($rows as $row) {
            if ($row['kind'] !== $last) {
                echo '<h2 class="month-label">'.self::h(Catalog::LABELS[$row['kind']]).'</h2>';
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
        self::heading('VAULT','Bank comparison','See which access fields are on file without revealing them.');
        self::tabs('Vault','');
        $groups = [];
        foreach ($store->run("SELECT * FROM records WHERE owner=? AND kind IN ('bank_accounts','cards') AND deleted_at IS NULL",[$owner]) as $row) {
            $groups[Store::payload($row)['institution'] ?: 'Institution unspecified'][] = $row;
        }
        ksort($groups,SORT_NATURAL | SORT_FLAG_CASE);
        if (!$groups) {
            echo '<div class="panel empty"><h2>No banking records</h2>'.self::button('Add a bank record',['view'=>'record','kind'=>'bank_accounts'],true).'</div>';
        }
        foreach ($groups as $institution=>$rows) {
            echo '<h2 class="month-label">'.self::h($institution).'</h2>';
            foreach ($rows as $row) {
                $p = Store::payload($row);
                $access = array_intersect_key(['username'=>'Username','password'=>'Password','pin'=>'PIN','number'=>'Account number','routing'=>'Routing number'],
                    $p['_private'] ?? []);
                echo '<article class="panel record-row"><div class="row-content"><a class="row-title" href="'.
                    self::href(['view'=>'record','kind'=>$row['kind'],'id'=>$row['id']]).'">'.self::h($row['title']).
                    '</a><small>'.self::h(implode(' · ',array_filter([$p['subtype'],$p['expiry'],implode(', ',$access)]))).'</small></div>'.
                    '<span class="pill">'.self::h(!empty($p['last_four']) ? '•••• '.$p['last_four'] : 'No card digits').'</span></article>';
            }
        }
    }

    public static function settings(Store $store,array $user,array $result): void
    {
        $p = Store::payload($user);
        self::heading('WORKSPACE','Settings','Preferences, portability and installation tools.');
        echo '<section class="panel widget"><h2>Personal preferences</h2><form method="post" action="index.php">'.
            self::csrf().self::hidden('action','settings').self::hidden('operation','preferences').'<div class="form-grid">'.
            self::select('theme','Appearance',self::options(['system','light','dark']),$p['theme'],false).self::timeZones($p['zone']).
            self::input('currency','Currency',$p['currency'],'text','required pattern="[A-Z]{3}"').
            self::select('format','Date format',self::options(['Y-m-d','m/d/Y','d/m/Y']),$p['format'],false);
        foreach (['relationships'=>'Additional contact relationships','account_types'=>'Additional account types',
            'intimacy_types'=>'Additional intimacy event types','intimacy_questions'=>'Additional private questionnaire prompts'] as $key=>$label) {
            echo self::area($key,$label.' (one per line)',$p[$key] ?? '');
        }
        echo '</div><div class="actions editor-actions"><button class="primary">Save preferences</button></div></form></section>'.
            '<section class="panel widget"><h2>Data portability</h2><p>General exports omit private journal and intimacy entries and protected fields.</p><div class="actions">'.
            '<form method="post" action="index.php">'.self::csrf().self::hidden('action','export').'<button>Export JSON</button></form>'.
            '<form method="post" action="index.php">'.self::csrf().self::hidden('action','settings').self::hidden('operation','seed').
            '<button>Load fictional demo</button></form></div></section>';
        if ($p['role'] !== 'admin') {
            return;
        }
        $output = $_SESSION['maintenance'] ?? null;
        unset($_SESSION['maintenance']);
        echo '<section class="panel widget"><h2>Installation &amp; backup</h2><p>Configuration is active. Restart Apache after changing its file.</p>'.
            '<details><summary>Show redacted configuration</summary><pre>'.self::h(json_encode(Config::public($result),JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)).'</pre></details>';
        if ($output !== null) {
            echo '<pre role="status">'.self::h(json_encode($output,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)).'</pre>';
        }
        echo '<form class="form-grid" method="post" action="index.php" data-maintenance>'.self::csrf().self::hidden('action','settings').
            self::select('operation','Action',['verify'=>'Verify backup','repair'=>'Queue repair','drain'=>'Drain backup queue','migrate'=>'Apply migrations',
                'backup'=>'Create backup','restore'=>'Restore backup','reconfigure'=>'Reconfiguration instructions'],'verify',false).
            self::input('backup','Backup identifier','','text','placeholder="YYYYMMDDTHHMMSSZ-abcdef"').
            self::checkbox('from_mirror','Restore from the MySQL dump').
            self::input('password','Confirm application password','','password','required autocomplete="current-password"').
            '<div class="actions"><button>Run action</button></div></form></section>';
        $mirror = $store->config['mirror'];
        echo '<section class="panel widget"><h2>Change backup mode</h2><p>Your SQLite database and existing users stay in place.</p>'.
            '<form class="form-grid" method="post" action="index.php">'.self::csrf().self::hidden('action','settings').self::hidden('operation','mirror_config').
            self::select('mode','Storage',['sqlite'=>'SQLite only','dual'=>'SQLite with MySQL backup'],$store->config['mode'],false).
            self::input('mysql_host','MySQL host',$mirror['host'] ?? '127.0.0.1').
            self::input('mysql_port','MySQL port',$mirror['port'] ?? 3306,'number','min="1" max="65535"').
            self::input('mysql_database','MySQL database',$mirror['database'] ?? 'lifetools').
            self::input('mysql_user','MySQL user',$mirror['user'] ?? 'lifetools').
            self::input('mysql_password','New MySQL password (leave empty to keep)','','password','autocomplete="new-password"').
            self::checkbox('clear_mysql_password','Use an empty MySQL password').
            self::input('password','Confirm application password','','password','required autocomplete="current-password"').
            '<div class="actions"><button>Save backup mode</button></div></form></section>'.
            '<section class="panel widget"><h2>Create an application user</h2>'.
            '<p>Each user gets a separate workspace. Administrators manage this installation and its backups.</p><form class="form-grid" method="post" action="index.php">'.
            self::csrf().self::hidden('action','settings').self::hidden('operation','create_user').
            self::input('login','New login','','text','required autocomplete="off"').
            self::select('role','Role',['standard'=>'Standard user','admin'=>'Administrator'],'standard',false).
            self::input('new_password','New user password','','password','required minlength="12" maxlength="72" autocomplete="new-password"').
            self::input('password','Your application password','','password','required autocomplete="current-password"').
            '<div class="actions"><button>Create user</button></div></form></section>';
    }
}

