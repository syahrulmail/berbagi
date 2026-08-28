@push('styles')
<style>
    .t-file {
        font-size: 12px;
        width: 100%;
        margin-top: 8px;
    }
    .profile-photo-wrap {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 4px;
    }
    .profile-photo-preview {
        width: 96px;
        height: 96px;
        object-fit: cover;
        border-radius: 14px;
        border: 1px solid var(--gray-300);
        background: #fff;
    }
    .profile-photo-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 96px;
        height: 96px;
        border-radius: 14px;
        background: linear-gradient(135deg, #08a899, #086e66);
        color: #fff;
        font-size: 34px;
        font-weight: 700;
        border: 1px solid var(--gray-300);
    }
    .inline-check {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: var(--gray-700);
        cursor: pointer;
        margin-top: 8px;
    }
</style>
@endpush
