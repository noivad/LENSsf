<?php

declare(strict_types=1);

class EventManager
{
    public function __construct(
        private readonly PDO $db,
        private readonly ?string $uploadPath = null
    ) {
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
            $tags = json_decode($event['tags'] ?? '[]', true) ?: [];

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
                'image' => $event['image'],
                'tags' => $tags,
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

    public function create(array $data, ?array $imageFile = null): ?array
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

        $tags = array_map('trim', $data['tags'] ?? []);
        $tags = array_values(array_filter(array_unique($tags)));

        $isRecurring = !empty($data['is_recurring']);
        $recurrencePattern = $this->buildRecurrencePattern($data);

        $imageName = null;
        if ($imageFile && $this->uploadPath) {
            $imageName = $this->handleImageUpload($imageFile);
        }

        try {
            $this->db->beginTransaction();

            $hasTags = $this->hasColumn('events', 'tags');
            $hasRecurrence = $this->hasColumn('events', 'is_recurring');

            $columns = ['title', 'description', 'event_date', 'event_time', 'venue_id', 'owner_name', 'deputies', 'image'];
            $params = [
                ':title' => $title,
                ':description' => $description,
                ':event_date' => $eventDate,
                ':event_time' => $startTime ?: null,
                ':venue_id' => $venueId ?: null,
                ':owner_name' => $owner,
                ':deputies' => json_encode($deputies, JSON_THROW_ON_ERROR),
                ':image' => $imageName,
            ];

            if ($hasTags) {
                $columns[] = 'tags';
                $params[':tags'] = json_encode($tags, JSON_THROW_ON_ERROR);
            }

            if ($hasRecurrence) {
                $columns[] = 'is_recurring';
                $columns[] = 'recurrence_pattern';
                $params[':is_recurring'] = $isRecurring;
                $params[':recurrence_pattern'] = $recurrencePattern ? json_encode($recurrencePattern, JSON_THROW_ON_ERROR) : null;
            }

            $placeholders = array_map(static fn(string $c) => ':' . $c, $columns);
            $sql = sprintf(
                'INSERT INTO events (%s) VALUES (%s)',
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $eventId = (int) $this->db->lastInsertId();

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            if ($imageName) {
                $this->deleteImage($imageName);
            }
            throw $e;
        }

        return $this->findById($eventId);
    }

    public function update(int $eventId, array $data, ?array $imageFile = null): ?array
    {
        if ($eventId <= 0) {
            return null;
        }

        $existing = $this->findById($eventId);
        if (!$existing) {
            return null;
        }

        $title = trim($data['title'] ?? '');
        $eventDate = $data['event_date'] ?? null;
        $owner = trim($data['owner'] ?? '');

        if ($title === '' || !$eventDate || $owner === '') {
            return null;
        }

        $description = trim($data['description'] ?? '') ?: null;
        $startTime = trim((string) ($data['start_time'] ?? '')) ?: null;
        $venueId = $data['venue_id'] ?: null;

        $deputies = array_map('trim', $data['deputies'] ?? []);
        $deputies = array_values(array_filter(array_unique($deputies)));

        $tags = array_map('trim', $data['tags'] ?? []);
        $tags = array_values(array_filter(array_unique($tags)));

        $newImage = null;
        if ($imageFile && $this->uploadPath) {
            $newImage = $this->handleImageUpload($imageFile) ?: null;
        }

        try {
            $this->db->beginTransaction();

            $assignments = [
                'title' => $title,
                'description' => $description,
                'event_date' => $eventDate,
                'event_time' => $startTime,
                'venue_id' => $venueId ?: null,
                'owner_name' => $owner,
                'deputies' => json_encode($deputies, JSON_THROW_ON_ERROR),
            ];

            if ($this->hasColumn('events', 'tags')) {
                $assignments['tags'] = json_encode($tags, JSON_THROW_ON_ERROR);
            }

            if ($newImage !== null) {
                $assignments['image'] = $newImage;
            }

            $setClauses = [];
            $params = [':id' => $eventId];
            foreach ($assignments as $column => $value) {
                $setClauses[] = sprintf('%s = :%s', $column, $column);
                $params[':' . $column] = $value;
            }

            $sql = sprintf('UPDATE events SET %s WHERE id = :id', implode(', ', $setClauses));
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $this->db->commit();

            if ($newImage !== null && !empty($existing['image']) && $existing['image'] !== $newImage) {
                $this->deleteImage($existing['image']);
            }
        } catch (Throwable $e) {
            $this->db->rollBack();
            if ($newImage !== null) {
                $this->deleteImage($newImage);
            }
            throw $e;
        }

        return $this->findById($eventId);
    }

    public function delete(int $eventId): bool
    {
        if ($eventId <= 0) {
            return false;
        }

        $event = $this->findById($eventId);
        if (!$event) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $this->db->prepare('DELETE FROM event_calendar_entries WHERE event_id = :id')->execute([':id' => $eventId]);
            $this->db->prepare('DELETE FROM event_shares WHERE event_id = :id')->execute([':id' => $eventId]);
            $this->db->prepare('DELETE FROM event_comments WHERE event_id = :id')->execute([':id' => $eventId]);
            $this->db->prepare('UPDATE photos SET event_id = NULL WHERE event_id = :id')->execute([':id' => $eventId]);
            $this->db->prepare('DELETE FROM events WHERE id = :id')->execute([':id' => $eventId]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        if (!empty($event['image'])) {
            $this->deleteImage($event['image']);
        }

        return true;
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

    public function unshare(string $eventId, string $person): void
    {
        $eventId = trim($eventId);
        $person = trim($person);
        if ($eventId === '' || $person === '') {
            return;
        }

        $stmt = $this->db->prepare(
            'DELETE FROM event_shares WHERE event_id = :event_id AND shared_with = :person'
        );
        $stmt->execute([
            ':event_id' => (int) $eventId,
            ':person' => $person,
        ]);
    }

    public function addDeputy(int $eventId, string $name): ?array
    {
        $name = trim($name);
        if ($eventId <= 0 || $name === '') {
            return null;
        }

        $event = $this->findById($eventId);
        if (!$event) {
            return null;
        }

        $deputies = $event['deputies'] ?? [];
        $deputies[] = $name;
        $deputies = array_values(array_filter(array_unique(array_map('trim', $deputies))));

        $stmt = $this->db->prepare('UPDATE events SET deputies = :deputies WHERE id = :id');
        $stmt->execute([
            ':deputies' => json_encode($deputies, JSON_THROW_ON_ERROR),
            ':id' => $eventId,
        ]);

        return $this->findById($eventId);
    }

    public function removeDeputy(int $eventId, string $name): ?array
    {
        $name = trim($name);
        if ($eventId <= 0 || $name === '') {
            return null;
        }

        $event = $this->findById($eventId);
        if (!$event) {
            return null;
        }

        $deputies = array_values(array_filter(($event['deputies'] ?? []), static fn(string $n): bool => $n !== $name));

        $stmt = $this->db->prepare('UPDATE events SET deputies = :deputies WHERE id = :id');
        $stmt->execute([
            ':deputies' => json_encode($deputies, JSON_THROW_ON_ERROR),
            ':id' => $eventId,
        ]);

        return $this->findById($eventId);
    }

    public function updateImage(int $eventId, array $imageFile): ?array
    {
        if ($eventId <= 0 || empty($imageFile)) {
            return null;
        }

        $event = $this->findById($eventId);
        if (!$event) {
            return null;
        }

        $newName = $this->handleImageUpload($imageFile);
        if (!$newName) {
            return null;
        }

        $stmt = $this->db->prepare('UPDATE events SET image = :image WHERE id = :id');
        $stmt->execute([
            ':image' => $newName,
            ':id' => $eventId,
        ]);

        // Optionally delete old image
        if (!empty($event['image'])) {
            $this->deleteImage($event['image']);
        }

        return $this->findById($eventId);
    }

    public function addEventComment(int $eventId, string $name, string $comment): ?array
    {
        $name = trim($name);
        $comment = trim($comment);
        if ($eventId <= 0 || $name === '' || $comment === '') {
            return null;
        }

        $stmt = $this->db->prepare('INSERT INTO event_comments (event_id, name, comment) VALUES (:event_id, :name, :comment)');
        $stmt->execute([
            ':event_id' => $eventId,
            ':name' => $name,
            ':comment' => $comment,
        ]);

        $id = (int) $this->db->lastInsertId();
        return [
            'id' => $id,
            'event_id' => $eventId,
            'name' => $name,
            'comment' => $comment,
        ];
    }

    public function getEventComments(int $eventId): array
    {
        if ($eventId <= 0) {
            return [];
        }
        $stmt = $this->db->prepare('SELECT id, name, comment, created_at FROM event_comments WHERE event_id = :event_id ORDER BY created_at ASC');
        $stmt->execute([':event_id' => $eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
        $recurrencePattern = null;
        if (!empty($event['recurrence_pattern'])) {
            $recurrencePattern = json_decode($event['recurrence_pattern'], true);
        }

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
            'image' => $event['image'],
            'tags' => json_decode($event['tags'] ?? '[]', true) ?: [],
            'is_recurring' => !empty($event['is_recurring']),
            'recurrence_pattern' => $recurrencePattern,
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

    public function addTag(int $eventId, string $tag): ?array
    {
        $tag = trim($tag);
        if ($eventId <= 0 || $tag === '' || !$this->hasColumn('events', 'tags')) {
            return null;
        }

        $event = $this->findById($eventId);
        if (!$event) {
            return null;
        }

        $tags = $event['tags'] ?? [];
        $tags[] = $tag;
        $tags = array_values(array_filter(array_unique(array_map('trim', $tags))));

        $stmt = $this->db->prepare('UPDATE events SET tags = :tags WHERE id = :id');
        $stmt->execute([
            ':tags' => json_encode($tags, JSON_THROW_ON_ERROR),
            ':id' => $eventId,
        ]);

        return $this->findById($eventId);
    }

    private function hasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $table = trim($table);
        $column = trim($column);
        if ($table === '' || $column === '') {
            return false;
        }
        if (!isset($cache[$table])) {
            $stmt = $this->db->query(sprintf('SHOW COLUMNS FROM `%s`', str_replace('`', '``', $table)));
            $cols = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $cache[$table] = array_map(static fn(array $c) => $c['Field'] ?? '', $cols);
        }
        return in_array($column, $cache[$table], true);
    }

    private function isDuplicateException(PDOException $e): bool
    {
        $code = $e->getCode();

        return $code === '23000' || $code === '23505';
    }

    private function handleImageUpload(array $file): ?string
    {
        if (!$this->uploadPath) {
            return null;
        }

        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            return null;
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > 10_485_760) {
            return null;
        }

        $tmpName = $file['tmp_name'] ?? '';
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return null;
        }

        $mimeType = mime_content_type($tmpName) ?: '';
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowed, true)) {
            return null;
        }

        $extension = match ($mimeType) {
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/webp' => '.webp',
            default => '.jpg',
        };

        $filename = sanitize_filename(pathinfo($file['name'] ?? '', PATHINFO_FILENAME));
        if ($filename === '') {
            $filename = 'image';
        }

        $subDirectory = rtrim($this->uploadPath, '/') . '/events';
        if (!is_dir($subDirectory)) {
            mkdir($subDirectory, 0755, true);
        }

        $uniqueName = generate_id('event_') . '_' . $filename . $extension;
        $destination = $subDirectory . '/' . $uniqueName;

        if (!move_uploaded_file($tmpName, $destination)) {
            return null;
        }

        return 'events/' . $uniqueName;
    }

    private function deleteImage(string $imageName): void
    {
        if (!$this->uploadPath || !$imageName) {
            return;
        }

        $path = rtrim($this->uploadPath, '/') . '/' . $imageName;
        if (file_exists($path)) {
            unlink($path);
        }
    }

    private function buildRecurrencePattern(array $data): ?array
    {
        if (empty($data['is_recurring'])) {
            return null;
        }

        $recurrenceType = trim($data['recurrence_type'] ?? '');
        if ($recurrenceType === '') {
            return null;
        }

        $pattern = [
            'type' => $recurrenceType,
            'end_date' => trim($data['recurrence_end_date'] ?? '') ?: null,
        ];

        switch ($recurrenceType) {
            case 'weekly':
                $pattern['interval'] = (int)($data['weekly_interval'] ?? 1);
                break;

            case 'monthly_day':
                $pattern['week'] = trim($data['month_week'] ?? 'first');
                $pattern['day_of_week'] = trim($data['day_of_week'] ?? 'monday');
                $pattern['interval'] = (int)($data['monthly_day_interval'] ?? 1);
                break;

            case 'monthly_date':
                $pattern['interval'] = (int)($data['monthly_date_interval'] ?? 1);
                break;

            case 'custom':
                $pattern['interval'] = (int)($data['custom_interval'] ?? 1);
                $pattern['unit'] = trim($data['custom_unit'] ?? 'days');
                break;
        }

        return $pattern;
    }
}
