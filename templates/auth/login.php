<?php $pageTitle = 'Masuk'; ?>

<div class="auth-box">
    <h1>Masuk ke <?= htmlspecialchars($appName) ?></h1>
    <form method="post" action="/login" class="form">
        <?= csrfField() ?>
        <div class="form-group">
            <label for="identifier">Email atau Username</label>
            <input type="text" id="identifier" name="identifier" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Masuk</button>
    </form>
    <p class="muted">Belum punya akun? <a href="/register">Daftar di sini</a></p>
</div>
