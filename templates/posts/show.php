<?php
/** @var array $post */
/** @var array $comments */
?>
<?php $pageTitle = $post['title']; ?>

<article class="post-detail">
    <div class="post-meta">
        <span><?= htmlspecialchars($post['category_name'] ?? 'Umum') ?></span>
        <span>• <?= htmlspecialchars($post['display_name']) ?></span>
        <span>• <?= date('d M Y H:i', strtotime($post['published_at'] ?? $post['created_at'])) ?></span>
        <span>• <?= (int) $post['views'] ?>x dilihat</span>
    </div>

    <h1><?= htmlspecialchars($post['title']) ?></h1>

    <div class="post-body">
        <?php foreach (explode("\n", $post['body']) as $paragraph): ?>
            <?php if (trim($paragraph) !== ''): ?>
                <p><?= nl2br(htmlspecialchars($paragraph)) ?></p>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($user && (int) $user['id'] === (int) $post['user_id']): ?>
        <div class="post-actions">
            <a href="/post/<?= (int) $post['id'] ?>/edit" class="btn">Edit</a>
            <form method="post" action="/post/<?= (int) $post['id'] ?>" class="inline-form" onsubmit="return confirm('Hapus konten ini?');">
                <?= csrfField() ?>
                <button type="submit" class="btn btn-danger">Hapus</button>
            </form>
        </div>
    <?php endif; ?>
</article>

<section class="comments">
    <h2>Komentar (<?= count($comments) ?>)</h2>

    <?php foreach ($comments as $comment): ?>
        <div class="comment">
            <div class="comment-meta"><?= htmlspecialchars($comment['author_name']) ?> • <?= date('d M Y H:i', strtotime($comment['created_at'])) ?></div>
            <p><?= nl2br(htmlspecialchars($comment['body'])) ?></p>
        </div>
    <?php endforeach; ?>

    <?php if (empty($comments)): ?>
        <p class="muted">Belum ada komentar.</p>
    <?php endif; ?>

    <?php if ($post['status'] === 'published'): ?>
        <form method="post" action="/comment/<?= (int) $post['id'] ?>" class="form comment-form">
            <?= csrfField() ?>
            <div class="form-group">
                <label for="body">Tinggalkan komentar</label>
                <textarea id="body" name="body" rows="3" required placeholder="Pendapat Anda..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Kirim Komentar</button>
        </form>
    <?php endif; ?>
</section>
