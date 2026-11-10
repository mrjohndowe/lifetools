<?php
declare(strict_types=1);

final class Catalog
{
    // Field definitions use type, label and optional choices or permitted link targets.
    public const MODULES = [
        'Timeline'=>['events'],
        'Contacts'=>['contacts'],
        'Vault'=>['personal_profile','phone_history','email_history','accounts','bank_accounts','cards'],
        'Organizers'=>['vehicles','vehicle_documents','receipts','chores','lists','list_items','inventory_items','documents'],
        'Music'=>['music_tracks','playlists','playlist_items'],
        'Business'=>['business_inventory','income_entries','appointments','work_orders','work_order_items','work_order_deliveries'],
        'Life admin'=>['meal_plans','recipes','work_shifts','daily_logs','journal_entries','intimacy_events','incidents','notes'],
        'Files'=>['attachments'],
        'Websites'=>['websites'],
    ];

    public const LABELS = [
        'events'=>'Events','contacts'=>'Contacts','personal_profile'=>'Personal profile','phone_history'=>'Past phones',
        'email_history'=>'Past emails','accounts'=>'Managed accounts','bank_accounts'=>'Bank accounts','cards'=>'Cards',
        'vehicles'=>'Vehicles','vehicle_documents'=>'Vehicle records','receipts'=>'Receipts','chores'=>'Chores & cleaning',
        'lists'=>'Lists','list_items'=>'List items','inventory_items'=>'Household inventory','documents'=>'Documents',
        'music_tracks'=>'Tracks','playlists'=>'Playlists','playlist_items'=>'Playlist tracks','business_inventory'=>'Business inventory',
        'income_entries'=>'Income','appointments'=>'Appointments','work_orders'=>'Work orders','work_order_items'=>'Order items',
        'work_order_deliveries'=>'Deliveries','meal_plans'=>'Meal plans','recipes'=>'Recipes','work_shifts'=>'Work shifts',
        'daily_logs'=>'Daily log','journal_entries'=>'Private journal','intimacy_events'=>'Intimacy journal',
        'incidents'=>'Incidents','notes'=>'Notes','attachments'=>'Files & photos','websites'=>'Websites',
    ];

    public const QUESTIONS = [
        'What type of encounter or activity occurred?',
        'Was the encounter consensual and mutually agreed upon?',
        'Did everyone involved feel comfortable participating?',
        'How would you rate the overall experience?',
        'How would you rate your emotional connection?',
        'How would you rate the communication?',
        'How would you rate the level of comfort and trust?',
        'How satisfied were you with the experience?',
        'How would you describe your mood before the event?',
        'How would you describe your mood afterward?',
        'What did you particularly enjoy?',
        'Was there anything you disliked or would change?',
        'Were any boundaries discussed or respected?',
        'Was there anything new you learned about your preferences?',
        'Is there anything you would like to remember for next time?',
        'Are there any additional notes you want to record?',
    ];

