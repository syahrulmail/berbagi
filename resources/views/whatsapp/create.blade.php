@extends('layouts.app')

@section('title', 'Jadwalkan Pesan WhatsApp')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fab fa-whatsapp"></i> Jadwalkan Pesan WhatsApp</h1>
        <p class="subtitle">Kirim pesan otomatis ke kontak donatur.</p>
    </div>
    <a href="{{ route('whatsapp.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('whatsapp.store') }}">
        @csrf
        <div class="form-group">
            <label for="contact_id">Pilih Kontak</label>
            <select id="contact_id" name="contact_id">
                <option value="">— Ketik manual nomor tujuan —</option>
                @foreach($contacts as $contact)
                    <option value="{{ $contact->id }}" {{ old('contact_id') == $contact->id ? 'selected' : '' }}>
                        {{ $contact->name }} ({{ $contact->phone }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="phone">No. Tujuan *</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="628xxxxxxx" required>
            <small style="color: var(--gray-500);">Format internasional tanpa tanda +, contoh: 6281234567890</small>
        </div>
        <div class="form-group">
            <label for="message">Isi Pesan *</label>
            <textarea id="message" name="message" rows="5" required placeholder="Teks pesan yang akan dikirim...">{{ old('message') }}</textarea>
        </div>
        <div class="alert" style="background: #E9F0FA; border-color: var(--primary); color: var(--primary);">
            <i class="fas fa-circle-info"></i>
            Pesan masuk antrian dan akan dikirim oleh scheduler (cron job) saat <code>php artisan schedule:run</code> dijalankan.
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Jadwalkan</button>
            <a href="{{ route('whatsapp.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>
@endsection
