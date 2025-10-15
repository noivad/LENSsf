<?php

declare(strict_types=1);

class VenueManager
{
    private const STORE_KEY = 'venues';

    public function __construct(private readonly DataStore $store)
    {
    }

    public function all(): array
    {
        $venues = $this->store->load(self::STORE_KEY);

        usort($venues, static function (array $a, array $b) {
            return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
        });

        return $venues;
    }

    public function create(array $data): array
    {
        $venues = $this->store->load(self::STORE_KEY);
        $id = generate_id('ven_');

        $venue = [
            'id' => $id,
            'name' => trim($data['name'] ?? ''),
            'address' => trim($data['address'] ?? ''),
            'city' => trim($data['city'] ?? ''),
            'state' => trim($data['state'] ?? ''),
            'zip_code' => trim($data['zip_code'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'owner' => trim($data['owner'] ?? ''),
            'deputies' => $data['deputies'] ?? [],
            'created_at' => date('c'),
        ];

        $venues[] = $venue;
        $this->store->save(self::STORE_KEY, $venues);

        return $venue;
    }

    public function find(string $id): ?array
    {
        foreach ($this->all() as $venue) {
            if ($venue['id'] === $id) {
                return $venue;
            }
        }

        return null;
    }
}
