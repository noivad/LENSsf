<?php

declare(strict_types=1);

class MediaManager
{
    public function __construct(
        private readonly PDO $db,
        private readonly string $uploadPath,
        private readonly int $maxFileSize = 10_485_760
    ) {
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    private function hasTable(string $table): bool
    {
        try {
            $stmt = $this->db->query(sprintf("SHOW TABLES LIKE '%s'", str_replace("'", "''", $table)));
            return (bool) $stmt->fetch();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function addMediaForEntity(array $file, string $entityType, int $entityId, int $uploaderId, int $sortOrder = 0): ?int
    {
        if (!$this->hasTable('media') || !$this->hasTable('media_relations')) {
            return null;
        }

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

        $filename = sanitize_filename(pathinfo($file['name'] ?? 'image', PATHINFO_FILENAME));
        $uniqueName = generate_id('media_') . '_' . $filename . $extension;
        $subDirectory = rtrim($this->uploadPath, '/') . '/' . $entityType;
        
        if (!is_dir($subDirectory)) {
            mkdir($subDirectory, 0755, true);
        }

        $destination = $subDirectory . '/' . $uniqueName;

        if (!move_uploaded_file($tmpName, $destination)) {
            return null;
        }

        $filePath = $entityType . '/' . $uniqueName;

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                'INSERT INTO media (type, file_path, mime_type, file_size, uploader_id, status)
                 VALUES (:type, :file_path, :mime_type, :file_size, :uploader_id, :status)'
            );

            $stmt->execute([
                ':type' => 'image',
                ':file_path' => $filePath,
                ':mime_type' => $mimeType,
                ':file_size' => $size,
                ':uploader_id' => $uploaderId,
                ':status' => 'active',
            ]);

            $mediaId = (int) $this->db->lastInsertId();

            $stmt = $this->db->prepare(
                'INSERT INTO media_relations (media_id, entity_type, entity_id, sort_order)
                 VALUES (:media_id, :entity_type, :entity_id, :sort_order)'
            );

            $stmt->execute([
                ':media_id' => $mediaId,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':sort_order' => $sortOrder,
            ]);

            $this->db->commit();

            return $mediaId;
        } catch (Throwable $e) {
            $this->db->rollBack();
            if (file_exists($destination)) {
                unlink($destination);
            }
            throw $e;
        }
    }

    public function getMediaForEntity(string $entityType, int $entityId): array
    {
        if (!$this->hasTable('media') || !$this->hasTable('media_relations')) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT m.id, m.file_path, m.mime_type, m.file_size, m.upload_date, mr.sort_order
             FROM media m
             JOIN media_relations mr ON m.id = mr.media_id
             WHERE mr.entity_type = :entity_type AND mr.entity_id = :entity_id AND m.status = :status
             ORDER BY mr.sort_order ASC, m.upload_date DESC'
        );

        $stmt->execute([
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':status' => 'active',
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function deleteMedia(int $mediaId): bool
    {
        if (!$this->hasTable('media')) {
            return false;
        }

        $stmt = $this->db->prepare('SELECT file_path FROM media WHERE id = :id');
        $stmt->execute([':id' => $mediaId]);
        $media = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$media) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            $this->db->prepare('UPDATE media SET status = :status WHERE id = :id')
                ->execute([':status' => 'removed', ':id' => $mediaId]);

            $this->db->commit();

            $filePath = rtrim($this->uploadPath, '/') . '/' . $media['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateUserAvatar(int $userId, array $file): ?string
    {
        if (!$this->hasTable('media') || !$this->hasTable('media_relations')) {
            return null;
        }

        $existingMedia = $this->getMediaForEntity('user', $userId);
        
        foreach ($existingMedia as $media) {
            $this->deleteMedia((int)$media['id']);
        }

        $mediaId = $this->addMediaForEntity($file, 'user', $userId, $userId, 0);
        
        if ($mediaId) {
            $stmt = $this->db->prepare('SELECT file_path FROM media WHERE id = :id');
            $stmt->execute([':id' => $mediaId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['file_path'] : null;
        }

        return null;
    }
}
