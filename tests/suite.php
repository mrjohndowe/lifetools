<?php
declare(strict_types=1);

final class Tests
{
    private static int $checks = 0;

    private static function check(bool $result,string $label): void
    {
        if (!$result) {
            throw new Problem("FAIL $label");
        }
        self::$checks++;
        echo "PASS $label\n";
    }

    private static function rejected(callable $operation): bool
    {
        try {
            $operation();
            return false;
        } catch (Problem) {
            return true;
        }
    }

    public static function run(?array $selected): int
    {
        self::$checks = 0;
        $mode = $selected['mode'] ?? 'sqlite';
        $path = LT_ROOT.'/data/test-'.ltUuid().'.sqlite';
        $config = [
            'path'=>$path,'mode'=>$mode,'key'=>random_bytes(32),'session'=>bin2hex(random_bytes(32)),
            'theme'=>'system','zone'=>'America/Denver','currency'=>'USD','format'=>'Y-m-d',
            'mirror'=>$selected['mirror'] ?? [],
        ];
        $schema = 'lt_test_' . bin2hex(random_bytes(6));
        $server = null;
        try {
            if ($mode === 'dual') {
                $server = Mirror::connect($config['mirror']);
                $server->exec("CREATE DATABASE $schema CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $config['mirror']['database'] = $schema;
            }
            $store = new Store($config,true);
            $store->migrate();
            $store->migrate();
            self::check((int)$store->db->query('SELECT COUNT(*) FROM migrations')->fetchColumn() === 1,'migration is repeatable');
            $installation = ltUuid();
            $store->meta('installation',$installation . ':' . hash_hmac('sha256',$installation,$config['key']));
            $store->checkInstallation($installation);
            [$owner,$other] = $store->transaction(fn()=>[
                $store->createUser('demo-admin','Fictional-Only-2026!','admin',$config)['id'],
                $store->createUser('demo-second','Another-Fictional-2026!','standard',$config)['id'],
            ]);
            $prefs = Store::payload($store->record($owner,$owner,'users'));

            $encoded = ltSeal('synthetic',$config['key'],'record:field');
            self::check(ltOpen($encoded,$config['key'],'record:field') === 'synthetic','encryption round trip');
            self::check($encoded !== ltSeal('synthetic',$config['key'],'record:field'),'random encryption nonce');
            self::check(self::rejected(fn()=>ltOpen($encoded,$config['key'],'wrong-field')),'AAD rejects field substitution');
            $tampered = base64_decode($encoded,true);
            $tampered[29] = chr(ord($tampered[29]) ^ 1);
            self::check(self::rejected(fn()=>ltOpen(base64_encode($tampered),$config['key'],'record:field')),'authentication rejects tampering');

            $contact = Catalog::save($store,$owner,'contacts',['title'=>'Fictional Example'],$prefs);
            $event = Catalog::save($store,$owner,'events',[
                'title'=>'Weekly demo','start'=>'2026-10-25T23:30','end'=>'2026-10-26T00:15',
                'zone'=>'America/Denver','frequency'=>'weekly','weekdays'=>'7','count'=>'8',
                'skips'=>'2026-11-08, 2026-11-22','overrides'=>'2026-11-15=2026-11-16T18:00','contact'=>$contact['id'],
            ],$prefs);
            $occurrences = Planner::occurrences($event,'2026-10-01','2027-01-01','America/Denver');
            self::check(count($occurrences) === 6,'weekly count with two exclusions');
            self::check(array_map(static fn($o)=>$o['start']->format('Y-m-d H:i'),$occurrences) === [
                '2026-10-25 23:30','2026-11-01 23:30','2026-11-16 18:00','2026-11-29 23:30','2026-12-06 23:30','2026-12-13 23:30',
            ],'recurrence keeps wall time and applies override across DST');
            self::check($occurrences[1]['start']->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i') ===
                '2026-11-02 06:30','late event UTC time after DST');
            $monthly = Catalog::save($store,$owner,'events',[
                'title'=>'Month-end example','start'=>'2026-01-31','all_day'=>'1','frequency'=>'monthly','count'=>'3',
            ],$prefs);
            $monthDates = Planner::occurrences($monthly,'2026-01-01','2026-06-30','Pacific/Honolulu');
            self::check(array_map(static fn($o)=>$o['start']->format('Y-m-d'),$monthDates) ===
                ['2026-01-31','2026-03-31','2026-05-31'],'month-end skips short months and all-day dates never shift');
            self::check(self::rejected(fn()=>Planner::local('2026-03-08T02:30',new DateTimeZone('America/Denver'))),'DST gap validation');
            self::check(self::rejected(fn()=>$store->record($other,$contact['id'])),'owner-only record read');
            self::check(self::rejected(fn()=>Catalog::save($store,$other,'events',[
                'title'=>'Denied','all_day'=>'1','start'=>'2026-10-12','contact'=>$contact['id'],
            ],$prefs)),'owner-only record linking');
            $bank = Catalog::save($store,$owner,'cards',['title'=>'Synthetic card','card'=>'0000 0000 0000 0019',
                'password'=>'Synthetic-Access','cvv'=>'999'],$prefs);
            self::check(!str_contains($bank['payload'],'Synthetic-Access') && !str_contains($bank['payload'],'0000 0000') &&
                !array_key_exists('cvv',Store::payload($bank)) && Store::payload($bank)['last_four'] === '0019','masked ciphertext and CVV exclusion');
            self::check($store->privateValue($bank,'password') === 'Synthetic-Access','vault decrypts an authorized field');
            $journal = Catalog::save($store,$owner,'intimacy_events',['date'=>'2026-11-09','content'=>'Fictional reflection'],$prefs);
            self::check(!str_contains($journal['payload'],'Fictional reflection') && $store->privateValue($journal,'question_2') === '',
                'private journal text encrypted and unanswered consent stays blank');
            self::check(self::rejected(fn()=>Catalog::save($store,$owner,'events',[
                'id'=>$event['id'],'revision'=>'0','title'=>'Stale',
            ],$prefs)),'stale revisions are rejected');
            $request = ['title'=>'Idempotent note','request_id'=>str_repeat('a',32)];
            $first = Catalog::save($store,$owner,'notes',$request,$prefs);
            $again = Catalog::save($store,$owner,'notes',$request,$prefs);
            self::check($first['id'] === $again['id'] && $first['revision'] === $again['revision'],
                'lost save acknowledgement can replay without creating another record');
            try {
                $store->transaction(function () use ($store,$owner): void {
                    $store->save($owner,'notes','Rolled back',[]);
                    throw new Problem('Rollback');
                });
            } catch (Problem) {
                // Deliberate failure.
            }
            self::check(!(bool)$store->run("SELECT 1 FROM records WHERE title='Rolled back'")->fetchColumn(),'failed mutation rolls back');
            $store = new Store($config);
            self::check($store->record($owner,$event['id'])['title'] === 'Weekly demo','SQLite survives reconnect');
            $year = Maintenance::seed($store,$owner,$prefs,2026);
            $count = (int)$store->run('SELECT COUNT(*) FROM records')->fetchColumn();
            Maintenance::seed($store,$owner,$prefs,2027);
            self::check($year === 2026 && $count === (int)$store->run('SELECT COUNT(*) FROM records')->fetchColumn(),'synthetic seed is idempotent');
            $mirror = new Mirror($store);
            if ($mode === 'sqlite') {
                self::check($mirror->verify()['ok'] && $mirror->status()['mysql'] === 'not configured' &&
                    (int)$store->run('SELECT COUNT(*) FROM sync_queue')->fetchColumn() === 0,'SQLite mode leaves no MySQL jobs');
            } else {
                self::dual($store,$mirror,$owner,$event,$config);
            }
            echo self::$checks." checks passed in $mode mode.\n";
            return 0;
        } finally {
            unset($mirror,$store);
            if ($server) {
                $server->exec("DROP DATABASE $schema");
            }
            foreach ([$path,$path.'-wal',$path.'-shm'] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    private static function dual(Store $store,Mirror $mirror,string $owner,array $event,array $config): void
    {
        $bad = $config;
        $bad['mirror']['host'] = '127.0.0.1';
        $bad['mirror']['port'] = 1;
        $offline = new Store($bad);
        $outage = new Mirror($offline);
        $outage->drain();
        self::check($outage->status()['mysql'] === 'degraded' && $outage->status()['queueDepth'] > 0,'outage leaves a durable queue');
        $deleted = $offline->transaction(fn()=>$offline->save($owner,'events',$event['title'],Store::payload($event),old:$event,deleted:true));
        self::check($deleted['deleted_at'] !== null && $outage->status()['queueDepth'] > 0,'deletion commits while backup is unavailable');
        unset($outage,$offline);
        $mirror->drain(1000,true);
        self::check($mirror->verify()['ok'] && $mirror->status()['queueDepth'] === 0,'queue catches up with matching hashes');
        $mirror->locked(function (PDO $mysql) use ($event): void {
            $statement = $mysql->prepare('UPDATE lt_records SET revision=revision+10,title=? WHERE id=?');
            $statement->execute(['Deliberate drift',$event['id']]);
        });
        self::check(!$mirror->verify()['ok'],'verify detects drift');
        self::check($mirror->verify(true)['repairQueued'],'repair queues SQLite state');
        $mirror->drain(1000,true);
        $result = $mirror->verify();
        self::check($result['ok'] && count($result['history']) === 1,'SQLite wins and records a higher mirror revision');
        $store->queue($event['id'],(int)$deleted['revision']);
        $mirror->drain(1000,true);
        self::check($mirror->verify()['ok'],'tombstone replay is idempotent');
        $mirror->locked(static fn(PDO $mysql)=>$mysql->exec('ALTER TABLE lt_records ADD COLUMN drift_test INT NULL'));
        self::check(self::rejected(fn()=>$mirror->drain(1000,true)) && $mirror->status()['mysql']==='degraded',
            'unexpected backup schema refuses to sync');
    }
}

