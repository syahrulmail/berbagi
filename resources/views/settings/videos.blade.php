@extends('layouts.app')

@section('title', 'Video Beranda')

@push('styles')
<style>
    .vd-row {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        padding: 12px;
        border: 1px solid var(--gray-200);
        border-radius: 12px;
        background: #fafcfc;
        margin-bottom: 10px;
    }
    .vd-preview {
        flex: 0 0 120px;
        width: 120px;
        height: 120px;
        border-radius: 10px;
        border: 1px solid var(--gray-200);
        background: linear-gradient(135deg, #0f766e, #0b3b36) center/cover no-repeat;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 11px;
        text-align: center;
        padding: 6px;
        overflow: hidden;
    }
    .vd-preview.has-image {
        background-color: #000;
    }
    .vd-fields {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .vd-fields .l-file {
        font-size: 12px;
    }
    .vd-fields input[type="file"] {
        display: block;
        width: 100%;
        margin-top: 4px;
        font-size: 12px;
    }
    .vd-hint {
        font-size: 11.5px;
        color: var(--gray-500);
        word-break: break-word;
    }
    .vd-hint.is-error {
        color: #b91c1c;
    }
    .vd-progress {
        height: 6px;
        border-radius: 9999px;
        background: var(--gray-200);
        overflow: hidden;
    }
    .vd-progress-bar {
        height: 100%;
        width: 0;
        background: var(--primary-500, #0f766e);
        transition: width .2s ease;
    }
    .vd-rm {
        flex: 0 0 auto;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-video"></i> Video Beranda</h1>
        <p class="subtitle">Video yang tampil di bawah kartu pencapaian pada beranda dan halaman agen.</p>
    </div>
</div>

<div class="card" style="max-width: 760px;">
    <form method="POST" action="{{ route('settings.videos.update') }}" enctype="multipart/form-data"
          data-video-form data-next-index="{{ count($settings['home_videos']) }}">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label>Daftar Video</label>

            <div data-video-list>
                @foreach($settings['home_videos'] as $index => $video)
                    <div class="vd-row" data-video-row>
                        <div class="vd-preview {{ $video['poster'] ? 'has-image' : '' }}" data-preview
                             @if($video['poster']) style="background-image: url('{{ asset('storage/' . $video['poster']) }}');" @endif>
                            @if(! $video['poster'])
                                @if($video['video'])
                                    Video tersimpan
                                @else
                                    Belum ada video
                                @endif
                            @endif
                        </div>
                        <div class="vd-fields">
                            <label class="l-file">Video (MP4/WebM, hasil kompresi maks. 10 MB)
                                <input type="file" name="videos[{{ $index }}][video]"
                                       accept="video/mp4,video/webm,video/quicktime" data-video-input>
                            </label>
                            <label class="l-file">Sampul (opsional, JPG/PNG/WebP maks. 2 MB)
                                <input type="file" name="videos[{{ $index }}][poster]"
                                       accept="image/jpeg,image/png,image/webp" data-poster-input>
                            </label>
                            <div class="vd-hint" data-status>
                                @if($video['video'])
                                    Video tersimpan. Pilih berkas baru untuk mengganti.
                                @endif
                            </div>
                            <div class="vd-progress" data-progress hidden>
                                <div class="vd-progress-bar" data-progress-bar></div>
                            </div>
                        </div>
                        <input type="hidden" name="videos[{{ $index }}][existing_video]" value="{{ $video['video'] }}" data-existing-video>
                        <input type="hidden" name="videos[{{ $index }}][existing_poster]" value="{{ $video['poster'] }}">
                        <input type="hidden" name="videos[{{ $index }}][ratio]" value="{{ $video['ratio'] }}" data-ratio-input>
                        <input type="hidden" name="videos[{{ $index }}][remove]" value="0" data-remove-input>
                        <button type="button" class="vd-rm btn btn-outline btn-sm" data-remove>Hapus</button>
                    </div>
                @endforeach
            </div>

            <button type="button" class="btn btn-outline btn-sm" data-video-add style="margin-top: 4px;">
                <i class="fas fa-plus"></i> Tambah Video
            </button>

            <small style="display:block; color: var(--gray-500); margin-top: 10px;">
                Video dikompres otomatis di browser saat dipilih (tanpa server ffmpeg),
                lalu diunggah dengan batas 10 MB. Sampul opsional: bila tidak diunggah,
                sampul diambil otomatis dari frame pertama video. Video baru diputar
                saat pengunjung menekan tombol putar, jadi beranda tetap ringan.
            </small>
        </div>

    <template data-video-template>
        <div class="vd-row" data-video-row>
            <div class="vd-preview" data-preview>Belum ada video</div>
            <div class="vd-fields">
                <label class="l-file">Video (MP4/WebM, hasil kompresi maks. 10 MB)
                    <input type="file" name="videos[__INDEX__][video]"
                           accept="video/mp4,video/webm,video/quicktime" data-video-input>
                </label>
                <label class="l-file">Sampul (opsional, JPG/PNG/WebP maks. 2 MB)
                    <input type="file" name="videos[__INDEX__][poster]"
                           accept="image/jpeg,image/png,image/webp" data-poster-input>
                </label>
                <div class="vd-hint" data-status></div>
                <div class="vd-progress" data-progress hidden>
                    <div class="vd-progress-bar" data-progress-bar></div>
                </div>
            </div>
            <input type="hidden" name="videos[__INDEX__][existing_video]" value="" data-existing-video>
            <input type="hidden" name="videos[__INDEX__][existing_poster]" value="">
            <input type="hidden" name="videos[__INDEX__][ratio]" value="1" data-ratio-input>
            <input type="hidden" name="videos[__INDEX__][remove]" value="0" data-remove-input>
            <button type="button" class="vd-rm btn btn-outline btn-sm" data-remove>Hapus</button>
        </div>
    </template>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script src="{{ assetv('js/home-video-upload.js') }}" defer></script>
@endpush
