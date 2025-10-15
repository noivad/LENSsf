<?php

declare(strict_types=1);

class EventManager
{
    private const STORE_KEY = 'events';

    public function __construct(private readonly DataStore $store)
    {
    }

    public function all(): array
    {
        $events = $this->store->load(self::STORE_KEY);

        usort($events, static function (array $a, array $b) {
            $dateComparison = strcmp($a['event_date'] ?? '', $b['event_date'] ?? '');
            if ($dateComparison === 0) {
                return strcmp($a['start_time'] ?? '', $b['start_time'] ?? '');
            }

            return $dateComparison;
        });

        return $events;
    }

    public function create(array $data): array
    {
        $events = $this->store->load(self::STORE_KEY);
        $id = generate_id('evt_');

        $event = [
            'id' => $id,
            'title' => trim($data['title'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'event_date' => $data['event_date'] ?? '',
            'start_time' => $data['start_time'] ?? '',
            'venue_id' => $data['venue_id'] ?? null,
            'venue_name' => $data['venue_name'] ?? null,
            'owner' => trim($data['owner'] ?? ''),
            'deputies' => $data['deputies'] ?? [],
            'shared_with' => [],
            'calendar_entries' => [],
            'created_at' => date('c'),
        ];

        $events[] = $event;
        $this->store->save(self::STORE_KEY, $events);

        return $event;
    }

    public function addCalendarEntry(string $eventId, string $name): void
    {
        $name = trim($name);

        if ($name === '') {
            return;
        }

        $events = $this->store->load(self::STORE_KEY);

        foreach ($events as &$event) {
            if ($event['id'] !== $eventId) {
                continue;
            }

            $event['calendar_entries'][] = [
                'id' => generate_id('cal_'),
                'name' => $name,
                'added_at' => date('c'),
            ];

            $this->store->save(self::STORE_KEY, $events);

            return;
        }
    }

    public function share(string $eventId, array $people): void
    {
        if ($people === []) {
            return;
        }

        $events = $this->store->load(self::STORE_KEY);

        foreach ($events as &$event) {
            if ($event['id'] !== $eventId) {
                continue;
            }

            $shareList = $event['shared_with'] ?? [];
            foreach ($people as $person) {
                $person = trim($person);
                if ($person === '') {
                    continue;
                }

                if (!in_array($person, $shareList, true)) {
                    $shareList[] = $person;
                }
            }

            $event['shared_with'] = $shareList;
            $this->store->save(self::STORE_KEY, $events);

            return;
        }
    }

    public function attachVenueName(array $events, array $venues): array
    {
        $venueLookup = [];
        foreach ($venues as $venue) {
            $venueLookup[$venue['id']] = $venue;
        }

        foreach ($events as &$event) {
            $venueId = $event['venue_id'] ?? null;
            $event['venue_name'] = $venueId && isset($venueLookup[$venueId])
                ? $venueLookup[$venueId]['name']
                : ($event['venue_name'] ?? null);
        }

        return $events;
    }

    public function upcoming(int $limit = 5): array
    {
        $events = array_filter($this->all(), static function (array $event): bool {
            if (empty($event['event_date'])) {
                return false;
            }

            return strtotime($event['event_date']) >= strtotime('today');
        });

        return array_slice($events, 0, $limit);
    }
}
