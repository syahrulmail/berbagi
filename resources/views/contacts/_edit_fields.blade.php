{{--
    Field form edit kontak untuk dimuat di dalam modal rincian (contacts.index).
    Parameters:
    - $contact : model Contact
    - $branches, $agents (dipass oleh ContactController@editFields)
    Form tidak memakai <form> global; AJAX memakai FormData dari container #contact-modal-form.
--}}
<form id="contact-modal-form">
    <div class="form-frame">
        <div class="form-row">
            <div class="form-group">
                <label for="branch_id">Cabang</label>
                <select id="branch_id" name="branch_id">
                    <option value="">— Pilih Cabang —</option>
                    @foreach($branches as $branch)
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
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="modal_contact_name">Nama *</label>
            <input type="text" id="modal_contact_name" name="name" value="{{ old('name', $contact->name) }}" required>
        </div>
        <div class="form-group">
            <label for="modal_contact_phone">No. WhatsApp *</label>
            <input type="text" id="modal_contact_phone" name="phone" value="{{ old('phone', $contact->phone) }}" required>
        </div>
    </div>

    <div class="form-group">
        <label for="modal_contact_status">Status *</label>
        <select id="modal_contact_status" name="status">
            <option value="prospect" {{ old('status', $contact->status) == 'prospect' ? 'selected' : '' }}>Prospek</option>
            <option value="contacted" {{ old('status', $contact->status) == 'contacted' ? 'selected' : '' }}>Simpan</option>
            <option value="donated" {{ old('status', $contact->status) == 'donated' ? 'selected' : '' }}>Wakif</option>
            <option value="churned" {{ old('status', $contact->status) == 'churned' ? 'selected' : '' }}>Stop</option>
        </select>
    </div>

    <div class="form-group">
        <label for="modal_contact_notes">Catatan</label>
        <textarea id="modal_contact_notes" name="notes" rows="3">{{ old('notes', $contact->notes) }}</textarea>
    </div>
</form>
