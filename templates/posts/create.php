<?php
/** @var array $categories */
/** @var array|null $post */
?>
<?php $pageTitle = $post ? 'Edit Konten' : 'Tulis Konten'; ?>

<h1><?= $post ? 'Edit Konten' : 'Tulis Konten Baru' ?></h1>

<form method="post" action="<?= $post ? '/post/' . (int) $post['id'] . '/edit' : '/create' ?>" class="form">
    <?= csrfField() ?>

    <div class="form-group">
        <label for="title">Judul *</label>
        <input type="text" id="title" name="title" required
               value="<?= htmlspecialchars($post['title'] ?? '') ?>" placeholder="Judul konten Anda">
    </div>

    <div class="form-group">
        <label for="category_id">Kategori</label>
        <select id="category_id" name="category_id">
            <option value="">— Pilih kategori —</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= (int) $category['id'] ?>"
                    <?= $post && (int) $post['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="excerpt">Ringkasan</label>
        <textarea id="excerpt" name="excerpt" rows="2" placeholder="Ringkasan singkat (opsional)"><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
        <label for="body">Isi Konten *</label>
        <textarea id="body" name="body" rows="14" required placeholder="Tulis konten Anda di sini..."><?= htmlspecialchars($post['body'] ?? '') ?></textarea>
    </div>

    <div class="form-actions">
        <button type="submit" name="status" value="published" class="btn btn-primary">Publikasikan</button>
        <button type="submit" name="status" value="draft" class="btn">Simpan sebagai Draf</button>
    </div>
</form>
