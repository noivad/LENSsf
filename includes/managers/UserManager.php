<?php

declare(strict_types=1);

class UserManager
{
    public function __construct(
        private readonly PDO $db,
        private readonly ?string $uploadPath = null
    ) {
    }

    public function create(string $username, string $displayName, string $email, string $password): ?array
    {
        $username = trim($username);
        $displayName = trim($displayName);
        $email = trim($email);
        if ($username === '' || $displayName === '' || $email === '' || $password === '') {
            return null;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $this->db->prepare('INSERT INTO users (username, display_name, email, password_hash) VALUES (:u, :d, :e, :p)');
            $stmt->execute([':u' => $username, ':d' => $displayName, ':e' => $email, ':p' => $hash]);
        } catch (PDOException $e) {
            return null;
        }

        return $this->findByUsername($username);
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT id, username, display_name, email, password_hash, created_at FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => trim($username)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return $row;
    }

    public function verify(string $username, string $password): ?array
    {
        $user = $this->findByUsername($username);
        if (!$user) {
            return null;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }
        return $user;
    }

    public function updateAvatar(int $userId, array $imageFile): bool
    {
        if ($userId <= 0 || !$this->uploadPath) {
            return false;
        }

        $imageName = $this->handleImageUpload($imageFile);
        if (!$imageName) {
            return false;
        }

        try {
            $stmt = $this->db->prepare('INSERT INTO user_profiles (user_id, avatar_url) VALUES (:id, :avatar) ON DUPLICATE KEY UPDATE avatar_url = :avatar');
            $stmt->execute([':id' => $userId, ':avatar' => $imageName]);
            return true;
        } catch (PDOException $e) {
            if ($imageName) {
                $this->deleteImage($imageName);
            }
            return false;
        }
    }

    public function getProfile(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM user_profiles WHERE user_id = :id');
        $stmt->execute([':id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
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
            $filename = 'avatar';
        }

        $subDirectory = rtrim($this->uploadPath, '/') . '/users';
        if (!is_dir($subDirectory)) {
            mkdir($subDirectory, 0755, true);
        }

        $uniqueName = generate_id('user_') . '_' . $filename . $extension;
        $destination = $subDirectory . '/' . $uniqueName;

        if (!move_uploaded_file($tmpName, $destination)) {
            return null;
        }

        return 'users/' . $uniqueName;
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
