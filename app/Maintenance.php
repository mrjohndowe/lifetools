<?php
declare(strict_types=1);

final class Maintenance
{
    public static function seed(Store $store, string $owner, array $prefs, ?int $year = null): int
    {
        return $store->transaction(fn()=>self::seedInTransaction($store,$owner,$prefs,$year));
    }

    public static function seedInTransaction(Store $store, string $owner, array $prefs, ?int $year = null): int
    {
        $year ??= (int)(new DateTimeImmutable('now',new DateTimeZone($prefs['zone'])))->format('Y');
        $year = (int)($store->meta('seed_year:' . $owner) ?? $year);
        $add = static function (string $kind,string $title,array $data = []) use ($store,$owner,$prefs): string {
            $hash = substr(hash('sha256',"$owner:$kind:$title"),0,32);
            $hash[12] = '4';
            $hash[16] = '8';
            $id = vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split($hash,4));
            if (!$store->run('SELECT 1 FROM records WHERE id=?',[$id])->fetchColumn()) {
                Catalog::write($store,$owner,$kind,['title'=>$title]+$data,$prefs,seedId:$id);
            }
            return $store->run('SELECT 1 FROM records WHERE id=? AND deleted_at IS NULL',[$id])->fetchColumn() ? $id : '';
        };
        $today = (new DateTimeImmutable('now',new DateTimeZone($prefs['zone'])))->format('Y-m-d');
        $contact = $add('contacts','Avery Example',['first'=>'Avery','last'=>'Example','dob'=>'1994-06-14',
            'email'=>'avery@example.invalid','phone'=>'202-555-0142','relationship'=>'Friend']);
        $vehicle = $add('vehicles','Blue Finch',['year'=>'2012','make'=>'Fiction Motors','model'=>'Finch']);
        foreach ([
            ['07-12','Moved in','rental'],['09-22',"Friend walked to B's",'personal'],
            ['10-02','Got car back','vehicle'],['10-20','Car broke down — tried to fix it with a friend','vehicle'],
        ] as [$date,$title,$category]) {
            $add('events',$title,['start'=>"$year-$date",'all_day'=>'1','category'=>$category,'status'=>'done',
                'contact'=>$contact,'related'=>$category === 'vehicle' && $vehicle !== '' ? [$vehicle] : []]);
        }
        $add('events','Weekly reset',['start'=>$today,'all_day'=>'1','frequency'=>'weekly','category'=>'personal','reminders'=>'60','tags'=>'routine']);
        if (!$store->run("SELECT 1 FROM records WHERE owner=? AND kind='personal_profile' LIMIT 1",[$owner])->fetchColumn()) {
            $add('personal_profile','Fictional demo profile',['legal'=>'Jordan Example','ssn'=>'000-00-0000','license'=>'DEMO-NOT-A-LICENSE']);
        }
        $add('phone_history','202-555-0133',['start'=>'2020-01-01']);
        $add('email_history','old@example.invalid',['start'=>'2020-01-01']);
        $account = $add('accounts','Example Planner',['contact'=>$contact,'url'=>'https://example.invalid/',
            'username'=>'fictional-demo','password'=>'Synthetic-vault-only-42!','question'=>'What is the demo mascot?',
            'answer'=>'Fictional Finch','codes'=>'DEMO-CODE']);
        foreach (['Premiere','Bank of America','PSC','Atlas Credit','Atlas CreditMaster'] as $index=>$name) {
            $add($index < 2 ? 'cards' : 'bank_accounts',$name . ' • demo',[
                'institution'=>$name,'subtype'=>$index < 2 ? 'credit' : 'checking','card'=>'0000 0000 0000 00' . (10+$index),
                'expiry'=>($year+2).'-12','number'=>'DEMO-ACCOUNT-'.$index,'routing'=>'000000000',
                'username'=>'atlas-demo','password'=>'Demo-only-vault-password!','pin'=>'0000','ssn'=>'000-00-0000',
            ]);
        }
        $add('vehicle_documents','Finch maintenance',['vehicle'=>$vehicle,'type'=>'maintenance','date'=>$today,'cost'=>'25']);
        $add('receipts','Corner shop receipt',['merchant'=>'Example Shop','date'=>$today,'amount'=>'12']);
        $add('chores','Tidy the entryway',['due'=>$today,'frequency'=>'weekly','interval'=>'1']);
        $list = $add('lists','Weekend groceries',['type'=>'grocery']);
        $add('list_items','Apples',['list'=>$list,'quantity'=>'4','position'=>'1']);
        $add('inventory_items','Folding chair',['room'=>'Closet','quantity'=>'2','value'=>'16']);
        $add('documents','Fictional lease checklist',['category'=>'other','issue'=>$today,'tags'=>'rental']);
        $track = $add('music_tracks','A Fictional Afternoon',['artist'=>'The Example Ensemble','album'=>'Made Up Songs','genre'=>'Instrumental','favorite'=>'1']);
        foreach (['Happy','Sad','Good','Okay','Sex','Driving','Work','Chill','Bass','Me'] as $name) {
            $playlist = $add('playlists',$name);
            if ($name === 'Chill') {
                $add('playlist_items','A Fictional Afternoon',['playlist'=>$playlist,'track'=>$track,'position'=>'1']);
            }
        }
        $add('business_inventory','Sample envelopes',['sku'=>'DEMO-001','quantity'=>'2','threshold'=>'5','cost'=>'1','price'=>'2']);
        $add('income_entries','Sample commission',['date'=>$today,'contact'=>$contact,'amount'=>'35','status'=>'paid']);
        $add('appointments','Sample client check-in',['time'=>$today.'T14:00','contact'=>$contact,'status'=>'planned']);
        $order = $add('work_orders','Fictional video delivery',['date'=>$today,'contact'=>$contact,'discount_type'=>'given discount',
            'discount'=>'5','amount'=>'40','status'=>'in progress']);
        $add('work_order_items','Two sample clips',['order'=>$order,'quantity'=>'2','duration'=>'10','amount'=>'30']);
        $add('work_order_deliveries','Sample draft',['order'=>$order,'sent'=>$today,'duration'=>'3']);
        $recipe = $add('recipes','Toast with apples',['ingredients'=>"Bread\nApples",'steps'=>'Toast the bread. Slice and add apples.']);
        $add('meal_plans','Saturday breakfast',['date'=>$today,'meal'=>'breakfast','recipe'=>$recipe]);
        $add('work_shifts','Studio shift',['time'=>$today.'T09:00','end'=>$today.'T13:00','role'=>'Example assistant','pay'=>'40']);
        $add('daily_logs','A fictional day',['time'=>$today.'T12:00','content'=>'A quiet morning and a walk around the block.']);
        $add('journal_entries','First journal entry',['time'=>$today.'T18:00','contact'=>$contact,'content'=>'This is a fictional journal entry.','rating'=>'8']);
        $add('intimacy_events','Demo date',['date'=>$today,'people'=>$contact ? [$contact] : [],'type'=>'Date',
            'privacy'=>'Owner only','content'=>'Fictional dinner date. No questionnaire responses recorded.']);
        $add('incidents','Fictional water spill',['time'=>$today.'T10:00','content'=>'A fictional example.','severity'=>'low','follow_up'=>'resolved']);
        $add('notes','Weekend note',['content'=>'Synthetic reminder to bring a reusable bag.']);
        $add('websites','Example Planner',['url'=>'https://example.invalid/','category'=>'Planner','account'=>$account,'favorite'=>'1']);
        $imageId = vsprintf('%s%s-%s-%s-%s-%s%s%s',str_split(substr(hash('sha256',$owner.':demo-image'),0,32),4));
        if (!$store->run('SELECT 1 FROM records WHERE id=?',[$imageId])->fetchColumn()) {
            $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO9WfNsAAAAASUVORK5CYII=',true);
            $store->save($owner,'attachments','Fictional sample image',[
                'mime'=>'image/png','size'=>strlen($image),'sha256'=>hash('sha256',$image),
            ],['file'=>$image],id:$imageId);
        }
        $store->meta('seed_year:' . $owner,(string)$year);
        return $year;
    }

    public static function backup(Store $store, string $actor): array
    {
        return ltLock(LT_ROOT . '/storage/worker.lock', function () use ($store,$actor): array {
            $store->transaction(fn()=>$store->audit($actor,'backup'));
            $name = gmdate('Ymd\THis\Z') . '-' . bin2hex(random_bytes(3));
            $directory = LT_ROOT . '/backups/' . $name;
            if (!mkdir($directory,0700)) {
                throw new Problem(ltText('could_not_create_the_backup_folder'));
            }
            $snapshot = $directory . '/sqlite.snapshot';
            $store->run('VACUUM INTO ?',[$snapshot]);
            chmod($snapshot,0600);
            $db = new PDO('sqlite:' . $snapshot);
            $counts = $db->query('SELECT kind,COUNT(*) AS count FROM records GROUP BY kind')->fetchAll(PDO::FETCH_KEY_PAIR);
            $db = null;
            $manifest = [
                'version'=>Store::VERSION,'createdAt'=>ltNow(),'authoritative'=>'sqlite',
                'installation'=>$store->meta('installation'),
                'files'=>['sqlite.snapshot'=>hash_file('sha256',$snapshot)],
                'counts'=>['sqlite'=>$counts],'mirror'=>$store->config['mode'] === 'dual' ? 'pending' : 'not configured',
            ];
            if ($store->config['mode'] === 'dual') {
                $file = $directory . '/mysql.jsonl';
                try {
                    $mirror = new Mirror($store);
                    $manifest['counts']['mysql'] = $mirror->locked(function (PDO $mysql) use ($file): array {
                        $handle = fopen($file,'xb');
                        if (!$handle) {
                            throw new Problem(ltText('could_not_write_the_mysql_dump'));
                        }
                        $counts = [];
                        try {
                            foreach ($mysql->query('SELECT * FROM lt_records ORDER BY id') as $row) {
                                $line = ltJson($row) . "\n";
                                if (fwrite($handle,$line) !== strlen($line)) {
                                    throw new Problem(ltText('the_mysql_dump_could_not_be'));
                                }
                                $counts[$row['kind']] = 1+($counts[$row['kind']] ?? 0);
                            }
                        } finally {
                            fclose($handle);
                        }
                        if (!$counts) {
                            throw new Problem(ltText('the_mirror_has_no_records_yet'));
                        }
                        return $counts;
                    });
                    $manifest['mirror'] = 'dumped';
                    $manifest['files']['mysql.jsonl'] = hash_file('sha256',$file);
                } catch (Throwable $e) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                    $manifest['mirror'] = 'failed';
                    $manifest['mirrorError'] = $e instanceof Problem ? $e->getMessage() : ltText('could_not_dump_mysql_backup');
                }
            }
            ltAtomic($directory . '/manifest.json',ltJson($manifest) . "\n");
            return ['backup'=>$name] + $manifest;
        });
    }

    public static function restore(Store $store,string $name,bool $fromMirror = false): array
    {
        if (!preg_match('/^\d{8}T\d{6}Z-[a-f0-9]{6}$/D',$name)) {
            throw new Problem(ltText('enter_a_backup_identifier_from_the'));
        }
        return ltLock(LT_ROOT . '/storage/worker.lock', function () use ($store,$name,$fromMirror): array {
            $directory = LT_ROOT . '/backups/' . $name;
            $manifest = json_decode((string)@file_get_contents($directory.'/manifest.json'),true);
            $file = $fromMirror ? 'mysql.jsonl' : 'sqlite.snapshot';
            $path = $directory.'/'.$file;
            if (($manifest['version'] ?? null) !== Store::VERSION || !isset($manifest['files'][$file])
                || !is_file($path) || !hash_equals($manifest['files'][$file],hash_file('sha256',$path))) {
                throw new Problem(ltText('the_selected_backup_is_missing_incompatible'));
            }
            $fingerprint = $manifest['installation'];
            $state = Install::state(['status'=>'OK']);
            if (!isset($state['id']) || !hash_equals($state['id'].':'.hash_hmac('sha256',$state['id'],$store->config['key']),$fingerprint)
                || ($store->meta('installation') !== null && $store->meta('installation') !== $fingerprint)) {
                throw new Problem(ltText('the_backup_does_not_belong_to'));
            }
            if (!$fromMirror) {
                $store->run('ATTACH DATABASE ? AS snapshot',[$path]);
            }
            try {
                $count = $store->transaction(function () use ($store,$fromMirror,$path,$fingerprint): int {
                    $store->db->exec('DELETE FROM links; DELETE FROM sync_queue; DELETE FROM sync_drift; DELETE FROM records;
                        DELETE FROM throttle; DELETE FROM save_receipts; DELETE FROM meta;');
                    if ($fromMirror) {
                        $handle = fopen($path,'rb');
                        try {
                            while (($line = fgets($handle)) !== false) {
                                $store->put(json_decode($line,true,512,JSON_THROW_ON_ERROR));
                            }
                            if (!feof($handle)) {
                                throw new Problem(ltText('could_not_read_the_complete_mysql'));
                            }
                        } finally {
                            fclose($handle);
                        }
                        $store->meta('installation',$fingerprint);
                    } else {
                        $fields = implode(',',Store::FIELDS);
                        $store->db->exec("INSERT INTO records ($fields) SELECT $fields FROM snapshot.records;
                            INSERT INTO meta SELECT * FROM snapshot.meta;
                            INSERT INTO sync_drift SELECT * FROM snapshot.sync_drift;");
                    }
                    if ($store->meta('installation') !== $fingerprint ||
                        !$store->run("SELECT 1 FROM records WHERE kind='users' AND deleted_at IS NULL AND json_extract(payload,'$.role')='admin'")->fetchColumn()) {
                        throw new Problem(ltText('the_backup_does_not_contain_a'));
                    }
                    foreach ($store->run('SELECT * FROM records') as $row) {
                        $store->indexLinks($row);
                    }
                    if ($store->config['mode'] === 'dual') {
                        $store->queueAll();
                    }
                    $store->meta('sync_next','0');
                    $store->meta('sync_result','pending');
                    $store->meta('session_generation',ltUuid());
                    return (int)$store->run('SELECT COUNT(*) FROM records')->fetchColumn();
                });
            } finally {
                if (!$fromMirror) {
                    $store->db->exec('DETACH DATABASE snapshot');
                }
            }
            return ['restored'=>$count,'source'=>$file];
        });
    }
}

