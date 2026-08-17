<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Comments
{
    public function forPost(int $postId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.*, COALESCE(u.display_name, "Pengunjung") AS author_name
             FROM comments c
             LEFT JOIN users u ON u.id = c.user_id
             WHERE c.post_id = :post_id
             ORDER BY c.created_at ASC'
        );
        $stmt->execute([':post_id' => $postId]);

        return $stmt->fetchAll();
    }

    public function add(int $postId, ?int $userId, string $body): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO comments (post_id, user_id, body) VALUES (:post_id, :user_id, :body)'
        );
        $stmt->execute([
            ':post_id' => $postId,
            ':user_id' => $userId,
            ':body' => $body,
        ]);

        return (int) Database::connection()->lastInsertId();
    }
}
