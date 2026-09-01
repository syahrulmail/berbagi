@extends('layouts.app')

@section('hideAlerts', true)

@section('title', 'Tambah Kontak')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-address-book"></i> Tambah Kontak</h1>
        <p class="subtitle">Tambahkan calon donatur baru.</p>
    </div>
    <a href="{{ route('contacts.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 720px;">
    <div class="contact-tabs">
        <button type="button" class="contact-tab active" data-tab="manual">Manual</button>
        <button type="button" class="contact-tab" data-tab="paste">Tempel</button>
        <button type="button" class="contact-tab" data-tab="import">Import</button>
    </div>

    {{-- ===================== MANUAL ===================== --}}
    <div class="tab-panel" id="tab-manual">
        <form method="POST" action="{{ route('contacts.store') }}">
            @csrf
            <input type="hidden" name="tab" value="manual">
            <div class="form-row">
                <div class="form-group">
                    <label for="branch_id">Cabang</label>
                    <select id="branch_id" name="branch_id">
                        <option value="">— Pilih Cabang —</option>
                        @foreach(\App\Models\Branch::where('is_active', true)->orderBy('name')->get() as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="agen_id">Agent</label>
                    <select id="agen_id" name="agen_id">
                        <option value="">— Pilih Agen —</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" data-branch="{{ $agent->branch_id ?? '' }}" {{ old('agen_id') == $agent->id ? 'selected' : '' }}>
                                {{ $agent->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="name">Nama *</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="form-group">
                    <label for="phone">No. WhatsApp *</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="628xxxxxxx" required>
                </div>
            </div>
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status">
                    <option value="prospect" {{ old('status') == 'prospect' ? 'selected' : '' }}>Prospek</option>
                    <option value="contacted" {{ old('status') == 'contacted' ? 'selected' : '' }}>Simpan</option>
                    <option value="donated" {{ old('status') == 'donated' ? 'selected' : '' }}>Wakif</option>
                    <option value="churned" {{ old('status') == 'churned' ? 'selected' : '' }}>Stop</option>
                </select>
            </div>
            <div class="form-group">
                <label for="notes">Catatan</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Catatan tentang kontak ini...">{{ old('notes') }}</textarea>
            </div>
            @include('layouts.partials.alerts-errors')
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                <a href="{{ route('contacts.index') }}" class="btn">Batal</a>
            </div>
        </form>
    </div>

    {{-- ===================== TEMPEL ===================== --}}
    <div class="tab-panel hidden" id="tab-paste">
        <div class="import-guidance">
            <h4><i class="fas fa-circle-info"></i> Cara Menggunakan "Tempel"</h4>
            <ol>
                <li>Salin data kontak dari Excel/Google Sheets.</li>
                <li>Tempel ke kolom di bawah. <strong>Satu kontak per baris</strong>, kolom dipisah koma (<code>,</code>) dengan urutan:
                    <strong>Cabang, Agent, Nama, No. WhatsApp, Status, Catatan</strong>.</li>
                <li>Klik <strong>"Simpan Kontak"</strong>.</li>
            </ol>
            <p>Contoh (1 baris = 1 kontak):</p>
            <pre>Yogyakarta,Marwa,Bpk Budi,6281234567890,Prospek,Suka Quran
Bandung,Agen Citra,Ibu Sari,6289876543210,Wakif,Kontak dari pameran</pre>
            <p>Status yang diterima: <strong>Prospek</strong>, <strong>Simpan</strong>, <strong>Wakif</strong>, <strong>Stop</strong>.</p>
            <p class="text-muted"><i class="fas fa-triangle-exclamation"></i> Cabang dan Agent harus sesuai data yang sudah ada, dan nama Agent harus unik di cabangnya. No. WhatsApp harus berupa angka 10-15 digit (boleh diawali 0, 62, atau +62; tidak boleh duplikat dengan kontak lain atau dalam satu tempelan). Jika ada yang bermasalah, tempelan dibatalkan dan diberi tahu baris yang salah.</p>
        </div>
        <form method="POST" action="{{ route('contacts.paste') }}">
            @csrf
            <input type="hidden" name="tab" value="paste">
            <div class="form-group">
                <label for="paste_lines">Data Kontak (Tempel di sini) *</label>
                <textarea id="paste_lines" name="paste_lines" rows="10" placeholder="Yogyakarta,Marwa,Bpk Budi,6281234567890,Prospek,Suka Quran&#10;..." required>{{ old('paste_lines') }}</textarea>
            </div>
            @include('layouts.partials.alerts-errors')
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-clipboard-list"></i> Simpan Kontak</button>
                <a href="{{ route('contacts.index') }}" class="btn">Batal</a>
            </div>
        </form>
    </div>

    {{-- ===================== IMPORT ===================== --}}
    <div class="tab-panel hidden" id="tab-import">
        <div class="import-guidance">
            <h4><i class="fas fa-circle-info"></i> Cara Menggunakan "Import"</h4>
            <ol>
                <li>Siapkan file berisi data kontak dengan format <strong>.xls</strong>, <strong>.xlsx</strong>, <strong>.csv</strong>, atau <strong>.txt</strong>.</li>
                <li>Susunan kolom (tanpa judul) dengan urutan:
                    <strong>Cabang, Agent, Nama, No. WhatsApp, Status, Catatan</strong>.</li>
                <li>Boleh menyertakan <strong>baris judul</strong> (mis. <code>Cabang,Agent,Nama,No. WhatsApp,Status,Catatan</code>) di baris pertama — akan dideteksi otomatis.</li>
                <li>Pilih file, lalu klik <strong>"Import Kontak"</strong>.</li>
            </ol>
            <p>Status yang diterima: <strong>Prospek</strong>, <strong>Simpan</strong>, <strong>Wakif</strong>, <strong>Stop</strong>.</p>
            <p class="text-muted"><i class="fas fa-triangle-exclamation"></i> Cabang dan Agent harus sesuai data yang sudah ada, dan nama Agent harus unik di cabangnya. No. WhatsApp harus berupa angka 10-15 digit (boleh diawali 0, 62, atau +62; tidak boleh duplikat dengan kontak lain atau dalam satu file). Jika ada yang bermasalah, import dibatalkan dan diberi tahu baris yang salah.</p>
        </div>
        <form method="POST" action="{{ route('contacts.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="tab" value="import">
            <div class="form-group">
                <label for="import_file">File Kontak *</label>
                <input type="file" id="import_file" name="import_file" accept=".xls,.xlsx,.csv,.txt" required>
            </div>
            @include('layouts.partials.alerts-errors')
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-file-import"></i> Import Kontak</button>
                <a href="{{ route('contacts.index') }}" class="btn">Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const tabs = document.querySelectorAll('.contact-tab');
        const panels = document.querySelectorAll('.tab-panel');
        const show = (name) => {
            tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === name));
            panels.forEach(p => p.classList.toggle('hidden', p.id !== 'tab-' + name));
        };
        tabs.forEach(t => t.addEventListener('click', () => show(t.dataset.tab)));
        const initial = '{{ old("tab", "manual") }}';
        if (initial === 'paste' || initial === 'import') {
            show(initial);
        }

        const branchSel = document.getElementById('branch_id');
        const agentSel = document.getElementById('agen_id');
        if (branchSel && agentSel) {
            const agentOptions = Array.from(agentSel.options).slice(1);
            const filterAgents = () => {
                const b = branchSel.value;
                let anyVisible = false;
                agentOptions.forEach(o => {
                    const show = !b || o.dataset.branch === b;
                    o.style.display = show ? '' : 'none';
                    if (show) anyVisible = true;
                });
                if (agentSel.value && !agentOptions.some(o => o.value === agentSel.value && o.style.display !== 'none')) {
                    agentSel.value = '';
                }
                if (!anyVisible) agentSel.value = '';
            };
            branchSel.addEventListener('change', filterAgents);
            filterAgents();
        }
    })();
</script>
@endsection
