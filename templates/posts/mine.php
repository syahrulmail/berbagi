<?php $pageTitle = 'Konten Saya'; ?>

<div class="list-header">
    <h1>Konten Saya</h1>
    <a href="/create" class="btn btn-primary">Tulis Konten Baru</a>
</div>

<?php if (empty($posts)): ?>
    <p class="empty-state">Anda belum memiliki konten. Yuk mulai menulis!</p>
<?php endif; ?>

<?php foreach ($posts as $post): ?>
    <article class="post-card">
        <div class="post-meta">
            <span class="badge badge-<?= $post['status'] === 'published' ? 'published' : 'draft' ?>">
                <?= $post['status'] === 'published' ? 'Terbit' : 'Draf' ?>
            </span>
            <span>• <?= htmlspecialchars($post['category_name'] ?? 'Umum') ?></span>
            <span>• <?= date('d M Y', strtotime($post['created_at'])) ?></span>
            <span>• <?= (int) $post['views'] ?>x dilihat</span>
        </div>
        <h2><a href="/post/<?= (int) $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a></h2>
        <div class="post-actions">
            <a href="/post/<?= (int) $post['id'] ?>/edit" class="btn btn-sm">Edit</a>
            <form method="post" action="/post/<?= (int) $post['id'] ?>" class="inline-form" onsubmit="return confirm('Hapus konten ini?');">
                <?= csrfField() ?>
                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
            </form>
        </div>
    </article>
<?php endforeach; ?>