    public static function fields(string $kind, array $prefs = []): array
    {
        $contact = ['link','Contact',['contacts']];
        $file = ['links','Files or photos',['attachments']];
        $notes = ['private','Private notes'];
        $tags = ['text','Tags (comma-separated)'];
        $work = ['select','Status',['quoted','paid','in progress','delivered','refunded','files deleted']];
        $holder = [
            'holder'=>['text','Account holder'], 'dob'=>['date','Date of birth'],
            'email'=>['email','Email'],'phone'=>['text','Phone'],
        ];
        $bank = [
            'holder'=>['secret','Account holder'],'dob'=>['secret','Date of birth'],
            'email'=>['secret','Email'],'phone'=>['secret','Phone'],
            'institution'=>['text','Institution'], 'subtype'=>['select','Account type',['checking','savings','credit','debit','investment','other']],
            'ssn'=>['secret','Social Security number'], 'password'=>['secret','Online banking password'],
            'pin'=>['secret','PIN'], 'number'=>['secret','Account number'],'routing'=>['secret','Routing number'],
            'card'=>['secret','Card number'],'expiry'=>['month','Card expiry'],'card_type'=>['text','Card network or type'],
            'username'=>['secret','Username'],'contact'=>$contact,'notes'=>$notes,
        ];
        return match ($kind) {
            'events'=>[
                'description'=>['area','Description'], 'category'=>['select','Category',['rental','vehicle','medical','work','legal','personal','business']],
                'status'=>['select','Status',['planned','done','cancelled']], 'all_day'=>['check','All day'],
                'start'=>['eventtime','Starts'], 'end'=>['eventtime','Ends (optional)'], 'zone'=>['zone','Event time zone'],
                'location'=>['text','Location'], 'frequency'=>['select','Repeat',['daily','weekly','monthly','yearly']],
                'interval'=>['positive','Repeat every'], 'weekdays'=>['text','Weekdays (1 Monday to 7 Sunday)'],
                'until'=>['date','Repeat through'], 'count'=>['positive','Maximum occurrences'],
                'skips'=>['text','Skip dates (YYYY-MM-DD, separated by commas)'],
                'overrides'=>['area','Overrides (original YYYY-MM-DD = new YYYY-MM-DD or YYYY-MM-DDTHH:MM)'],
                'reminders'=>['text','Reminders (minutes before, separated by commas)'],
                'contact'=>$contact, 'related'=>['links','Related records',['vehicles','accounts','documents','work_orders']],
                'attachments'=>$file, 'tags'=>$tags,
            ],
            'contacts'=>[
                'first'=>['text','First name'],'middle'=>['text','Middle name'],'last'=>['text','Last name'],
                'dob'=>['date','Birthday'],'email'=>['email','Email'],'phone'=>['text','Primary phone'],
                'phone2'=>['text','Secondary phone'],'street'=>['text','Street'],'city'=>['text','City'],
                'state'=>['text','State'],'postal'=>['text','Postal code'],
                'relationship'=>['select','Relationship', self::options($prefs['relationships'] ?? '', [
                    'Mother','Father','Brother','Sister','Son','Daughter','Friend','Best Friend','Family','Work','Girlfriend',
                    'Boyfriend','Partner','Ex','Fling','Friends with Benefits','One Night Stand','Other'])],
                'organization'=>['text','Organization'],'job'=>['text','Job title'],'notes'=>$notes,'tags'=>$tags,
            ],
            'personal_profile'=>[
                'legal'=>['text','Legal name'],'dob'=>['date','Date of birth'],'ssn'=>['secret','Social Security number'],
                'license'=>['secret','Driver’s license'],'phone'=>['text','Current phone'],'email'=>['email','Current email'],'tags'=>$tags,
            ],
            'phone_history','email_history'=>['start'=>['date','From'],'end'=>['date','Through'],'notes'=>$notes],
            'accounts'=>$holder + [
                'username'=>['secret','Username'],'password'=>['secret','Password'],'recovery_email'=>['secret','Recovery email'],
                'recovery_phone'=>['secret','Recovery phone'],'question'=>['secret','Security questions'],
                'answer'=>['secret','Security answers'],'codes'=>['secret','Recovery codes'],'url'=>['url','Service URL'],
                'type'=>['select','Account type',self::options($prefs['account_types'] ?? '', ['Websites','Email','Social media','Gaming',
                    'Subscriptions','Banking','Credit or debit cards','Investments','Utilities','Other'])],
                'contact'=>$contact,'notes'=>$notes,'tags'=>$tags,
            ],
            'bank_accounts','cards'=>$bank,
            'vehicles'=>[
                'year'=>['positive','Year'],'make'=>['text','Make'],'model'=>['text','Model'],'vin'=>['secret','VIN'],
                'title_info'=>['text','Title details'],'registration'=>['secret','Registration number'],
                'registration_expires'=>['date','Registration expires'],'insurer'=>['text','Insurer'],
                'policy'=>['secret','Policy number'],'insurance_expires'=>['date','Insurance expires'],'attachments'=>$file,'notes'=>$notes,
            ],
            'vehicle_documents'=>[
                'vehicle'=>['link','Vehicle',['vehicles']], 'type'=>['select','Type',['title','registration','insurance','work order','manual','maintenance']],
                'date'=>['date','Date'],'cost'=>['money','Cost'],'shop'=>['text','Shop'],'description'=>['area','Description'],'attachments'=>$file,
            ],
            'receipts'=>['merchant'=>['text','Merchant'],'date'=>['date','Purchase date'],'amount'=>['money','Amount'],'category'=>['text','Category'],'attachments'=>$file],
            'chores'=>['due'=>['date','Due date'],'frequency'=>['select','Repeat',['daily','weekly','monthly','yearly']],
                'interval'=>['positive','Repeat every'],'done'=>['check','Done'],'notes'=>$notes],
            'lists'=>['type'=>['select','List type',['general','shopping','grocery','packing']],'notes'=>$notes],
            'list_items'=>['list'=>['link','List',['lists']],'quantity'=>['number','Quantity'],'category'=>['text','Category'],
                'done'=>['check','Done'],'position'=>['number','Order']],
            'inventory_items'=>['room'=>['text','Room'],'quantity'=>['number','Quantity'],'value'=>['money','Value'],'attachments'=>$file],
            'documents'=>['category'=>['select','Category',['receipts','tax docs','day stubs','journals','manuals','identity documents','other']],
                'issue'=>['date','Issue date'],'expires'=>['date','Expiry date'],'tags'=>$tags,'attachments'=>$file,'notes'=>$notes],
            'music_tracks'=>['date'=>['date','Release date'],'artist'=>['text','Artist'],'features'=>['text','Featured artists'],
                'album'=>['text','Album'],'genre'=>['text','Genre'],'favorite'=>['check','Favorite'],'url'=>['url','External link']],
            'playlists'=>['notes'=>['area','Playlist notes']],
            'playlist_items'=>['playlist'=>['link','Playlist',['playlists']],'track'=>['link','Track',['music_tracks']],'position'=>['number','Order']],
            'business_inventory'=>['sku'=>['text','SKU'],'quantity'=>['number','Quantity'],'cost'=>['money','Cost'],'price'=>['money','Price'],
                'location'=>['text','Stock location'],'threshold'=>['number','Low-stock threshold']],
            'income_entries'=>['date'=>['date','Date'],'contact'=>$contact,'client'=>['text','Client'],'description'=>['area','Description'],
                'amount'=>['money','Amount'],'method'=>['text','Payment method'],'status'=>['select','Status',['pending','paid','refunded']]],
            'appointments'=>['time'=>['datetime','Starts'],'contact'=>$contact,'client'=>['text','Client'],'service'=>['text','Service'],
                'status'=>['select','Status',['planned','done','cancelled']]],
            'work_orders'=>['date'=>['date','Order date'],'contact'=>$contact,'client'=>['text','Client'],
                'discount_type'=>['select','Discount type',['bought at a discount','given discount']],
                'discount'=>['money','Discount'],'amount'=>['money','Amount'],'status'=>$work,'attachments'=>$file],
            'work_order_items','work_order_deliveries'=>['order'=>['link','Work order',['work_orders']],
                'duration'=>['positive','Duration (minutes)'],'quantity'=>['positive','Quantity'],'sent'=>['date','Sent date'],
                'amount'=>['money','Amount'],'files_deleted'=>['check','Files deleted'],'attachments'=>$file],
            'recipes'=>['ingredients'=>['area','Ingredients'],'steps'=>['area','Steps'],'tags'=>$tags],
            'meal_plans'=>['date'=>['date','Meal date'],'meal'=>['select','Meal',['breakfast','lunch','dinner','snack']],
                'recipe'=>['link','Recipe',['recipes']],'notes'=>$notes],
            'work_shifts'=>['time'=>['datetime','Starts'],'end'=>['datetime','Ends'],'role'=>['text','Role'],'pay'=>['money','Pay']],
            'daily_logs','journal_entries'=>['time'=>['datetime','Entry date and time'],'category'=>['text','Category'],
                'content'=>['private','Entry text'],'contact'=>$contact,'tags'=>$tags,'rating'=>['rating','Mood (1 low to 10 high)'],
                'notes'=>$notes,'attachments'=>$file],
            'intimacy_events'=>self::intimacy($prefs),
            'incidents'=>['time'=>['datetime','Date and time'],'people'=>['links','People involved',['contacts']],
                'location'=>['text','Location'],'content'=>['private','Description'],'severity'=>['select','Severity',['low','medium','high']],
                'follow_up'=>['select','Follow-up',['open','monitoring','resolved']],'attachments'=>$file,'notes'=>$notes],
            'notes'=>['content'=>['private','Note'],'tags'=>$tags],
            'attachments'=>['upload'=>['upload','Image or PDF'],'notes'=>$notes],
            'websites'=>['url'=>['url','Website URL'],'category'=>['select','Category',['Planner','Organize','Accounts','Banks','Finances','Bills',
                'Personal','Family','Vehicles','Medical','Work']], 'notes'=>['area','Notes'],'favorite'=>['check','Favorite'],
                'account'=>['link','Managed account',['accounts']]],
            default=>throw new Problem('Unknown module.'),
        };
    }

