<?php $pageTitle = 'Daftar'; ?>

<div class="auth-box">
    <h1>Buat Akun Baru</h1>
    <form method="post" action="/register" class="form">
        <?= csrfField() ?>
        <div class="form-group">
            <label for="display_name">Nama Tampilan</label>
            <input type="text" id="display_name" name="display_name" placeholder="Nama yang ditampilkan">
        </div>
        <div class="form-group">
            <label for="username">Username *</label>
            <input type="text" id="username" name="username" required>
        </div>
        <div class="form-group">
            <label for="email">Email *</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Daftar</button>
    </form>
    <p class="muted">Sudah punya akun? <a href="/login">Masuk di sini</a></p>
</div>
