<?php

declare(strict_types=1);

class VenueManager
{
    public function __construct(
        private readonly PDO $db,
        private readonly ?string $uploadPath = null
    ) {
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM venues ORDER BY name ASC'
        );

        $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static function (array $venue): array {
            $deputies = json_decode($venue['deputies'] ?? '[]', true) ?: [];

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
                'created_at' => $venue['created_at'],
            ];
        }, $venues);
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

        $deputies = array_map('trim', $data['deputies'] ?? []);
        $deputies = array_values(array_filter(array_unique($deputies)));

        $imageName = null;
        if ($imageFile && $this->uploadPath) {
            $imageName = $this->handleImageUpload($imageFile);
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO venues (name, address, city, state, zip_code, description, owner_name, deputies, image)
                 VALUES (:name, :address, :city, :state, :zip_code, :description, :owner_name, :deputies, :image)'
            );

            $stmt->execute([
                ':name' => $name,
                ':address' => $address,
                ':city' => $city,
                ':state' => $state,
                ':zip_code' => $zipCode,
                ':description' => $description,
                ':owner_name' => $owner,
                ':deputies' => json_encode($deputies, JSON_THROW_ON_ERROR),
                ':image' => $imageName,
            ]);

            $venueId = (int) $this->db->lastInsertId();

            return $this->findById($venueId);
        } catch (Throwable $e) {
            if ($imageName) {
                $this->deleteImage($imageName);
            }
            throw $e;
        }
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