    private static function intimacy(array $prefs): array
    {
        $fields = [
            'date'=>['date','Event date'],'start'=>['clock','Start time'],'end'=>['clock','End time'],'duration'=>['positive','Duration (minutes)'],
            'people'=>['links','Associated contacts',['contacts']],
            'type'=>['select','Event type',self::options($prefs['intimacy_types'] ?? '', ['Date','Romantic encounter','Affectionate activity',
                'Intimate encounter','Sexual activity','Other'])],
            'privacy'=>['select','Privacy level',['Owner only','Extra private']],
            'tags'=>['text','Tags (comma-separated)'],
        ];
        foreach ([
            'location'=>'Location or setting','content'=>'General description','notes'=>'Personal notes','activity'=>'Activity details',
            'preferences'=>'Preferences','boundaries'=>'Boundaries','communication_notes'=>'Communication notes',
            'reactions'=>'Emotional reactions','reflection'=>'Personal reflections','context'=>'Relationship context',
            'follow_up'=>'Follow-up notes','custom'=>'Custom questions and answers',
        ] as $key=>$label) {
            $fields[$key] = ['private',$label];
        }
        foreach (self::QUESTIONS as $index=>$question) {
            $fields['question_' . ($index+1)] = match ($index) {
                1,2=>['private-choice',$question,['Yes','No','Unknown','Not recorded']],
                3,4,5,6,7=>['private-rating',$question],
                default=>['private',$question],
            };
        }
        foreach (['Overall experience','Enjoyment','Emotional connection','Communication','Comfort','Trust','Satisfaction','After-event feelings'] as $index=>$label) {
            $fields['rating_' . ($index+1)] = ['private-rating',$label . ' (1 low to 10 high)'];
        }
        foreach (array_slice(self::options($prefs['intimacy_questions'] ?? '', []),0,10) as $index=>$question) {
            $fields['custom_question_' . ($index+1)] = ['private',$question];
        }
        return $fields;
    }

