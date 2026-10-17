@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-gear"></i> Pengaturan Sistem</h1>
        <p class="subtitle">Konfigurasi WhatsApp publik, pelacakan, dan laporan.</p>
    </div>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')

        <h3 style="font-size: 15px; margin-bottom: 14px;">WhatsApp Publik</h3>

        <div class="form-group">
            <label for="wa_public_number">Nomor WhatsApp BWA (untuk tombol WA di halaman publik)</label>
            <input type="text" id="wa_public_number" name="wa_public_number" value="{{ old('wa_public_number', $settings['wa_public_number']) }}" placeholder="6281234567890">
            <small style="color: var(--gray-500);">Format internasional tanpa + dan tanpa spasi.</small>
        </div>

        <div class="form-group">
            <label for="wa_public_template">Template WA Publik</label>
            <textarea id="wa_public_template" name="wa_public_template" rows="3">{{ old('wa_public_template', $settings['wa_public_template']) }}</textarea>
            <small style="color: var(--gray-500);">Placeholder <code>{program}</code> akan diganti nama program.</small>
        </div>

        <div class="form-group">
            <label for="wa_agent_template">Template WA Agen</label>
            <textarea id="wa_agent_template" name="wa_agent_template" rows="3">{{ old('wa_agent_template', $settings['wa_agent_template']) }}</textarea>
            <small style="color: var(--gray-500);">Placeholder <code>{agen}</code> (nama agen) dan <code>{program}</code> (nama program).</small>
        </div>

        <hr style="border: none; border-top: 1px solid var(--gray-200); margin: 20px 0;">

        <h3 style="font-size: 15px; margin-bottom: 14px;">Pelacakan &amp; Verifikasi</h3>

        <div class="form-group">
            <label for="google_site_verification">Google Site Verification</label>
            <input type="text" id="google_site_verification" name="google_site_verification" value="{{ old('google_site_verification', $settings['google_site_verification']) }}" maxlength="255" placeholder="nilai content dari Google Search Console">
            <small style="color: var(--gray-500);">Isi nilai <code>content</code> pada tag <code>google-site-verification</code>.</small>
        </div>

        <div class="form-group">
            <label for="gtm_container_id">Google Tag Manager ID</label>
            <input type="text" id="gtm_container_id" name="gtm_container_id" value="{{ old('gtm_container_id', $settings['gtm_container_id']) }}" maxlength="50" placeholder="GTM-XXXXXXX">
        </div>

        <div class="form-group">
            <label for="ga_measurement_id">Google Analytics Measurement ID</label>
            <input type="text" id="ga_measurement_id" name="ga_measurement_id" value="{{ old('ga_measurement_id', $settings['ga_measurement_id']) }}" maxlength="50" placeholder="G-XXXXXXXXXX">
        </div>

        <div class="form-group">
            <label for="head_tracking_code">Kode &lt;head&gt; (Google Analytics, Meta Pixel, dan lainnya)</label>
            <textarea id="head_tracking_code" name="head_tracking_code" rows="5" maxlength="20000" placeholder="<!-- Tempel script pelacakan di sini -->" style="font-family: monospace; font-size: 12.5px;">{{ old('head_tracking_code', $settings['head_tracking_code']) }}</textarea>
            <small style="color: var(--gray-500);">Kode dimuat apa adanya di dalam <code>&lt;head&gt;</code> seluruh halaman publik. Gunakan untuk Meta Pixel, Google Analytics, atau tag lain.</small>
        </div>

        <div class="form-group">
            <label for="body_tracking_code">Kode Sebelum &lt;/body&gt;</label>
            <textarea id="body_tracking_code" name="body_tracking_code" rows="4" maxlength="20000" placeholder="<!-- Tempel script yang perlu dimuat sebelum &lt;/body&gt; -->" style="font-family: monospace; font-size: 12.5px;">{{ old('body_tracking_code', $settings['body_tracking_code']) }}</textarea>
            <small style="color: var(--gray-500);">Kode dimuat apa adanya sebelum <code>&lt;/body&gt;</code>, mis. <code>&lt;noscript&gt;</code> Meta Pixel.</small>
        </div>

        <h3 style="font-size: 15px; margin: 24px 0 14px;">Laporan Publik (DFBWA)</h3>

        <div class="form-group">
            <label for="lapdfbwa_password">Password Laporan Publik</label>
            <input type="password" id="lapdfbwa_password" name="lapdfbwa_password" value="" autocomplete="new-password" minlength="4" maxlength="60" placeholder="{{ $settings['lapdfbwa_password_set'] ? 'Kosongkan bila tidak ingin mengubah' : 'Belum diatur' }}">
            <small style="color: var(--gray-500);">
                Digunakan untuk membuka halaman publik <a href="{{ route('lapdfbwa.show') }}" target="_blank" rel="noopener">{{ route('lapdfbwa.show') }}</a>.
                Hanya password (tanpa username) yang diminta pengunjung. Biarkan kosong bila tidak ingin mengubah.
            </small>
        </div>

        @if($settings['lapdfbwa_password_set'])
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="lapdfbwa_password_clear" value="1" {{ old('lapdfbwa_password_clear') ? 'checked' : '' }}>
                    Hapus / nonaktifkan password laporan publik
                </label>
            </div>
        @endif

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Pengaturan</button>
        </div>
    </form>
</div>
@endsection
