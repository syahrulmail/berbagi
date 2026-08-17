<?php
/** @var array $posts */
/** @var array $categories */
/** @var array|null $selectedCategory */
/** @var array $popular */
/** @var int $currentPage */
/** @var int $totalPages */
/** @var int $total */
/** @var string $q */
?>
<?php $pageTitle = 'Berbagi ilmu dan cerita'; ?>

<section class="hero">
    <h1>Berbagi ilmu, berbagi cerita, berbagi kebaikan.</h1>
    <p>Platform terbuka untuk siapa saja yang ingin membagikan pengetahuan dan pengalaman.</p>
</section>

<nav class="category-nav">
    <a href="/" class="<?= empty($selectedCategory) ? 'active' : '' ?>">Semua</a>
    <?php foreach ($categories as $category): ?>
        <a href="/category/<?= htmlspecialchars($category['slug']) ?>"
           class="<?= $selectedCategory && (int) $selectedCategory['id'] === (int) $category['id'] ? 'active' : '' ?>">
            <?= htmlspecialchars($category['name']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<div class="layout-grid">
    <section class="post-list">
        <?php if ($q !== ''): ?>
            <p class="result-info"><?= $total ?> hasil untuk "<strong><?= htmlspecialchars($q) ?></strong>"</p>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
            <p class="empty-state">Belum ada konten yang cocok. Jadilah yang pertama berbagi!</p>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <div class="post-meta">
                    <span><?= htmlspecialchars($post['category_name'] ?? 'Umum') ?></span>
                    <span>• <?= htmlspecialchars($post['display_name']) ?></span>
                    <span>• <?= date('d M Y', strtotime($post['published_at'])) ?></span>
                </div>
                <h2><a href="/post/<?= (int) $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a></h2>
                <?php if ($post['excerpt']): ?>
                    <p class="post-excerpt"><?= htmlspecialchars($post['excerpt']) ?></p>
                <?php endif; ?>
                <div class="post-meta">
                    <span><?= (int) $post['views'] ?>x dilihat</span>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
            <nav class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=<?= $currentPage - 1 ?>&q=<?= urlencode($q) ?>&category=<?= (int) ($categoryFilter ?? 0) ?>">&laquo; Sebelumnya</a>
                <?php endif; ?>
                <span>Halaman <?= $currentPage ?> dari <?= $totalPages ?></span>
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=<?= $currentPage + 1 ?>&q=<?= urlencode($q) ?>&category=<?= (int) ($categoryFilter ?? 0) ?>">Berikutnya &raquo;</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>

    <aside class="sidebar">
        <h3>Terpopuler</h3>
        <?php foreach ($popular as $item): ?>
            <div class="popular-item">
                <a href="/post/<?= (int) $item['id'] ?>"><?= htmlspecialchars($item['title']) ?></a>
                <span class="popular-meta"><?= (int) $item['views'] ?>x dilihat</span>
            </div>
        <?php endforeach; ?>
        <?php if (empty($popular)): ?>
            <p class="muted">Belum ada data.</p>
        <?php endif; ?>
    </aside>
</div>
