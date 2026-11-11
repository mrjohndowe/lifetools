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
        'Business'=>['business_inventory','income_entries','delivery_shifts','appointments','work_orders','work_order_items','work_order_deliveries'],
        'Life admin'=>['meal_plans','recipes','work_shifts','daily_logs','journal_entries','intimacy_events','incidents','notes'],
        'Files'=>['attachments'],
        'Websites'=>['websites'],
    ];

    public const LABELS = [
        'events'=>'events','contacts'=>'contacts','personal_profile'=>'personal_profile','phone_history'=>'past_phones',
        'email_history'=>'past_emails','accounts'=>'managed_accounts','bank_accounts'=>'bank_accounts','cards'=>'cards',
        'vehicles'=>'vehicles','vehicle_documents'=>'vehicle_records','receipts'=>'receipts','chores'=>'chores_cleaning',
        'lists'=>'lists','list_items'=>'list_items','inventory_items'=>'household_inventory','documents'=>'documents',
        'music_tracks'=>'tracks','playlists'=>'playlists','playlist_items'=>'playlist_tracks','business_inventory'=>'business_inventory',
        'income_entries'=>'income','delivery_shifts'=>'delivery_rideshare','appointments'=>'appointments','work_orders'=>'work_orders','work_order_items'=>'order_items',
        'work_order_deliveries'=>'deliveries','meal_plans'=>'meal_plans','recipes'=>'recipes','work_shifts'=>'work_shifts',
        'daily_logs'=>'daily_log','journal_entries'=>'private_journal','intimacy_events'=>'intimacy_journal',
        'incidents'=>'incidents','notes'=>'notes','attachments'=>'files_photos','websites'=>'websites',
    ];

    public const QUESTIONS = [
        'what_type_of_encounter_or_activity',
        'was_the_encounter_consensual_and_mutually',
        'did_everyone_involved_feel_comfortable_participating',
        'how_would_you_rate_the_overall',
        'how_would_you_rate_your_emotional',
        'how_would_you_rate_the_communication',
        'how_would_you_rate_the_level',
        'how_satisfied_were_you_with_the',
        'how_would_you_describe_your_mood',
        'how_would_you_describe_your_mood_afterward',
        'what_did_you_particularly_enjoy',
        'was_there_anything_you_disliked_or',
        'were_any_boundaries_discussed_or_respected',
        'was_there_anything_new_you_learned',
        'is_there_anything_you_would_like',
        'are_there_any_additional_notes_you',
    ];

    public static function label(string $kind): string
    {
        return ltText(self::LABELS[$kind]);
    }

    public static function labels(): array
    {
        return array_map(ltText(...), self::LABELS);
    }

    public static function fields(string $kind, array $prefs = []): array
    {
        $contact = ['link',ltText('contact'),['contacts']];
        $file = ['links',ltText('files_or_photos'),['attachments']];
        $notes = ['private',ltText('private_notes')];
        $tags = ['text',ltText('tags_comma_separated')];
        $work = ['select',ltText('status'),['quoted','paid','in progress','delivered','refunded','files deleted']];
        $holder = [
            'holder'=>['text',ltText('account_holder')], 'dob'=>['date',ltText('date_of_birth')],
            'email'=>['email',ltText('email')],'phone'=>['text',ltText('phone')],
        ];
        $bank = [
            'holder'=>['secret',ltText('account_holder')],'dob'=>['secret',ltText('date_of_birth')],
            'email'=>['secret',ltText('email')],'phone'=>['secret',ltText('phone')],
            'institution'=>['text',ltText('institution')], 'subtype'=>['select',ltText('account_type'),['checking','savings','credit','debit','investment','other']],
            'ssn'=>['secret',ltText('social_security_number')], 'password'=>['secret',ltText('online_banking_password')],
            'pin'=>['secret',ltText('pin')], 'number'=>['secret',ltText('account_number')],'routing'=>['secret',ltText('routing_number')],
            'card'=>['secret',ltText('card_number')],'expiry'=>['month',ltText('card_expiry')],'card_type'=>['text',ltText('card_network_or_type')],
            'username'=>['secret',ltText('username')],'contact'=>$contact,'notes'=>$notes,
        ];
        return match ($kind) {
            'events'=>[
                'description'=>['area',ltText('description')], 'category'=>['select',ltText('category'),['rental','vehicle','medical','work','legal','personal','business']],
                'status'=>['select',ltText('status'),['planned','done','cancelled']], 'all_day'=>['check',ltText('all_day')],
                'start'=>['eventtime',ltText('starts')], 'end'=>['eventtime',ltText('ends_optional')], 'zone'=>['zone',ltText('event_time_zone')],
                'location'=>['text',ltText('location')], 'frequency'=>['select',ltText('repeat'),['daily','weekly','monthly','yearly']],
                'interval'=>['positive',ltText('repeat_every')], 'weekdays'=>['text',ltText('weekdays_1_monday_to_7_sunday')],
                'until'=>['date',ltText('repeat_through')], 'count'=>['positive',ltText('maximum_occurrences')],
                'skips'=>['text',ltText('skip_dates_yyyy_mm_dd_separated')],
                'overrides'=>['area',ltText('overrides_original_yyyy_mm_dd_new')],
                'reminders'=>['text',ltText('reminders_minutes_before_separated_by_commas')],
                'contact'=>$contact, 'related'=>['links',ltText('related_records'),['vehicles','accounts','documents','work_orders']],
                'attachments'=>$file, 'tags'=>$tags,
            ],
            'contacts'=>[
                'first'=>['text',ltText('first_name')],'middle'=>['text',ltText('middle_name')],'last'=>['text',ltText('last_name')],
                'dob'=>['date',ltText('birthday')],'email'=>['email',ltText('email')],'phone'=>['text',ltText('primary_phone')],
                'phone2'=>['text',ltText('secondary_phone')],'street'=>['text',ltText('street')],'city'=>['text',ltText('city')],
                'state'=>['text',ltText('state')],'postal'=>['text',ltText('postal_code')],
                'relationship'=>['select',ltText('relationship'), self::options($prefs['relationships'] ?? '', [
                    'Mother','Father','Brother','Sister','Son','Daughter','Friend','Best Friend','Family','Work','Girlfriend',
                    'Boyfriend','Partner','Ex','Fling','Friends with Benefits','One Night Stand','Other'])],
                'organization'=>['text',ltText('organization')],'job'=>['text',ltText('job_title')],'notes'=>$notes,'tags'=>$tags,
            ],
            'personal_profile'=>[
                'legal'=>['text',ltText('legal_name')],'dob'=>['date',ltText('date_of_birth')],'ssn'=>['secret',ltText('social_security_number')],
                'license'=>['secret',ltText('drivers_license')],'phone'=>['text',ltText('current_phone')],'email'=>['email',ltText('current_email')],'tags'=>$tags,
            ],
            'phone_history','email_history'=>['start'=>['date',ltText('from')],'end'=>['date',ltText('through')],'notes'=>$notes],
            'accounts'=>$holder + [
                'username'=>['secret',ltText('username')],'password'=>['secret',ltText('password')],'recovery_email'=>['secret',ltText('recovery_email')],
                'recovery_phone'=>['secret',ltText('recovery_phone')],'question'=>['secret',ltText('security_questions')],
                'answer'=>['secret',ltText('security_answers')],'codes'=>['secret',ltText('recovery_codes')],'url'=>['url',ltText('service_url')],
                'type'=>['select',ltText('account_type'),self::options($prefs['account_types'] ?? '', ['Websites','Email','Social media','Gaming',
                    'Subscriptions','Banking','Credit or debit cards','Investments','Utilities','Other'])],
                'contact'=>$contact,'notes'=>$notes,'tags'=>$tags,
            ],
            'bank_accounts','cards'=>$bank,
            'vehicles'=>[
                'year'=>['positive',ltText('year')],'make'=>['text',ltText('make')],'model'=>['text',ltText('model')],'vin'=>['secret',ltText('vin')],
                'title_info'=>['text',ltText('title_details')],'registration'=>['secret',ltText('registration_number')],
                'registration_expires'=>['date',ltText('registration_expires')],'insurer'=>['text',ltText('insurer')],
                'policy'=>['secret',ltText('policy_number')],'insurance_expires'=>['date',ltText('insurance_expires')],'attachments'=>$file,'notes'=>$notes,
            ],
            'vehicle_documents'=>[
                'vehicle'=>['link',ltText('vehicle'),['vehicles']], 'type'=>['select',ltText('type'),['title','registration','insurance','work order','manual','maintenance']],
                'date'=>['date',ltText('date')],'cost'=>['money',ltText('cost')],'shop'=>['text',ltText('shop')],'description'=>['area',ltText('description')],'attachments'=>$file,
            ],
            'receipts'=>['merchant'=>['text',ltText('merchant')],'date'=>['date',ltText('purchase_date')],'amount'=>['money',ltText('amount')],'category'=>['text',ltText('category')],'attachments'=>$file],
            'chores'=>['due'=>['date',ltText('due_date')],'frequency'=>['select',ltText('repeat'),['daily','weekly','monthly','yearly']],
                'interval'=>['positive',ltText('repeat_every')],'done'=>['check',ltText('done')],'notes'=>$notes],
            'lists'=>['type'=>['select',ltText('list_type'),['general','shopping','grocery','packing']],'notes'=>$notes],
            'list_items'=>['list'=>['link',ltText('list'),['lists']],'quantity'=>['number',ltText('quantity')],'category'=>['text',ltText('category')],
                'done'=>['check',ltText('done')],'position'=>['number',ltText('order')]],
            'inventory_items'=>['room'=>['text',ltText('room')],'quantity'=>['number',ltText('quantity')],'value'=>['money',ltText('value')],'attachments'=>$file],
            'documents'=>['category'=>['select',ltText('category'),['receipts','tax docs','day stubs','journals','manuals','identity documents','other']],
                'issue'=>['date',ltText('issue_date')],'expires'=>['date',ltText('expiry_date')],'tags'=>$tags,'attachments'=>$file,'notes'=>$notes],
            'music_tracks'=>['date'=>['date',ltText('release_date')],'artist'=>['text',ltText('artist')],'features'=>['text',ltText('featured_artists')],
                'album'=>['text',ltText('album')],'genre'=>['text',ltText('genre')],'favorite'=>['check',ltText('favorite')],'url'=>['url',ltText('external_link')]],
            'playlists'=>['notes'=>['area',ltText('playlist_notes')]],
            'playlist_items'=>['playlist'=>['link',ltText('playlist'),['playlists']],'track'=>['link',ltText('track'),['music_tracks']],'position'=>['number',ltText('order')]],
            'business_inventory'=>['sku'=>['text',ltText('sku')],'quantity'=>['number',ltText('quantity')],'cost'=>['money',ltText('cost')],'price'=>['money',ltText('price')],
                'location'=>['text',ltText('stock_location')],'threshold'=>['number',ltText('low_stock_threshold')]],
            'income_entries'=>['date'=>['date',ltText('date')],'contact'=>$contact,'client'=>['suggestion',ltText('client')],'description'=>['area',ltText('description')],
                'amount'=>['money',ltText('amount')],'method'=>['text',ltText('payment_method')],'status'=>['select',ltText('status'),['pending','paid','refunded']]],
            'delivery_shifts'=>[
                'client'=>['suggestion',ltText('client')],
                'shift_start'=>['shift-time',ltText('start_time')],'shift_end'=>['shift-time',ltText('end_time')],
                'active_minutes'=>['number',ltText('delivery_active_minutes')],
                'dash_minutes'=>['computed',ltText('delivery_dash_minutes')],
                'offers'=>['number',ltText('delivery_offers')],'deliveries'=>['number',ltText('delivery_count')],
                'pickup'=>['suggestion',ltText('delivery_pickup')],
                'doordash_pay'=>['money',ltText('delivery_pay')],'customer_tips'=>['money',ltText('delivery_tips')],
            ],
            'appointments'=>['time'=>['datetime',ltText('starts')],'contact'=>$contact,'client'=>['text',ltText('client')],'service'=>['text',ltText('service')],
                'status'=>['select',ltText('status'),['planned','done','cancelled']]],
            'work_orders'=>['date'=>['date',ltText('order_date')],'contact'=>$contact,'client'=>['text',ltText('client')],
                'discount_type'=>['select',ltText('discount_type'),['bought at a discount','given discount']],
                'discount'=>['money',ltText('discount')],'amount'=>['money',ltText('amount')],'status'=>$work,'attachments'=>$file],
            'work_order_items','work_order_deliveries'=>['order'=>['link',ltText('work_order'),['work_orders']],
                'duration'=>['positive',ltText('duration_minutes')],'quantity'=>['positive',ltText('quantity')],'sent'=>['date',ltText('sent_date')],
                'amount'=>['money',ltText('amount')],'files_deleted'=>['check',ltText('files_deleted')],'attachments'=>$file],
            'recipes'=>['ingredients'=>['area',ltText('ingredients')],'steps'=>['area',ltText('steps')],'tags'=>$tags],
            'meal_plans'=>['date'=>['date',ltText('meal_date')],'meal'=>['select',ltText('meal'),['breakfast','lunch','dinner','snack']],
                'recipe'=>['link',ltText('recipe'),['recipes']],'notes'=>$notes],
            'work_shifts'=>['time'=>['datetime',ltText('starts')],'end'=>['datetime',ltText('ends')],'role'=>['text',ltText('role')],'pay'=>['money',ltText('pay')]],
            'daily_logs','journal_entries'=>['time'=>['datetime',ltText('entry_date_and_time')],'category'=>['text',ltText('category')],
                'content'=>['private',ltText('entry_text')],'contact'=>$contact,'tags'=>$tags,'rating'=>['rating',ltText('mood_1_low_to_10_high')],
                'notes'=>$notes,'attachments'=>$file],
            'intimacy_events'=>self::intimacy($prefs),
            'incidents'=>['time'=>['datetime',ltText('date_and_time')],'people'=>['links',ltText('people_involved'),['contacts']],
                'location'=>['text',ltText('location')],'content'=>['private',ltText('description')],'severity'=>['select',ltText('severity'),['low','medium','high']],
                'follow_up'=>['select',ltText('follow_up'),['open','monitoring','resolved']],'attachments'=>$file,'notes'=>$notes],
            'notes'=>['content'=>['private',ltText('note')],'tags'=>$tags],
            'attachments'=>['upload'=>['upload',ltText('image_or_pdf')],'notes'=>$notes],
            'websites'=>['url'=>['url',ltText('website_url')],'category'=>['select',ltText('category'),['Planner','Organize','Accounts','Banks','Finances','Bills',
                'Personal','Family','Vehicles','Medical','Work']], 'notes'=>['area',ltText('notes')],'favorite'=>['check',ltText('favorite')],
                'account'=>['link',ltText('managed_account'),['accounts']]],
            default=>throw new Problem(ltText('unknown_module')),
        };
    }

    private static function intimacy(array $prefs): array
    {
        $fields = [
            'date'=>['date',ltText('event_date')],'start'=>['clock',ltText('start_time')],'end'=>['clock',ltText('end_time')],'duration'=>['positive',ltText('duration_minutes')],
            'people'=>['links',ltText('associated_contacts'),['contacts']],
            'type'=>['select',ltText('event_type'),self::options($prefs['intimacy_types'] ?? '', ['Date','Romantic encounter','Affectionate activity',
                'Intimate encounter','Sexual activity','Other'])],
            'privacy'=>['select',ltText('privacy_level'),['Owner only','Extra private']],
            'tags'=>['text',ltText('tags_comma_separated')],
        ];
        foreach ([
            'location'=>ltText('location_or_setting'),'content'=>ltText('general_description'),'notes'=>ltText('personal_notes'),'activity'=>ltText('activity_details'),
            'preferences'=>ltText('preferences'),'boundaries'=>ltText('boundaries'),'communication_notes'=>ltText('communication_notes'),
            'reactions'=>ltText('emotional_reactions'),'reflection'=>ltText('personal_reflections'),'context'=>ltText('relationship_context'),
            'follow_up'=>ltText('follow_up_notes'),'custom'=>ltText('custom_questions_and_answers'),
        ] as $key=>$label) {
            $fields[$key] = ['private',$label];
        }
        foreach (self::QUESTIONS as $index=>$question) {
            $question = ltText($question);
            $fields['question_' . ($index+1)] = match ($index) {
                1,2=>['private-choice',$question,['Yes','No','Unknown','Not recorded']],
                3,4,5,6,7=>['private-rating',$question],
                default=>['private',$question],
            };
        }
        foreach ([ltText('overall_experience'),ltText('enjoyment'),ltText('emotional_connection'),ltText('communication'),ltText('comfort'),ltText('trust'),ltText('satisfaction'),ltText('after_event_feelings')] as $index=>$label) {
            $fields['rating_' . ($index+1)] = ['private-rating',ltText('label_1_low_to_10_high',['label'=>$label])];
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
            throw new Problem(ltText('invalid_save_request_reload_the_editor'));
        }
        return $store->transaction(function () use ($store,$owner,$kind,$input,$prefs,$file,$request): array {
            if ($request !== '') {
                $store->run('DELETE FROM save_receipts WHERE expires<?',[time()]);
                $receipt = $store->run('SELECT kind,id,revision,updated_at FROM save_receipts WHERE owner=? AND request=?',
                    [$owner,$request])->fetch();
                if ($receipt) {
                    if ($receipt['kind'] !== $kind) {
                        throw new Problem(ltText('invalid_save_request_reload_the_editor'));
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
            throw new Problem(ltText('this_record_changed_in_another_view'));
        }
        if ($kind === 'personal_profile' && !$old && $store->run(
            "SELECT 1 FROM records WHERE owner=? AND kind='personal_profile' AND deleted_at IS NULL",[$owner])->fetchColumn()) {
            throw new Problem(ltText('edit_the_existing_personal_profile'));
        }
        $title = self::text($input['title'] ?? '', 180);
        if ($kind === 'contacts' && $title === '') {
            $title = trim(self::text($input['first'] ?? '',80) . ' ' . self::text($input['last'] ?? '',80));
        }
        if ($kind === 'intimacy_events') {
            $title = ltText('private_event');
        }
        if ($kind === 'delivery_shifts') {
            $title = self::text($input['client'] ?? '',180) ?: ltText('delivery_shift');
        }
        if ($title === '') {
            throw new Problem(ltText('enter_a_title_or_display_name'));
        }
        $p = ['_links'=>[]];
        $private = [];
        $fields = self::fields($kind,$prefs);
        foreach ($fields as $field=>[$type,$label]) {
            $options = $fields[$field][2] ?? [];
            if ($type === 'upload' || $type === 'computed') {
                continue;
            }
            if ($type === 'link' || $type === 'links') {
                $ids = $type === 'link' ? [$input[$field] ?? ''] : ($input[$field] ?? []);
                if (!is_array($ids) || count($ids) > 40) {
                    throw new Problem(ltText('label_choose_up_to_40_records',['label'=>$label]));
                }
                foreach (array_unique(array_filter($ids)) as $target) {
                    $linked = $store->record($owner, self::text($target,36));
                    if (!in_array($linked['kind'],$options,true)) {
                        throw new Problem(ltText('label_this_record_type_cannot_be',['label'=>$label]));
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
                        throw new Problem(ltText('email_enter_a_valid_email'));
                    }
                    if ($field === 'card' && !preg_match('/^(?:[0-9][ -]?){12,18}[0-9]$/D',$value)) {
                        throw new Problem(ltText('card_number_enter_13_19_digits'));
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
                    throw new Problem(ltText('label_select_a_listed_answer',['label'=>$label]));
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
                'month'=>preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D',$value) ? $value : throw new Problem(ltText('label_enter_a_valid_month',['label'=>$label])),
                'clock'=>preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$value) ? $value : throw new Problem(ltText('label_enter_a_valid_time',['label'=>$label])),
                'number'=>self::integer($value,0,1000000000,$label),
                'positive'=>self::integer($value,1,$field === 'interval' ? 365 : 1000000,$label),
                'rating'=>self::integer($value,1,10,$label),
                'money'=>self::money($value,$old ? (Store::payload($old)[$field]['currency'] ?? $prefs['currency']) : $prefs['currency']),
                'select'=>in_array($value,$options,true) ? $value : throw new Problem(ltText('label_choose_a_listed_value',['label'=>$label])),
                'zone'=>in_array($value,DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC),true)
                    ? $value : throw new Problem(ltText('label_choose_a_valid_iana_time',['label'=>$label])),
                'email'=>filter_var($value,FILTER_VALIDATE_EMAIL) ? $value : throw new Problem(ltText('label_enter_a_valid_email',['label'=>$label])),
                'url'=>filter_var($value,FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($value,PHP_URL_SCHEME)),['http','https'],true)
                    && parse_url($value,PHP_URL_USER) === null
                    ? $value : throw new Problem(ltText('label_enter_an_http_or_https',['label'=>$label])),
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
        if ($kind === 'delivery_shifts') {
            $clock = new DateTimeZone('UTC');
            $start = Planner::local($p['shift_start'],$clock);
            $end = Planner::local($p['shift_end'],$clock);
            $p['dash_minutes'] = intdiv($end->getTimestamp() - $start->getTimestamp(),60);
            if ($p['dash_minutes'] < 0 || (int)$p['active_minutes'] > $p['dash_minutes']) {
                throw new Problem(ltText('delivery_invalid_duration'));
            }
            $p['_starts'] = $start->format('Y-m-d');
            $currency = $p['doordash_pay']['currency'] ?? $p['customer_tips']['currency'] ?? $prefs['currency'];
            foreach (['doordash_pay','customer_tips'] as $field) {
                if ($p[$field] === '') {
                    $p[$field] = self::money('0',$currency);
                }
            }
        }
        if ($kind === 'attachments') {
            if ($file && (int)$file['error'] !== UPLOAD_ERR_NO_FILE) {
                [$metadata,$bytes] = self::upload($file);
                $p += $metadata;
                $private['file'] = $bytes;
            } elseif ($old) {
                $p += array_intersect_key(Store::payload($old),array_flip(['mime','size','sha256']));
            } else {
                throw new Problem(ltText('choose_an_image_or_pdf_to'));
            }
        }
        return $store->save($owner,$kind,$title,$p,$private,$old,$seedId);
    }

    public static function text(mixed $input, int $max, bool $preserveWhitespace = false): string
    {
        if (!is_string($input) && !is_int($input)) {
            throw new Problem(ltText('a_field_has_an_invalid_value'));
        }
        $value = $preserveWhitespace ? (string)$input : trim((string)$input);
        if (!mb_check_encoding($value,'UTF-8') || mb_strlen($value) > $max || str_contains($value,"\0")) {
            throw new Problem(ltText('a_field_exceeds_max_characters_or',['max'=>$max]));
        }
        return $value;
    }

    private static function integer(string $input, int $min, int $max, string $label): int
    {
        $value = filter_var($input,FILTER_VALIDATE_INT,['options'=>['min_range'=>$min,'max_range'=>$max]]);
        if ($value === false) {
            throw new Problem(ltText('label_enter_a_whole_number_between',['label'=>$label,'min'=>$min,'max'=>$max]));
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
            throw new Problem(ltText('enter_an_amount_with_up_to',['scale'=>$scale]));
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
            throw new Problem(ltText('the_file_did_not_upload_successfully'));
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
        if (!in_array($mime,['image/png','image/jpeg','image/gif','image/webp','application/pdf'],true)) {
            throw new Problem(ltText('choose_a_png_jpeg_gif_webp'));
        }
        $bytes = file_get_contents($path);
        if ($bytes === false || ($mime === 'application/pdf' ? !str_starts_with($bytes,'%PDF-') : getimagesizefromstring($bytes) === false)) {
            throw new Problem(ltText('the_file_contents_do_not_match'));
        }
        return [['mime'=>$mime,'size'=>strlen($bytes),'sha256'=>hash('sha256',$bytes)],$bytes];
    }
}

