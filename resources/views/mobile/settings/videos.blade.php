@extends('mobile.layouts.app')

@section('title', 'Video Beranda')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-video" style="color:var(--mo-primary);font-size:19px;"></i> Video Beranda</h1>
            <div class="mo-appbar-sub">Tampil di beranda &amp; halaman agen</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="POST" action="{{ route('mo.settings.videos.update') }}" enctype="multipart/form-data"
          class="mo-form" data-video-form data-next-index="{{ count($settings['home_videos']) }}">
        @csrf
        @method('PUT')

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-video"></i> Daftar Video</h3>

            <div class="mo-form-help" style="margin-bottom:10px;">
                Video dikompres otomatis di browser saat dipilih, lalu diunggah dengan batas 10 MB.
                Sampul opsional; bila kosong diambil dari frame pertama video.
            </div>

            <div data-video-list>
                @foreach($settings['home_videos'] as $index => $video)
                    <div class="vd-mo-row" data-video-row>
                        <div class="vd-mo-preview {{ $video['poster'] ? 'has-image' : '' }}" data-preview
                             @if($video['poster']) style="background-image: url('{{ asset('storage/' . $video['poster']) }}');" @endif>
                            @if(! $video['poster'])
                                {{ $video['video'] ? 'Video tersimpan' : 'Belum ada video' }}
                            @endif
                        </div>
                        <div class="mo-field" style="margin-bottom:8px;">
                            <label class="vd-mo-label">Video (MP4/WebM, maks. 10 MB)</label>
                            <input type="file" class="mo-input" name="videos[{{ $index }}][video]"
                                   accept="video/mp4,video/webm,video/quicktime" data-video-input>
                        </div>
                        <div class="mo-field" style="margin-bottom:8px;">
                            <label class="vd-mo-label">Sampul (opsional, maks. 2 MB)</label>
                            <input type="file" class="mo-input" name="videos[{{ $index }}][poster]"
                                   accept="image/jpeg,image/png,image/webp" data-poster-input>
                        </div>
                        <div class="mo-form-help vd-mo-hint" data-status>
                            @if($video['video'])
                                Video tersimpan. Pilih berkas baru untuk mengganti.
                            @endif
                        </div>
                        <div class="vd-mo-progress" data-progress hidden>
                            <div class="vd-mo-progress-bar" data-progress-bar></div>
                        </div>
                        <input type="hidden" name="videos[{{ $index }}][existing_video]" value="{{ $video['video'] }}" data-existing-video>
                        <input type="hidden" name="videos[{{ $index }}][existing_poster]" value="{{ $video['poster'] }}">
                        <input type="hidden" name="videos[{{ $index }}][ratio]" value="{{ $video['ratio'] }}" data-ratio-input>
                        <input type="hidden" name="videos[{{ $index }}][remove]" value="0" data-remove-input>
                        <button type="button" class="vd-mo-rm mo-remove-btn" data-remove><i class="fas fa-trash-can"></i> Hapus</button>
                    </div>
                @endforeach
            </div>

            <button type="button" class="mo-btn mo-btn-ghost" data-video-add style="width:100%;">
                <i class="fas fa-plus"></i> Tambah Video
            </button>

            <div class="mo-form-help" style="margin-top:10px;">
                Video baru dimuat saat pengunjung menekan tombol putar, jadi halaman tetap ringan.
            </div>

            <template data-video-template>
                <div class="vd-mo-row" data-video-row>
                    <div class="vd-mo-preview" data-preview>Belum ada video</div>
                    <div class="mo-field" style="margin-bottom:8px;">
                        <label class="vd-mo-label">Video (MP4/WebM, maks. 10 MB)</label>
                        <input type="file" class="mo-input" name="videos[__INDEX__][video]"
                               accept="video/mp4,video/webm,video/quicktime" data-video-input>
                    </div>
                    <div class="mo-field" style="margin-bottom:8px;">
                        <label class="vd-mo-label">Sampul (opsional, maks. 2 MB)</label>
                        <input type="file" class="mo-input" name="videos[__INDEX__][poster]"
                               accept="image/jpeg,image/png,image/webp" data-poster-input>
                    </div>
                    <div class="mo-form-help vd-mo-hint" data-status></div>
                    <div class="vd-mo-progress" data-progress hidden>
                        <div class="vd-mo-progress-bar" data-progress-bar></div>
                    </div>
                    <input type="hidden" name="videos[__INDEX__][existing_video]" value="" data-existing-video>
                    <input type="hidden" name="videos[__INDEX__][existing_poster]" value="">
                    <input type="hidden" name="videos[__INDEX__][ratio]" value="1" data-ratio-input>
                    <input type="hidden" name="videos[__INDEX__][remove]" value="0" data-remove-input>
                    <button type="button" class="vd-mo-rm mo-remove-btn" data-remove><i class="fas fa-trash-can"></i> Hapus</button>
                </div>
            </template>
        </div>

        <div class="mo-form-footer">
            <a href="{{ route('mo.more') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .vd-mo-row {
        border: 1px solid var(--mo-border);
        border-radius: 12px;
        padding: 10px;
        margin-bottom: 10px;
        background: #fafcfc;
    }
    .vd-mo-preview {
        height: 120px;
        border-radius: 10px;
        margin-bottom: 10px;
        border: 1px solid var(--mo-border);
        background: linear-gradient(135deg, #0f766e, #0b3b36) center/cover no-repeat;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 12px;
    }
    .vd-mo-preview.has-image {
        background-color: #000;
    }
    .vd-mo-label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--mo-text, #0b3b36);
        margin-bottom: 4px;
    }
    .vd-mo-hint {
        word-break: break-word;
    }
    .vd-mo-hint.is-error {
        color: var(--mo-danger);
    }
    .vd-mo-progress {
        height: 6px;
        border-radius: 9999px;
        background: var(--mo-border);
        overflow: hidden;
        margin-top: 8px;
    }
    .vd-mo-progress-bar {
        height: 100%;
        width: 0;
        background: var(--mo-primary);
        transition: width .2s ease;
    }
    .vd-mo-rm {
        justify-content: center;
        gap: 6px;
        padding: 8px 10px;
        border-radius: 10px;
        background: #fdeeec;
        color: var(--mo-danger);
        border: none;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        margin-top: 8px;
        width: 100%;
    }
</style>
@endpush

@push('scripts')
<script src="{{ assetv('js/home-video-upload.js') }}" defer></script>
@endpush
