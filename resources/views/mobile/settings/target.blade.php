@extends('mobile.layouts.app')

@section('title', 'Target & Informasi')

@section('mobile-content')
<div class="mo-appbar">
    <div class="mo-appbar-row">
        <a href="{{ route('mo.more') }}" class="mo-appbar-back" aria-label="Kembali"><i class="fas fa-arrow-left"></i></a>
        <div style="flex:1;min-width:0;">
            <h1 class="mo-appbar-title"><i class="fas fa-bullseye" style="color:var(--mo-primary);font-size:19px;"></i> Target &amp; Informasi</h1>
            <div class="mo-appbar-sub">Target global dan info utama</div>
        </div>
    </div>
</div>

<div class="mo-content" style="padding-top:0;">
    <form method="POST" action="{{ route('mo.settings.target.update') }}" class="mo-form">
        @csrf
        @method('PUT')

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-bullseye"></i> Target Global</h3>

            <div class="mo-field">
                <label>Target Global BWA Bulanan</label>
                <input type="text" class="mo-input" value="Rp {{ number_format($totalGlobalTarget, 0, ',', '.') }}" readonly>
                <div class="mo-form-help">Otomatis dari total target donasi seluruh cabang aktif.</div>
            </div>
        </div>

        <div class="mo-form-card">
            <h3 class="mo-form-card-title"><i class="fas fa-bullhorn"></i> Informasi Halaman</h3>

            <div class="mo-field">
                <label for="trustbar_text">Teks Bar Atas (Trustbar)</label>
                <input type="text" id="trustbar_text" name="trustbar_text" class="mo-input" value="{{ old('trustbar_text', $settings['trustbar_text']) }}" maxlength="160">
            </div>

            <div class="mo-field">
                <label for="home_quote">Quote di Bawah Hero</label>
                <textarea id="home_quote" name="home_quote" class="mo-textarea" rows="4" maxlength="2000">{{ old('home_quote', $settings['home_quote']) }}</textarea>
                <div class="mo-form-help">Mendukung HTML sederhana seperti &lt;p&gt;, &lt;strong&gt;, dan &lt;blockquote&gt;.</div>
            </div>
        </div>

        <div class="mo-form-footer">
            <a href="{{ route('mo.more') }}" class="mo-btn mo-btn-ghost">Batal</a>
            <button type="submit" class="mo-btn mo-btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>
@endsection
