<?php

declare(strict_types=1);

class EventManager
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT e.*, v.name AS venue_name
             FROM events e
             LEFT JOIN venues v ON e.venue_id = v.id
             ORDER BY e.event_date ASC, e.event_time ASC'
        );

        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$events) {
            return [];
        }

        $eventIds = array_map(static fn (array $event): int => (int) $event['id'], $events);

        $calendarEntries = $this->fetchCalendarEntries($eventIds);
        $shares = $this->fetchStringRelations('event_shares', 'event_id', 'shared_with', $eventIds);

        return array_map(function (array $event) use ($calendarEntries, $shares): array {
            $id = (int) $event['id'];
            $deputies = json_decode($event['deputies'] ?? '[]', true) ?: [];

            return [
                'id' => $id,
                'title' => $event['title'],
                'description' => $event['description'],
                'event_date' => $event['event_date'],
                'start_time' => $event['event_time'],
                'venue_id' => $event['venue_id'],
                'venue_name' => $event['venue_name'],
                'owner' => $event['owner_name'],
                'deputies' => $deputies,
                'calendar_entries' => $calendarEntries[$id] ?? [],
                'shared_with' => $shares[$id] ?? [],
                'created_at' => $event['created_at'],
            ];
        }, $events);
    }

    public function upcoming(int $limit = 5): array
    {
        $today = new DateTimeImmutable('today');

        $events = array_filter($this->all(), static function (array $event) use ($today): bool {
            if (empty($event['event_date'])) {
                return false;
            }

            $eventDate = DateTimeImmutable::createFromFormat('Y-m-d', $event['event_date']);

            return $eventDate instanceof DateTimeImmutable && $eventDate >= $today;
        });

        return array_slice(array_values($events), 0, $limit);
    }

    public function create(array $data): ?array
    {
        $title = trim($data['title'] ?? '');
        $eventDate = $data['event_date'] ?? null;
        $owner = trim($data['owner'] ?? '');

        if ($title === '' || !$eventDate || $owner === '') {
            return null;
        }

        $description = trim($data['description'] ?? '') ?: null;
        $startTime = $data['start_time'] ?: null;
        $venueId = $data['venue_id'] ?: null;
        $deputies = array_map('trim', $data['deputies'] ?? []);
        $deputies = array_values(array_filter(array_unique($deputies)));

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                'INSERT INTO events (title, description, event_date, event_time, venue_id, owner_name, deputies)
                 VALUES (:title, :description, :event_date, :event_time, :venue_id, :owner_name, :deputies)'
            );

            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':event_date' => $eventDate,
                ':event_time' => $startTime ?: null,
                ':venue_id' => $venueId ?: null,
                ':owner_name' => $owner,
                ':deputies' => json_encode($deputies, JSON_THROW_ON_ERROR),
            ]);

            $eventId = (int) $this->db->lastInsertId();

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $this->findById($eventId);
    }

    public function addCalendarEntry(string $eventId, string $name): void
    {
        $name = trim($name);
        $eventId = trim($eventId);

        if ($eventId === '' || $name === '') {
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO event_calendar_entries (event_id, name) VALUES (:event_id, :name)'
        );

        try {
            $stmt->execute([
                ':event_id' => (int) $eventId,
                ':name' => $name,
            ]);
        } catch (PDOException $e) {
            if (!$this->isDuplicateException($e)) {
                throw $e;
            }
        }
    }

    public function share(string $eventId, array $people): void
    {
        $eventId = trim($eventId);
        if ($eventId === '') {
            return;
        }

        $people = array_values(array_filter(array_unique(array_map('trim', $people))));
        if ($people === []) {
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO event_shares (event_id, shared_with) VALUES (:event_id, :person)'
        );

        foreach ($people as $person) {
            if ($person === '') {
                continue;
            }

            try {
                $stmt->execute([
                    ':event_id' => (int) $eventId,
                    ':person' => $person,
                ]);
            } catch (PDOException $e) {
                if (!$this->isDuplicateException($e)) {
                    throw $e;
                }
            }
        }
    }

    public function findById(int $eventId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, v.name AS venue_name
             FROM events e
             LEFT JOIN venues v ON e.venue_id = v.id
             WHERE e.id = :id'
        );

        $stmt->execute([':id' => $eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            return null;
        }

        $deputies = json_decode($event['deputies'] ?? '[]', true) ?: [];

        return [
            'id' => (int) $event['id'],
            'title' => $event['title'],
            'description' => $event['description'],
            'event_date' => $event['event_date'],
            'start_time' => $event['event_time'],
            'venue_id' => $event['venue_id'],
            'venue_name' => $event['venue_name'],
            'owner' => $event['owner_name'],
            'deputies' => $deputies,
            'calendar_entries' => $this->fetchCalendarEntries([$eventId])[$eventId] ?? [],
            'shared_with' => $this->fetchStringRelations('event_shares', 'event_id', 'shared_with', [$eventId])[$eventId] ?? [],
            'created_at' => $event['created_at'],
        ];
    }

    private function fetchStringRelations(string $table, string $keyColumn, string $valueColumn, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            sprintf('SELECT %s, %s FROM %s WHERE %s IN (%s)', $keyColumn, $valueColumn, $table, $keyColumn, $placeholders)
        );

        $stmt->execute(array_values($ids));

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = (int) $row[$keyColumn];
            $result[$key][] = $row[$valueColumn];
        }

        return $result;
    }

    private function fetchCalendarEntries(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            sprintf('SELECT id, event_id, name, added_at FROM event_calendar_entries WHERE event_id IN (%s) ORDER BY added_at ASC', $placeholders)
        );

        $stmt->execute(array_values($ids));

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $key = (int) $row['event_id'];
            $result[$key][] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'added_at' => $row['added_at'],
            ];
        }

        return $result;
    }

    private function isDuplicateException(PDOException $e): bool
    {
        $code = $e->getCode();

        return $code === '23000' || $code === '23505';
    }
}