    private static function options(string $extra, array $defaults): array
    {
        return array_values(array_unique([...$defaults, ...array_filter(array_map('trim', explode("\n",$extra)))]));
    }

    public static function privateType(string $type): bool
    {
        return in_array($type,['private','private-choice','private-rating'],true);
    }

    public static function save(Store $store, string $owner, string $kind, array $input, array $prefs, ?array $file = null): array
    {
        $request = self::text($input['request_id'] ?? '',32);
        if ($request !== '' && !preg_match('/^[a-f0-9]{32}$/D',$request)) {
            throw new Problem('Invalid save request. Reload the editor.');
        }
        return $store->transaction(function () use ($store,$owner,$kind,$input,$prefs,$file,$request): array {
            if ($request !== '') {
                $store->run('DELETE FROM save_receipts WHERE expires<?',[time()]);
                $receipt = $store->run('SELECT kind,id,revision,updated_at FROM save_receipts WHERE owner=? AND request=?',
                    [$owner,$request])->fetch();
                if ($receipt) {
                    if ($receipt['kind'] !== $kind) {
                        throw new Problem('Invalid save request. Reload the editor.');
                    }
                    return $receipt;
                }
            }
            $row = self::write($store,$owner,$kind,$input,$prefs,$file);
            if ($request !== '') {
                $store->run('INSERT INTO save_receipts VALUES (?,?,?,?,?,?,?)',
                    [$owner,$request,$kind,$row['id'],$row['revision'],$row['updated_at'],time()+86400]);
            }
            return $row;
        });
    }

