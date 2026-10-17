@extends('mobile.layouts.app')

@section('title', 'Pengaturan Sistem')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-gear" style="color:var(--mo-primary);font-size:19px;"></i> Pengaturan Sistem</h1>
            <div class="mo-appbar-sub">WhatsApp publik, pelacakan &amp; laporan</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="POST" action="{{ route('mo.settings.update') }}" class="mo-form">
        @csrf
        @method('PUT')

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fab fa-whatsapp"></i> WhatsApp Publik</h3>

            <div class="mo-field">
                <label for="wa_public_number">Nomor WhatsApp BWA <span class="req">*</span></label>
                <input type="text" id="wa_public_number" name="wa_public_number" class="mo-input" value="{{ old('wa_public_number', $settings['wa_public_number']) }}" placeholder="6281234567890" required>
                <div class="mo-form-help">Format internasional tanpa + dan tanpa spasi.</div>
            </div>

            <div class="mo-field">
                <label for="wa_public_template">Template WA Publik</label>
                <textarea id="wa_public_template" name="wa_public_template" class="mo-textarea" rows="3">{{ old('wa_public_template', $settings['wa_public_template']) }}</textarea>
                <div class="mo-form-help">Placeholder <code>{program}</code> diganti nama program.</div>
            </div>

            <div class="mo-field">
                <label for="wa_agent_template">Template WA Agen</label>
                <textarea id="wa_agent_template" name="wa_agent_template" class="mo-textarea" rows="3">{{ old('wa_agent_template', $settings['wa_agent_template']) }}</textarea>
                <div class="mo-form-help">Placeholder <code>{agen}</code> dan <code>{program}</code>.</div>
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-chart-line"></i> Pelacakan &amp; Verifikasi</h3>

            <div class="mo-field">
                <label for="google_site_verification">Google Site Verification</label>
                <input type="text" id="google_site_verification" name="google_site_verification" class="mo-input" value="{{ old('google_site_verification', $settings['google_site_verification']) }}" maxlength="255">
            </div>

            <div class="mo-field">
                <label for="gtm_container_id">Google Tag Manager ID</label>
                <input type="text" id="gtm_container_id" name="gtm_container_id" class="mo-input" value="{{ old('gtm_container_id', $settings['gtm_container_id']) }}" maxlength="50" placeholder="GTM-XXXXXXX">
            </div>

            <div class="mo-field">
                <label for="ga_measurement_id">Google Analytics Measurement ID</label>
                <input type="text" id="ga_measurement_id" name="ga_measurement_id" class="mo-input" value="{{ old('ga_measurement_id', $settings['ga_measurement_id']) }}" maxlength="50" placeholder="G-XXXXXXXXXX">
            </div>

            <div class="mo-field">
                <label for="head_tracking_code">Kode &lt;head&gt;</label>
                <textarea id="head_tracking_code" name="head_tracking_code" class="mo-textarea" rows="4" maxlength="20000" placeholder="<!-- Meta Pixel, Google Analytics, dsb. -->" style="font-family:monospace;font-size:12px;">{{ old('head_tracking_code', $settings['head_tracking_code']) }}</textarea>
                <div class="mo-form-help">Dimuat apa adanya di dalam <code>&lt;head&gt;</code> halaman publik.</div>
            </div>

            <div class="mo-field">
                <label for="body_tracking_code">Kode Sebelum &lt;/body&gt;</label>
                <textarea id="body_tracking_code" name="body_tracking_code" class="mo-textarea" rows="3" maxlength="20000" placeholder="<!-- Kode sebelum &lt;/body&gt; -->" style="font-family:monospace;font-size:12px;">{{ old('body_tracking_code', $settings['body_tracking_code']) }}</textarea>
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-file-lines"></i> Laporan Publik (DFBWA)</h3>

            <div class="mo-field">
                <label for="lapdfbwa_password">Password Laporan Publik</label>
                <input type="password" id="lapdfbwa_password" name="lapdfbwa_password" class="mo-input" value="" autocomplete="new-password" minlength="4" maxlength="60" placeholder="{{ $settings['lapdfbwa_password_set'] ? 'Kosongkan bila tidak ingin mengubah' : 'Belum diatur' }}">
                <div class="mo-form-help">Hanya password yang diminta pengunjung. Kosongkan bila tidak ingin mengubah.</div>
            </div>

            @if($settings['lapdfbwa_password_set'])
                <div class="mo-switch">
                    <div>
                        <div class="lbl">Hapus password</div>
                        <div class="sub">Nonaktifkan akses laporan publik</div>
                    </div>
                    <input type="checkbox" name="lapdfbwa_password_clear" value="1" id="mo-lapdfbwa-clear" {{ old('lapdfbwa_password_clear') ? 'checked' : '' }}>
                    <label class="track" for="mo-lapdfbwa-clear"></label>
                </div>
            @endif
        </div>

        <div class="mo-form-footer">
            <a href="{{ route('mo.more') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection
