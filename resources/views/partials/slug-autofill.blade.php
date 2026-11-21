@push('scripts')
<script data-slug-autofill>
(function () {
    var nameInput = document.getElementById('name');
    var slugInput = document.getElementById('slug');
    if (!nameInput || !slugInput) return;

    var slugTouched = slugInput.value.trim() !== '';

    function slugify(value) {
        return String(value)
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function syncSlug() {
        if (slugTouched) return;
        slugInput.value = slugify(nameInput.value);
    }

    nameInput.addEventListener('input', syncSlug);
    slugInput.addEventListener('input', function () {
        slugTouched = slugInput.value.trim() !== '';
    });

    syncSlug();
})();
</script>
@endpush
