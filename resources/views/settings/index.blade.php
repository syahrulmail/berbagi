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
