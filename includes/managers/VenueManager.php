<?php

declare(strict_types=1);

class VenueManager
{
    public function __construct(private readonly PDO $db)
    {
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
                'created_at' => $venue['created_at'],
            ];
        }, $venues);
    }

    public function create(array $data): ?array
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

        $stmt = $this->db->prepare(
            'INSERT INTO venues (name, address, city, state, zip_code, description, owner_name, deputies)
             VALUES (:name, :address, :city, :state, :zip_code, :description, :owner_name, :deputies)'
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
        ]);

        $venueId = (int) $this->db->lastInsertId();

        return $this->findById($venueId);
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
}