    public static function write(Store $store, string $owner, string $kind, array $input, array $prefs, ?array $file = null,
        ?string $seedId = null): array
    {
        $id = $input['id'] ?? '';
        $old = $id === '' ? null : $store->record($owner,$id,$kind);
        if ($old && (string)$old['revision'] !== (string)($input['revision'] ?? '')) {
            throw new Problem('This record changed in another view. Reload it before saving.');
        }
        if ($kind === 'personal_profile' && !$old && $store->run(
            "SELECT 1 FROM records WHERE owner=? AND kind='personal_profile' AND deleted_at IS NULL",[$owner])->fetchColumn()) {
            throw new Problem('Edit the existing personal profile.');
        }
        $title = self::text($input['title'] ?? '', 180);
        if ($kind === 'contacts' && $title === '') {
            $title = trim(self::text($input['first'] ?? '',80) . ' ' . self::text($input['last'] ?? '',80));
        }
        if ($kind === 'intimacy_events') {
            $title = 'Private event';
        }
        if ($title === '') {
            throw new Problem('Enter a title or display name.');
        }
        $p = ['_links'=>[]];
        $private = [];
        $fields = self::fields($kind,$prefs);
        foreach ($fields as $field=>[$type,$label]) {
            $options = $fields[$field][2] ?? [];
            if ($type === 'upload') {
                continue;
            }
            if ($type === 'link' || $type === 'links') {
                $ids = $type === 'link' ? [$input[$field] ?? ''] : ($input[$field] ?? []);
                if (!is_array($ids) || count($ids) > 40) {
                    throw new Problem("$label: choose up to 40 records.");
                }
                foreach (array_unique(array_filter($ids)) as $target) {
                    $linked = $store->record($owner, self::text($target,36));
                    if (!in_array($linked['kind'],$options,true)) {
                        throw new Problem("$label: this record type cannot be linked.");
                    }
                    $p['_links'][$field][] = $target;
                }
                continue;
            }
            $raw = $input[$field] ?? '';
            $value = self::text($raw, in_array($type,['area','private','secret'],true) ? 24000 : 500, $type === 'secret');
            if ($type === 'secret') {
                if (($input['clear_' . $field] ?? '') === '1') {
                    $private[$field] = '';
                } elseif ($value !== '') {
                    if ($field === 'dob') {
                        Planner::date($value);
                    }
                    if ($field === 'email' && !filter_var($value,FILTER_VALIDATE_EMAIL)) {
                        throw new Problem('Email: enter a valid email.');
                    }
                    if ($field === 'card' && !preg_match('/^(?:[0-9][ -]?){12,18}[0-9]$/D',$value)) {
                        throw new Problem('Card number: enter 13–19 digits, optionally separated by spaces or dashes.');
                    }
                    $private[$field] = $value;
                    if ($field === 'card') {
                        $p['last_four'] = substr(preg_replace('/[^0-9]/','',$value),-4);
                    }
                } elseif ($field === 'card' && $old) {
                    $p['last_four'] = Store::payload($old)['last_four'] ?? '';
                }
                continue;
            }
            if (self::privateType($type)) {
                if ($value !== '' && $type === 'private-choice' && !in_array($value,$options,true)) {
                    throw new Problem("$label: select a listed answer.");
                }
                if ($value !== '' && $type === 'private-rating') {
                    $value = (string)self::integer($value,1,10,$label);
                }
                if (array_key_exists($field,$input)) {
                    $private[$field] = $value;
                }
                continue;
            }
            if ($type === 'check') {
                $p[$field] = $value === '1' ? 1 : 0;
                continue;
            }
            if ($value === '') {
                $p[$field] = '';
                continue;
            }
            $p[$field] = match ($type) {
                'date'=>Planner::date($value)->format('Y-m-d'),
                'datetime'=>Planner::utc(Planner::local($value,new DateTimeZone($prefs['zone']))),
                'month'=>preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D',$value) ? $value : throw new Problem("$label: enter a valid month."),
                'clock'=>preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$value) ? $value : throw new Problem("$label: enter a valid time."),
                'number'=>self::integer($value,0,1000000000,$label),
                'positive'=>self::integer($value,1,$field === 'interval' ? 365 : 1000000,$label),
                'rating'=>self::integer($value,1,10,$label),
                'money'=>self::money($value,$old ? (Store::payload($old)[$field]['currency'] ?? $prefs['currency']) : $prefs['currency']),
                'select'=>in_array($value,$options,true) ? $value : throw new Problem("$label: choose a listed value."),
                'zone'=>in_array($value,DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC),true)
                    ? $value : throw new Problem("$label: choose a valid IANA time zone."),
                'email'=>filter_var($value,FILTER_VALIDATE_EMAIL) ? $value : throw new Problem("$label: enter a valid email."),
                'url'=>filter_var($value,FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($value,PHP_URL_SCHEME)),['http','https'],true)
                    && parse_url($value,PHP_URL_USER) === null
                    ? $value : throw new Problem("$label: enter an HTTP or HTTPS URL without login information."),
                default=>$value,
            };
        }
        if ($kind === 'events') {
            $p['status'] = $p['status'] ?: 'planned';
            $p['category'] = $p['category'] ?: 'personal';
            $p['zone'] = $p['zone'] ?: $prefs['zone'];
            $p = Planner::validate($p);
        } else {
            $p['_starts'] = $p['time'] ?? $p['date'] ?? $p['due'] ?? '';
        }
        if ($kind === 'attachments') {
            if ($file && (int)$file['error'] !== UPLOAD_ERR_NO_FILE) {
                [$metadata,$bytes] = self::upload($file);
                $p += $metadata;
                $private['file'] = $bytes;
            } elseif ($old) {
                $p += array_intersect_key(Store::payload($old),array_flip(['mime','size','sha256']));
            } else {
                throw new Problem('Choose an image or PDF to upload.');
            }
        }
        return $store->save($owner,$kind,$title,$p,$private,$old,$seedId);
    }

    public static function text(mixed $input, int $max, bool $preserveWhitespace = false): string
    {
        if (!is_string($input) && !is_int($input)) {
            throw new Problem('A field has an invalid value.');
        }
        $value = $preserveWhitespace ? (string)$input : trim((string)$input);
        if (!mb_check_encoding($value,'UTF-8') || mb_strlen($value) > $max || str_contains($value,"\0")) {
            throw new Problem("A field exceeds $max characters or contains invalid text.");
        }
        return $value;
    }

    private static function integer(string $input, int $min, int $max, string $label): int
    {
        $value = filter_var($input,FILTER_VALIDATE_INT,['options'=>['min_range'=>$min,'max_range'=>$max]]);
        if ($value === false) {
            throw new Problem("$label: enter a whole number between $min and $max.");
        }
        return $value;
    }

    public static function money(string $input, string $currency): array
    {
        // The local UI supports the following minor-unit exponents.
        $scale = match ($currency) {
            'JPY','KRW','CLP','VND','XAF','XOF','XPF','BIF','DJF','GNF','KMF','PYG','RWF','UGX','VUV','ISK'=>0,
            'BHD','IQD','JOD','KWD','LYD','OMR','TND'=>3,
            'CLF','UYW'=>4,
            default=>2,
        };
        if (!preg_match('/^(\d{1,9})(?:\.(\d{1,4}))?$/D',$input,$parts) || strlen($parts[2] ?? '') > $scale) {
            throw new Problem("Enter an amount with up to $scale decimal places.");
        }
        $minor = (int)($parts[1] . str_pad($parts[2] ?? '',$scale,'0'));
        return ['minor'=>$minor,'currency'=>$currency,'scale'=>$scale];
    }

    public static function decimal(array $value): string
    {
        $scale = $value['scale'];
        $text = str_pad((string)$value['minor'],$scale+1,'0',STR_PAD_LEFT);
        return $scale === 0 ? $text : substr($text,0,-$scale) . '.' . substr($text,-$scale);
    }

    private static function upload(array $file): array
    {
        $path = $file['tmp_name'];
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($path) || $file['size'] > 10*1024*1024 || $file['size'] < 1) {
            throw new Problem('The file did not upload successfully. The maximum size is 10 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!in_array($mime,['image/png','image/jpeg','image/gif','image/webp','application/pdf'],true)) {
            throw new Problem('Choose a PNG, JPEG, GIF, WebP or PDF file.');
        }
        $bytes = file_get_contents($path);
        if ($bytes === false || ($mime === 'application/pdf' ? !str_starts_with($bytes,'%PDF-') : getimagesizefromstring($bytes) === false)) {
            throw new Problem('The file contents do not match the file type.');
        }
        return [['mime'=>$mime,'size'=>strlen($bytes),'sha256'=>hash('sha256',$bytes)],$bytes];
    }
}

