<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function register(string $username, string $email, string $password, string $displayName = ''): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1');
        $stmt->execute([':email' => $email, ':username' => $username]);

        if ($stmt->fetch()) {
            return ['error' => 'Email atau username sudah terdaftar.'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, display_name) VALUES (:username, :email, :hash, :name)'
        );
        $stmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':hash' => $passwordHash,
            ':name' => $displayName !== '' ? $displayName : $username,
        ]);

        $userId = (int) $pdo->lastInsertId();
        self::loginUser($userId, $username, $displayName !== '' ? $displayName : $username);

        return ['success' => true];
    }

    public static function login(string $identifier, string $password): array
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :id OR username = :id LIMIT 1');
        $stmt->execute([':id' => $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['error' => 'Email/username atau password salah.'];
        }

        self::loginUser((int) $user['id'], $user['username'], $user['display_name'], $user['role']);

        return ['success' => true];
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function user(): ?array
    {
        self::startSession();
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && $user['role'] === 'admin';
    }

    private static function loginUser(int $id, string $username, string $displayName, string $role = 'user'): void
    {
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $id,
            'username' => $username,
            'display_name' => $displayName,
            'role' => $role,
        ];
    }
}
