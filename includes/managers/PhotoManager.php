<?php

declare(strict_types=1);

class PhotoManager
{
    private const STORE_KEY = 'photos';

    private int $maxFileSize;

    public function __construct(
        private readonly DataStore $store,
        private readonly string $uploadPath,
        int $maxFileSize = 5_242_880 // 5MB
    ) {
        $this->maxFileSize = $maxFileSize;

        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    public function all(): array
    {
        $photos = $this->store->load(self::STORE_KEY);

        usort($photos, static function (array $a, array $b) {
            return strcmp($b['uploaded_at'] ?? '', $a['uploaded_at'] ?? '');
        });

        return $photos;
    }

    public function add(array $file, array $data): ?array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $size = $file['size'] ?? 0;
        if ($size <= 0 || $size > $this->maxFileSize) {
            return null;
        }

        $tmpName = $file['tmp_name'] ?? '';
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return null;
        }

        $mimeType = mime_content_type($tmpName);
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mimeType, $allowed, true)) {
            return null;
        }

        $extension = match ($mimeType) {
            'image/png' => '.png',
            'image/gif' => '.gif',
            default => '.jpg',
        };

        $filename = sanitize_filename(pathinfo($file['name'], PATHINFO_FILENAME));
        $uniqueName = generate_id('photo_') . '_' . $filename . $extension;
        $destination = rtrim($this->uploadPath, '/') . '/' . $uniqueName;

        if (!move_uploaded_file($tmpName, $destination)) {
            return null;
        }

        $photos = $this->store->load(self::STORE_KEY);

        $photo = [
            'id' => generate_id('ph_'),
            'filename' => $uniqueName,
            'original_name' => $file['name'],
            'size' => $size,
            'caption' => trim($data['caption'] ?? ''),
            'uploaded_by' => trim($data['uploaded_by'] ?? ''),
            'event_id' => $data['event_id'] ?? null,
            'uploaded_at' => date('c'),
            'comments' => [],
        ];

        $photos[] = $photo;
        $this->store->save(self::STORE_KEY, $photos);

        return $photo;
    }

    public function addComment(string $photoId, string $name, string $comment): void
    {
        $name = trim($name);
        $comment = trim($comment);

        if ($name === '' || $comment === '') {
            return;
        }

        $photos = $this->store->load(self::STORE_KEY);

        foreach ($photos as &$photo) {
            if ($photo['id'] !== $photoId) {
                continue;
            }

            $photo['comments'][] = [
                'id' => generate_id('com_'),
                'name' => $name,
                'comment' => $comment,
                'created_at' => date('c'),
            ];

            $this->store->save(self::STORE_KEY, $photos);

            return;
        }
    }
}
