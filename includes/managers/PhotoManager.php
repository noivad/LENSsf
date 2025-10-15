<?php

declare(strict_types=1);

class PhotoManager
{
    public function __construct(
        private readonly PDO $db,
        private readonly string $uploadPath,
        private readonly int $maxFileSize = 5_242_880
    ) {
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    public function all(): array
    {
        $stmt = $this->db->query(
            'SELECT * FROM photos ORDER BY uploaded_at DESC'
        );

        $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$photos) {
            return [];
        }

        $photoIds = array_map(static fn (array $photo): int => (int) $photo['id'], $photos);
        $commentsMap = $this->fetchComments($photoIds);

        return array_map(function (array $photo) use ($commentsMap): array {
            $id = (int) $photo['id'];

            return [
                'id' => $id,
                'event_id' => $photo['event_id'],
                'filename' => $photo['filename'],
                'original_name' => $photo['original_name'],
                'caption' => $photo['caption'],
                'uploaded_by' => $photo['uploaded_by'],
                'uploaded_at' => $photo['uploaded_at'],
                'size' => (int) $photo['file_size'],
                'comments' => $commentsMap[$id] ?? [],
            ];
        }, $photos);
    }

    public function add(array $file, array $data): ?array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $size = (int) ($file['size'] ?? 0);
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

        $caption = trim($data['caption'] ?? '') ?: null;
        $uploadedBy = trim($data['uploaded_by'] ?? '');
        $eventId = $data['event_id'] ?: null;

        if ($uploadedBy === '') {
            unlink($destination);

            return null;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO photos (event_id, filename, original_name, file_size, caption, uploaded_by)
             VALUES (:event_id, :filename, :original_name, :file_size, :caption, :uploaded_by)'
        );

        try {
            $stmt->execute([
                ':event_id' => $eventId ?: null,
                ':filename' => $uniqueName,
                ':original_name' => $file['name'],
                ':file_size' => $size,
                ':caption' => $caption,
                ':uploaded_by' => $uploadedBy,
            ]);
        } catch (Throwable $e) {
            unlink($destination);
            throw $e;
        }

        $photoId = (int) $this->db->lastInsertId();

        return $this->findById($photoId);
    }

    public function addComment(string $photoId, string $name, string $comment): void
    {
        $photoId = trim($photoId);
        $name = trim($name);
        $comment = trim($comment);

        if ($photoId === '' || $name === '' || $comment === '') {
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO photo_comments (photo_id, name, comment) VALUES (:photo_id, :name, :comment)'
        );

        $stmt->execute([
            ':photo_id' => (int) $photoId,
            ':name' => $name,
            ':comment' => $comment,
        ]);
    }

    private function fetchComments(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            sprintf('SELECT id, photo_id, name, comment, created_at FROM photo_comments WHERE photo_id IN (%s) ORDER BY created_at ASC', $placeholders)
        );
        $stmt->execute(array_values($ids));

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $photoId = (int) $row['photo_id'];
            $result[$photoId][] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'comment' => $row['comment'],
                'created_at' => $row['created_at'],
            ];
        }

        return $result;
    }

    private function findById(int $photoId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM photos WHERE id = :id');
        $stmt->execute([':id' => $photoId]);

        $photo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$photo) {
            return null;
        }

        return [
            'id' => (int) $photo['id'],
            'event_id' => $photo['event_id'],
            'filename' => $photo['filename'],
            'original_name' => $photo['original_name'],
            'caption' => $photo['caption'],
            'uploaded_by' => $photo['uploaded_by'],
            'uploaded_at' => $photo['uploaded_at'],
            'size' => (int) $photo['file_size'],
            'comments' => $this->fetchComments([$photoId])[$photoId] ?? [],
        ];
    }
}
