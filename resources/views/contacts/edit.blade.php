@extends('layouts.app')

@section('title', 'Edit Kontak')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="fas fa-address-book"></i> Edit Kontak</h1>
        <p class="subtitle">{{ $contact->name }}</p>
    </div>
    <a href="{{ route('contacts.index') }}" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
</div>

<div class="card" style="max-width: 640px;">
    <form method="POST" action="{{ route('contacts.update', $contact) }}">
        @csrf
        @method('PUT')
        <div class="form-row">
            <div class="form-group">
                <label for="branch_id">Cabang</label>
                <select id="branch_id" name="branch_id">
                    <option value="">— Pilih Cabang —</option>
                    @foreach(\App\Models\Branch::where('is_active', true)->orderBy('name')->get() as $branch)
                        <option value="{{ $branch->id }}" {{ old('branch_id', $contact->branch_id) == $branch->id ? 'selected' : '' }}>
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
                        <option value="{{ $agent->id }}" data-branch="{{ $agent->branch_id ?? '' }}" {{ old('agen_id', $contact->agen_id) == $agent->id ? 'selected' : '' }}>
                            {{ $agent->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="name">Nama *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $contact->name) }}" required>
            </div>
            <div class="form-group">
                <label for="phone">No. WhatsApp *</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $contact->phone) }}" required>
            </div>
        </div>
        <div class="form-group">
            <label for="status">Status *</label>
            <select id="status" name="status">
                <option value="prospect" {{ old('status', $contact->status) == 'prospect' ? 'selected' : '' }}>Prospek</option>
                <option value="contacted" {{ old('status', $contact->status) == 'contacted' ? 'selected' : '' }}>Simpan</option>
                <option value="donated" {{ old('status', $contact->status) == 'donated' ? 'selected' : '' }}>Wakif</option>
                <option value="churned" {{ old('status', $contact->status) == 'churned' ? 'selected' : '' }}>Stop</option>
            </select>
        </div>
        <div class="form-group">
            <label for="notes">Catatan</label>
            <textarea id="notes" name="notes" rows="3">{{ old('notes', $contact->notes) }}</textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan</button>
            <a href="{{ route('contacts.index') }}" class="btn">Batal</a>
        </div>
    </form>
</div>

<script>
    (function () {
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
