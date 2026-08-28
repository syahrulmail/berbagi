@push('scripts')
<script>
(function () {
    var fileInput = document.getElementById('profilePhoto');
    var preview = document.getElementById('profilePhotoPreview');
    var placeholder = document.getElementById('profilePhotoPlaceholder');

    if (!fileInput) return;

    fileInput.addEventListener('change', function () {
        var file = fileInput.files && fileInput.files[0];
        if (!file) return;

        var reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = '';
            placeholder.style.display = 'none';
        };
        reader.readAsDataURL(file);
    });

    var removeCheck = document.querySelector('input[name="photo_remove"]');
    if (removeCheck) {
        removeCheck.addEventListener('change', function () {
            if (removeCheck.checked) {
                preview.style.display = 'none';
                placeholder.style.display = '';
                fileInput.value = '';
            } else {
                preview.style.display = preview.src ? '' : 'none';
                placeholder.style.display = preview.src ? 'none' : '';
            }
        });
    }
})();
</script>
@endpush
