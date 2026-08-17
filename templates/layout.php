<?php
/** @var array $user */
/** @var array|null $flash */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' · ' : '' ?><?= htmlspecialchars($appName) ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="/" class="brand"><?= htmlspecialchars($appName) ?></a>
        <form action="/" method="get" class="search-form">
            <input type="search" name="q" placeholder="Cari konten..." value="<?= isset($q) ? htmlspecialchars($q) : '' ?>">
        </form>
        <nav class="nav">
            <?php if ($user): ?>
                <a href="/my-posts">Konten Saya</a>
                <a href="/create" class="btn btn-primary">Tulis Konten</a>
                <span class="nav-user"><?= htmlspecialchars($user['display_name']) ?></span>
                <a href="/logout">Keluar</a>
            <?php else: ?>
                <a href="/login">Masuk</a>
                <a href="/register" class="btn btn-primary">Daftar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<?php if ($flash): ?>
    <div class="container">
        <div class="flash flash-<?= htmlspecialchars($flash['type']) ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    </div>
<?php endif; ?>

<main class="container">
    <?= $content ?? '' ?>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($appName) ?> — Berbagi ilmu, berbagi cerita, berbagi kebaikan.</p>
    </div>
</footer>
</body>
</html>
