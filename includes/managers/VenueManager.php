<?php

declare(strict_types=1);

class VenueManager
{
    public function __construct(
        private readonly PDO $db,
        private readonly ?string $uploadPath = null
    ) {
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

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM venues ORDER BY name ASC'
        );

        $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hasTags = $this->hasColumn('venues', 'tags');
        $hasOpenTimes = $this->hasColumn('venues', 'open_times');
        $hasIsPrivate = $this->hasColumn('venues', 'is_private');
        $hasIsPublic = $this->hasColumn('venues', 'is_public');

        return array_map(static function (array $venue) use ($hasTags, $hasOpenTimes, $hasIsPrivate, $hasIsPublic): array {
            $deputies = json_decode($venue['deputies'] ?? '[]', true) ?: [];
            $tags = $hasTags ? (json_decode($venue['tags'] ?? '[]', true) ?: []) : [];
            return [
                'id' => (int) $venue['id'],
                'name' => $venue['name'],
                'address' => $venue['address'],
                'city' => $venue['city'],
                'state' => $venue['state'],
                'zip_code' => $venue['zip_code'],
                'description' => $venue['description'],
                'owner' => $venue['owner_name'],
                'deputies' => $deputies,
                'image' => $venue['image'],
                'open_times' => $hasOpenTimes ? ($venue['open_times'] ?? null) : null,
                'is_private' => $hasIsPrivate ? (bool)($venue['is_private'] ?? false) : false,
                'is_public' => $hasIsPublic ? (bool)($venue['is_public'] ?? true) : true,
                'tags' => $tags,
                'created_at' => $venue['created_at'],
            ];
        }, $venues);
    }

    private function mergeVenues(int $venueId, int $duplicateId): void
    {
        $venue = $this->findById($venueId);
        $duplicate = $this->findById($duplicateId);
        
        if (!$venue || !$duplicate) {
            return;
        }

        $mergedName = $venue['name'] . ' / ' . $duplicate['name'];
        
        $mergedDescription = trim(($venue['description'] ?? '') . "\n\n" . ($duplicate['description'] ?? ''));
        
        $widestOpenTime = $this->getWidestOpenTime($venue['open_times'] ?? '', $duplicate['open_times'] ?? '');
        
        $this->update($venueId, [
            'name' => $mergedName,
            'description' => $mergedDescription,
            'open_times' => $widestOpenTime,
            'address' => $venue['address'],
            'city' => $venue['city'],
            'state' => $venue['state'],
            'zip_code' => $venue['zip_code'],
            'owner' => $venue['owner'],
            'deputies' => array_unique(array_merge($venue['deputies'] ?? [], $duplicate['deputies'] ?? [])),
            'tags' => array_unique(array_merge($venue['tags'] ?? [], $duplicate['tags'] ?? [])),
        ]);
        
        $this->db->prepare('UPDATE events SET venue_id = :new_id WHERE venue_id = :old_id')
            ->execute([':new_id' => $venueId, ':old_id' => $duplicateId]);
        
        $this->delete($duplicateId);
    }

    private function getWidestOpenTime(string $time1, string $time2): string
    {
        if (empty($time1)) return $time2;
        if (empty($time2)) return $time1;
        
        return strlen($time1) >= strlen($time2) ? $time1 : $time2;
    }

    private function checkForDuplicateAddress(string $address, string $city, string $state): ?int
    {
        if (empty($address)) {
            return null;
        }
        
        $stmt = $this->db->prepare('SELECT id FROM venues WHERE address = :address AND city = :city AND state = :state LIMIT 1');
        $stmt->execute([':address' => $address, ':city' => $city, ':state' => $state]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (int)$result['id'] : null;
    }

    public function create(array $data, ?array $imageFile = null): ?array
    {
        $name = trim($data['name'] ?? '');
        $owner = trim($data['owner'] ?? '');

        if ($name === '' || $owner === '') {
            return null;
        }

        $address = trim($data['address'] ?? '') ?: null;
        $city = trim($data['city'] ?? '') ?: null;
        $state = trim($data['state'] ?? '') ?: null;
        $zipCode = trim($data['zip_code'] ?? '') ?: null;
        $description = trim($data['description'] ?? '') ?: null;
        $openTimes = trim($data['open_times'] ?? '') ?: null;
        
        $duplicateId = $this->checkForDuplicateAddress($address ?? '', $city ?? '', $state ?? '');
        if ($duplicateId !== null) {
            $newVenueId = $this->createWithoutDuplicateCheck($data, $imageFile);
            if ($newVenueId) {
                $this->mergeVenues($duplicateId, $newVenueId);
                return $this->findById($duplicateId);
            }
        }

        $venueId = $this->createWithoutDuplicateCheck($data, $imageFile);
        return $venueId ? $this->findById($venueId) : null;
    }

    private function createWithoutDuplicateCheck(array $data, ?array $imageFile = null): ?int
    {
        $name = trim($data['name'] ?? '');
        $owner = trim($data['owner'] ?? '');

        if ($name === '' || $owner === '') {
            return null;
        }

        $address = trim($data['address'] ?? '') ?: null;
        $city = trim($data['city'] ?? '') ?: null;
        $state = trim($data['state'] ?? '') ?: null;
        $zipCode = trim($data['zip_code'] ?? '') ?: null;
        $description = trim($data['description'] ?? '') ?: null;
        $openTimes = trim($data['open_times'] ?? '') ?: null;

        $deputies = array_map('trim', $data['deputies'] ?? []);
        $deputies = array_values(array_filter(array_unique($deputies)));

        $tags = array_map(static fn($t) => strtolower(trim((string) $t)), $data['tags'] ?? []);
        $tags = array_values(array_filter(array_unique($tags)));

        $imageName = null;
        if ($imageFile && $this->uploadPath) {
            $imageName = $this->handleImageUpload($imageFile);
        }

        $hasTags = $this->hasColumn('venues', 'tags');
        $hasOpenTimes = $this->hasColumn('venues', 'open_times');
        $hasIsPrivate = $this->hasColumn('venues', 'is_private');
        $hasIsPublic = $this->hasColumn('venues', 'is_public');

        try {
            $columns = ['name','address','city','state','zip_code','description','owner_name','deputies','image'];
            $params = [
                ':name' => $name,
                ':address' => $address,
                ':city' => $city,
                ':state' => $state,
                ':zip_code' => $zipCode,
                ':description' => $description,
                ':owner_name' => $owner,
                ':deputies' => json_encode($deputies, JSON_THROW_ON_ERROR),
                ':image' => $imageName,
            ];

            if ($hasOpenTimes) {
                $columns[] = 'open_times';
                $params[':open_times'] = $openTimes;
            }
            if ($hasTags) {
                $columns[] = 'tags';
                $params[':tags'] = json_encode($tags, JSON_THROW_ON_ERROR);
            }
            if ($hasIsPrivate) {
                $columns[] = 'is_private';
                $params[':is_private'] = (bool)($data['is_private'] ?? false);
            }
            if ($hasIsPublic) {
                $columns[] = 'is_public';
                $params[':is_public'] = (bool)($data['is_public'] ?? true);
            }

            $placeholders = array_map(static fn(string $c) => ':' . $c, $columns);
            $sql = sprintf(
                'INSERT INTO venues (%s) VALUES (%s)',
                implode(', ', $columns),
                implode(', ', $placeholders)
            );

            $stmt = $this->db->prepare($sql);

            $execParams = [];
            foreach ($columns as $c) {
                $key = ':' . $c;
                $execParams[$key] = $params[$key] ?? null;
            }

            $stmt->execute($execParams);

            return (int) $this->db->lastInsertId();
        } catch (Throwable $e) {
            if ($imageName) {
                $this->deleteImage($imageName);
            }
            throw $e;
        }
    }

    public function update(int $venueId, array $data, ?array $imageFile = null): ?array
    {
        if ($venueId <= 0) {
            return null;
        }

        $existing = $this->findById($venueId);
        if (!$existing) {
            return null;
        }

        $name = trim($data['name'] ?? '');
        $owner = trim($data['owner'] ?? '');

        if ($name === '' || $owner === '') {
            return null;
        }

        $address = trim($data['address'] ?? '') ?: null;
        $city = trim($data['city'] ?? '') ?: null;
        $state = trim($data['state'] ?? '') ?: null;
        $zipCode = trim($data['zip_code'] ?? '') ?: null;
        $description = trim($data['description'] ?? '') ?: null;

        $deputies = array_map('trim', $data['deputies'] ?? []);
        $deputies = array_values(array_filter(array_unique($deputies)));

        $hasTags = $this->hasColumn('venues', 'tags');
        $hasOpenTimes = $this->hasColumn('venues', 'open_times');

        $tags = [];
        if ($hasTags) {
            $tags = array_map(static fn($t) => strtolower(trim((string) $t)), $data['tags'] ?? []);
            $tags = array_values(array_filter(array_unique($tags)));
        }

        $newImage = null;
        if ($imageFile && $this->uploadPath) {
            $newImage = $this->handleImageUpload($imageFile) ?: null;
        }

        try {
            $this->db->beginTransaction();

            $assignments = [
                'name' => $name,
                'address' => $address,
                'city' => $city,
                'state' => $state,
                'zip_code' => $zipCode,
                'description' => $description,
                'owner_name' => $owner,
                'deputies' => json_encode($deputies, JSON_THROW_ON_ERROR),
            ];

            if ($hasOpenTimes && array_key_exists('open_times', $data)) {
                $assignments['open_times'] = trim($data['open_times'] ?? '') ?: null;
            }
            if ($hasTags) {
                $assignments['tags'] = json_encode($tags, JSON_THROW_ON_ERROR);
            }
            
            $hasIsPrivate = $this->hasColumn('venues', 'is_private');
            $hasIsPublic = $this->hasColumn('venues', 'is_public');
            
            if ($hasIsPrivate && array_key_exists('is_private', $data)) {
                $assignments['is_private'] = (bool)($data['is_private'] ?? false);
            }
            if ($hasIsPublic && array_key_exists('is_public', $data)) {
                $assignments['is_public'] = (bool)($data['is_public'] ?? true);
            }

            if ($newImage !== null) {
                $assignments['image'] = $newImage;
            }

            $setClauses = [];
            $params = [':id' => $venueId];
            foreach ($assignments as $column => $value) {
                $setClauses[] = sprintf('%s = :%s', $column, $column);
                $params[':' . $column] = $value;
            }

            $sql = sprintf('UPDATE venues SET %s WHERE id = :id', implode(', ', $setClauses));
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

        return $this->findById($venueId);
    }

    public function delete(int $venueId): bool
    {
        if ($venueId <= 0) {
            return false;
        }

        $venue = $this->findById($venueId);
        if (!$venue) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $this->db->prepare('UPDATE events SET venue_id = NULL WHERE venue_id = :id')->execute([':id' => $venueId]);
            $this->db->prepare('DELETE FROM venues WHERE id = :id')->execute([':id' => $venueId]);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        if (!empty($venue['image'])) {
            $this->deleteImage($venue['image']);
        }

        return true;
    }

    public function findById(int $venueId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM venues WHERE id = :id');
        $stmt->execute([':id' => $venueId]);

        $venue = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$venue) {
            return null;
        }

        $deputies = json_decode($venue['deputies'] ?? '[]', true) ?: [];
        $hasTags = $this->hasColumn('venues', 'tags');
        $hasOpenTimes = $this->hasColumn('venues', 'open_times');
        $hasIsPrivate = $this->hasColumn('venues', 'is_private');
        $hasIsPublic = $this->hasColumn('venues', 'is_public');

        return [
            'id' => (int) $venue['id'],
            'name' => $venue['name'],
            'address' => $venue['address'],
            'city' => $venue['city'],
            'state' => $venue['state'],
            'zip_code' => $venue['zip_code'],
            'description' => $venue['description'],
            'owner' => $venue['owner_name'],
            'deputies' => $deputies,
            'image' => $venue['image'],
            'open_times' => $hasOpenTimes ? ($venue['open_times'] ?? null) : null,
            'is_private' => $hasIsPrivate ? (bool)($venue['is_private'] ?? false) : false,
            'is_public' => $hasIsPublic ? (bool)($venue['is_public'] ?? true) : true,
            'tags' => $hasTags ? (json_decode($venue['tags'] ?? '[]', true) ?: []) : [],
            'created_at' => $venue['created_at'],
        ];
    }

    public function find(string $id): ?array
    {
        $venueId = (int) $id;
        if ($venueId <= 0) {
            return null;
        }

        return $this->findById($venueId);
    }

    public function addTagPublic(int $venueId, string $tag): ?array
    {
        $tag = strtolower(trim($tag));
        if ($venueId <= 0 || $tag === '' || !$this->hasColumn('venues', 'tags')) {
            return null;
        }
        $venue = $this->findById($venueId);
        if (!$venue) {
            return null;
        }
        $tags = $venue['tags'] ?? [];
        $tags[] = $tag;
        $tags = array_values(array_filter(array_unique($tags)));
        $stmt = $this->db->prepare('UPDATE venues SET tags = :tags WHERE id = :id');
        $stmt->execute([
            ':tags' => json_encode($tags, JSON_THROW_ON_ERROR),
            ':id' => $venueId,
        ]);
        return $this->findById($venueId);
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

        $subDirectory = rtrim($this->uploadPath, '/') . '/venues';
        if (!is_dir($subDirectory)) {
            mkdir($subDirectory, 0755, true);
        }

        $uniqueName = generate_id('venue_') . '_' . $filename . $extension;
        $destination = $subDirectory . '/' . $uniqueName;

        if (!move_uploaded_file($tmpName, $destination)) {
            return null;
        }

        return 'venues/' . $uniqueName;
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
}
