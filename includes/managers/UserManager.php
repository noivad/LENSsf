<?php

declare(strict_types=1);

class UserManager
{
    public function __construct(private readonly PDO $db)
    {
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
}
