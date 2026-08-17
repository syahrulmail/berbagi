<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Post
{
    public static function create(int $userId, array $data): int
    {
        $pdo = Database::connection();

        $status = $data['status'] === 'published' ? 'published' : 'draft';

        $stmt = $pdo->prepare(
            'INSERT INTO posts (user_id, category_id, title, slug, excerpt, body, status, published_at)
             VALUES (:user_id, :category_id, :title, :slug, :excerpt, :body, :status, :published_at)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':category_id' => $data['category_id'] !== null ? (int) $data['category_id'] : null,
            ':title' => $data['title'],
            ':slug' => self::uniqueSlug($data['slug']),
            ':excerpt' => $data['excerpt'] !== '' ? $data['excerpt'] : null,
            ':body' => $data['body'],
            ':status' => $status,
            ':published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $pdo = Database::connection();

        $status = $data['status'] === 'published' ? 'published' : 'draft';

        $stmt = $pdo->prepare(
            'UPDATE posts
             SET category_id = :category_id, title = :title, excerpt = :excerpt, body = :body, status = :status,
                 published_at = CASE
                     WHEN :published_at_now = 1 AND published_at IS NULL THEN NOW()
                     ELSE published_at
                 END
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId,
            ':category_id' => $data['category_id'] !== null ? (int) $data['category_id'] : null,
            ':title' => $data['title'],
            ':excerpt' => $data['excerpt'] !== '' ? $data['excerpt'] : null,
            ':body' => $data['body'],
            ':status' => $status,
            ':published_at_now' => $status === 'published' ? 1 : 0,
        ]);

        return $stmt->rowCount() > 0;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.*, u.username, u.display_name, c.name AS category_name
             FROM posts p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id'
        );
        $stmt->execute([':id' => $id]);
        $post = $stmt->fetch();

        return $post ?: null;
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.*, u.username, u.display_name, c.name AS category_name
             FROM posts p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = :slug AND p.status = "published"'
        );
        $stmt->execute([':slug' => $slug]);
        $post = $stmt->fetch();

        return $post ?: null;
    }

    public static function incrementViews(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE posts SET views = views + 1 WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public static function published(array $filters = []): array
    {
        $pdo = Database::connection();

        $where = ['p.status = "published"'];
        $params = [];

        if (!empty($filters['category'])) {
            $where[] = 'p.category_id = :category';
            $params[':category'] = (int) $filters['category'];
        }

        if (!empty($filters['q'])) {
            $where[] = '(p.title LIKE :q1 OR p.excerpt LIKE :q2 OR p.body LIKE :q3)';
            $like = '%' . $filters['q'] . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }

        $limit = (int) ($filters['limit'] ?? 12);
        $offset = (int) ($filters['offset'] ?? 0);

        $sql = 'SELECT p.*, u.username, u.display_name, c.name AS category_name
                FROM posts p
                JOIN users u ON u.id = p.user_id
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY p.published_at DESC
                LIMIT :limit OFFSET :offset';

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function countPublished(array $filters = []): int
    {
        $pdo = Database::connection();

        $where = ['status = "published"'];
        $params = [];

        if (!empty($filters['category'])) {
            $where[] = 'category_id = :category';
            $params[':category'] = (int) $filters['category'];
        }

        if (!empty($filters['q'])) {
            $where[] = '(title LIKE :q1 OR excerpt LIKE :q2 OR body LIKE :q3)';
            $like = '%' . $filters['q'] . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM posts WHERE ' . implode(' AND ', $where));
        $stmt->execute($params);

        return (int) $stmt->fetch()['total'];
    }

    public static function mine(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.*, c.name AS category_name
             FROM posts p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.user_id = :user_id
             ORDER BY p.created_at DESC'
        );
        $stmt->execute([':user_id' => $userId]);

        return $stmt->fetchAll();
    }

    public static function delete(int $id, int $userId): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM posts WHERE id = :id AND user_id = :user_id');
        $stmt->execute([':id' => $id, ':user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    public static function categories(): array
    {
        $stmt = Database::connection()->query(
            'SELECT id, name, slug FROM categories ORDER BY name'
        );

        return $stmt->fetchAll();
    }

    public static function popular(int $limit = 5): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.*, u.display_name, c.name AS category_name
             FROM posts p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.status = "published"
             ORDER BY p.views DESC
             LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = str_replace(['\'', '"'], '', $text);
        $text = preg_replace('/[^a-z0-9\s-]/u', '', $text);
        $text = preg_replace('/[\s_-]+/', '-', $text);

        return trim((string) $text, '-') ?: 'konten';
    }

    private static function uniqueSlug(string $slug): string
    {
        $base = $slug !== '' ? $slug : 'konten';
        $candidate = $base;
        $counter = 1;

        $stmt = Database::connection()->prepare('SELECT id FROM posts WHERE slug = :slug LIMIT 1');

        while (true) {
            $stmt->execute([':slug' => $candidate]);
            if (!$stmt->fetch()) {
                return $candidate;
            }
            $candidate = $base . '-' . $counter;
            $counter++;
        }
    }
}
