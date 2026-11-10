<?php
declare(strict_types=1);

final class Planner
{
    public static function date(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
        if (!$date || $date->format('Y-m-d') !== $value || (int)$date->format('Y') < 1) {
            throw new Problem('Enter a valid date.');
        }
        return $date;
    }

    public static function local(string $value, DateTimeZone $zone): DateTimeImmutable
    {
        $time = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, $zone);
        if (!$time || $time->format('Y-m-d\TH:i') !== $value || (int)$time->format('Y') < 1) {
            throw new Problem('Enter a valid local time. The selected time may fall inside a daylight-saving gap.');
        }
        return $time;
    }

    public static function utc(DateTimeImmutable $time): string
    {
        return $time->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    public static function validate(array $p): array
    {
        $zone = new DateTimeZone($p['zone'] ?: 'UTC');
        $allDay = (bool)$p['all_day'];
        $start = $allDay ? self::date(substr($p['start'],0,10)) : self::local($p['start'],$zone);
        $end = $p['end'] === '' ? $start
            : ($allDay ? self::date(substr($p['end'],0,10)) : self::local($p['end'],$zone));
        if ($end < $start) {
            throw new Problem('The event end must follow the start.');
        }
        if ($p['until'] !== '' && $p['until'] < $start->format('Y-m-d')) {
            throw new Problem('The recurrence end date must follow the start.');
        }
        $p['_starts'] = $allDay ? $start->format('Y-m-d') : self::utc($start);
        $p['_ends'] = $allDay ? $end->format('Y-m-d') : self::utc($end);
        $p['interval'] = $p['interval'] === '' ? 1 : (int)$p['interval'];
        $p['_days'] = self::integers($p['weekdays'], 1, 7, 'Weekdays');
        $p['_offsets'] = self::integers($p['reminders'], 0, 43200, 'Reminder offsets');
        $p['_skips'] = [];
        foreach (self::split($p['skips']) as $date) {
            self::date($date);
            $p['_skips'][] = $date;
        }
        $p['_overrides'] = [];
        foreach (preg_split('/\R/', trim($p['overrides']), -1, PREG_SPLIT_NO_EMPTY) as $line) {
            $parts = array_map('trim', explode('=', $line, 2));
            if (count($parts) !== 2) {
                throw new Problem('Use one occurrence override per line in original-date = new-date/time format.');
            }
            [$original,$replacement] = $parts;
            self::date($original);
            $allDay ? self::date($replacement) : self::local($replacement,$zone);
            $p['_overrides'][$original] = $replacement;
        }
        return $p;
    }

    private static function split(string $text): array
    {
        return preg_split('/[\s,]+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
    }

    private static function integers(string $text, int $min, int $max, string $label): array
    {
        $values = [];
        foreach (self::split($text) as $part) {
            $value = filter_var($part, FILTER_VALIDATE_INT, ['options'=>['min_range'=>$min,'max_range'=>$max]]);
            if ($value === false) {
                throw new Problem("$label must be between $min and $max.");
            }
            $values[] = $value;
        }
        return array_values(array_unique($values));
    }

    public static function occurrences(array $event, string $from, string $through, string $displayZone): array
    {
        $p = Store::payload($event);
        $allDay = (bool)$p['all_day'];
        $zone = new DateTimeZone($allDay ? 'UTC' : ($p['zone'] ?: 'UTC'));
        $viewer = new DateTimeZone($displayZone);
        $initial = new DateTimeImmutable($event['starts'], $zone);
        $initial = $initial->setTimezone($zone);
        $initialDay = self::date($initial->format('Y-m-d'));
        $until = $p['until'];
        $countLimit = $p['count'] === '' ? PHP_INT_MAX : (int)$p['count'];
        $result = [];
        $seconds = (new DateTimeImmutable($event['ends'], $zone))->getTimestamp() - $initial->getTimestamp();
        $days = intdiv($seconds,86400);
        $rangeStart = self::date($from)->modify('-' . (max(0,$days) + 2) . ' days');
        $rangeEnd = self::date($through)->modify('+2 days');
        $overrides = $p['_overrides'];
        if ($overrides) {
            $rangeStart = min($rangeStart, self::date(min(array_keys($overrides))));
            $rangeEnd = max($rangeEnd, self::date(max(array_keys($overrides))));
        }
        $frequency = $p['frequency'];
        $interval = max(1,(int)$p['interval']);
        $weekdays = $p['_days'] ?: [(int)$initialDay->format('N')];
        $weekStart = $initialDay->modify('monday this week');
        $cursor = $initialDay;
        // Jump near the window for open-ended series. Count-limited rules count from DTSTART.
        if ($countLimit === PHP_INT_MAX && $frequency !== '' && $rangeStart > $cursor) {
            $cursor = $rangeStart;
        }
        if ($frequency === '') {
            $rangeEnd = $cursor;
        }
        $count = 0;
        for (; $cursor <= $rangeEnd; $cursor = $cursor->modify('+1 day')) {
            $day = $cursor->format('Y-m-d');
            if ($until !== '' && $frequency !== '' && $day > $until) {
                break;
            }
            $elapsed = (int)$initialDay->diff($cursor)->days;
            $months = 12 * ((int)$cursor->format('Y') - (int)$initialDay->format('Y'))
                + (int)$cursor->format('n') - (int)$initialDay->format('n');
            $matches = $cursor == $initialDay || match ($frequency) {
                'daily'=>$elapsed % $interval === 0,
                'weekly'=>intdiv((int)$weekStart->diff($cursor)->days,7) % $interval === 0
                    && in_array((int)$cursor->format('N'),$weekdays,true),
                'monthly'=>$months % $interval === 0 && $cursor->format('j') === $initialDay->format('j'),
                'yearly'=>$months % (12 * $interval) === 0 && $cursor->format('m-d') === $initialDay->format('m-d'),
                default=>false,
            };
            if (!$matches) {
                continue;
            }
            $originalTime = $day . 'T' . $initial->format('H:i');
            try {
                $start = $allDay ? $cursor : self::local($originalTime,$zone);
            } catch (Problem) {
                // Nonexistent wall times and invalid dates do not consume an occurrence.
                continue;
            }
            if (++$count > $countLimit) {
                break;
            }
            if (in_array($day,$p['_skips'],true) && !isset($overrides[$day])) {
                continue;
            }
            if (isset($overrides[$day])) {
                $start = $allDay ? self::date($overrides[$day]) : self::local($overrides[$day],$zone);
            }
            if ($allDay) {
                $end = $start->modify("+$days days");
            } else {
                $end = $start->setTimestamp($start->getTimestamp()+$seconds)->setTimezone($viewer);
                $start = $start->setTimezone($viewer);
            }
            if ($end->format('Y-m-d') < $from || $start->format('Y-m-d') > $through) {
                continue;
            }
            $result[] = ['id'=>$event['id'],'title'=>$event['title'],'original'=>$day,'start'=>$start,
                'end'=>$end,'allDay'=>$allDay,'status'=>$p['status'],'category'=>$p['category'],'offsets'=>$p['_offsets']];
        }
        return $result;
    }

    public static function range(Store $store, string $owner, string $from, string $through, string $zone): array
    {
        // UTC and local-date keys share a sortable prefix. Pad for timezone offsets.
        $low = self::date($from)->modify('-1 day')->format('Y-m-d');
        $high = self::date($through)->modify('+2 days')->format('Y-m-d');
        $query = $store->run("SELECT * FROM records WHERE owner=? AND kind='events' AND deleted_at IS NULL
            AND ((starts<? AND (ends>=? OR repeat=1)) OR (repeat=1 AND instr(payload,'=')>0))", [$owner,$high,$low]);
        $items = [];
        foreach ($query as $row) {
            foreach (self::occurrences($row,$from,$through,$zone) as $occurrence) {
                $items[] = $occurrence;
            }
        }
        usort($items, static fn($a,$b)=>$a['start'] <=> $b['start']);
        return $items;
    }

    public static function due(array $items, DateTimeImmutable $now): array
    {
        $today = $now->format('Y-m-d');
        $due = [];
        foreach ($items as $event) {
            if ($event['status'] !== 'planned' || $event['end']->format('Y-m-d') < $today) {
                continue;
            }
            $at = $event['allDay']
                ? new DateTimeImmutable($event['start']->format('Y-m-d').'T00:00',$now->getTimezone())
                : $event['start'];
            foreach ($event['offsets'] as $offset) {
                if ($at->getTimestamp()-$offset*60 <= $now->getTimestamp()) {
                    $due[] = $event;
                    break;
                }
            }
        }
        return $due;
    }
}

