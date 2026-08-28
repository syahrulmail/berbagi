@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-gear"></i> Pengaturan Sistem</h1>
        <p class="subtitle">Konfigurasi target global dan otomasi.</p>
    </div>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('settings.update') }}">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="global_target">Target Global BWA (Rp)</label>
            <input type="number" id="global_target" name="global_target" value="{{ old('global_target', $settings['global_target']) }}" min="0" step="0.01">
            <small style="color: var(--gray-500);">Total target fundraising secara keseluruhan.</small>
        </div>

        <div class="form-group">
            <label for="trustbar_text">Teks Bar Atas (Trustbar)</label>
            <input type="text" id="trustbar_text" name="trustbar_text" value="{{ old('trustbar_text', $settings['trustbar_text']) }}" maxlength="160">
            <small style="color: var(--gray-500);">Teks yang tampil pada baris teratas seluruh halaman publik, mis. "Badan Wakaf Al Qur'an · Terdaftar &amp; Berizin".</small>
        </div>

        <hr style="border: none; border-top: 1px solid var(--gray-200); margin: 20px 0;">

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

        <h3 style="font-size: 15px; margin-bottom: 14px;">Otomasi WhatsApp</h3>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="wa_reminder_enabled" value="1" {{ old('wa_reminder_enabled', $settings['wa_reminder_enabled']) == '1' ? 'checked' : '' }}>
                Aktifkan pengingat otomatis WhatsApp
            </label>
        </div>
        <div class="form-group">
            <label for="wa_reminder_hour">Jam Pengingat</label>
            <select id="wa_reminder_hour" name="wa_reminder_hour">
                @for($h = 0; $h <= 23; $h++)
                    <option value="{{ $h }}" {{ old('wa_reminder_hour', $settings['wa_reminder_hour']) == $h ? 'selected' : '' }}>
                        {{ sprintf('%02d:00', $h) }}
                    </option>
                @endfor
            </select>
            <small style="color: var(--gray-500);">Dijalankan oleh cron job melalui <code>php artisan schedule:run</code>.</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Pengaturan</button>
        </div>
    </form>
</div>
@endsection
