@extends('mobile.layouts.app')

@section('title', 'Tambah Pesan WhatsApp')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.whatsapp') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title">Pesan WhatsApp</h1>
            <div class="mo-appbar-sub">Jadwalkan pesan ke kontak</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="POST" action="{{ route('mo.whatsapp.store') }}" class="mo-form">
        @csrf

        <div class="mo-form-card">
            <div class="mo-form-card-title"><i class="fas fa-paper-plane"></i> Detail Pesan</div>

            <div class="mo-field">
                <label>Kontak <span style="color:var(--mo-muted);font-weight:400;">(opsional)</span></label>
                <select name="contact_id" id="wa-contact" class="mo-select">
                    <option value="">— Tanpa kontak / nomor manual —</option>
                    @foreach($contacts as $c)
                        <option value="{{ $c->id }}" data-phone="{{ $c->phone }}" {{ old('contact_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} · {{ $c->phone }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mo-field">
                <label>No. WhatsApp <span class="req">*</span></label>
                <input type="text" name="phone" id="wa-phone" class="mo-input" value="{{ old('phone') }}" placeholder="62812xxxxxxx" required>
                @error('phone')<div class="mo-form-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>@enderror
            </div>

            <div class="mo-field">
                <label>Isi Pesan <span class="req">*</span></label>
                <textarea name="message" class="mo-textarea" rows="5" placeholder="Tulis pesan..." required>{{ old('message') }}</textarea>
                @error('message')<div class="mo-form-error"><i class="fas fa-circle-exclamation"></i> {{ $message }}</div>@enderror
            </div>
        </div>

        <div class="mo-form-footer">
            <a href="{{ route('mo.whatsapp') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-paper-plane"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var sel = document.getElementById('wa-contact');
        var phone = document.getElementById('wa-phone');
        if (sel && phone) {
            sel.addEventListener('change', function () {
                var opt = sel.options[sel.selectedIndex];
                var val = opt ? (opt.getAttribute('data-phone') || '') : '';
                if (val) phone.value = val;
            });
        }
    })();
</script>
@endpush
