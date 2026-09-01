{{--
    Modal Tambah Kontak Baru (Manual) untuk halaman Catat/Edit Donasi.
    Ditempatkan DI LUAR form donasi utama.
    Memerlukan variabel: $branches (aktif), $agents (visibleAgents dari DonationController).
--}}
<div class="modal-backdrop" id="contact-quick-modal" role="dialog" aria-modal="true">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title"><i class="fas fa-user-plus"></i> Tambah Kontak Baru (Manual)</span>
            <button type="button" class="modal-close" data-contact-quick-close>&times;</button>
        </div>
        <form method="POST" action="{{ route('contacts.quick') }}">
            @csrf
            <div class="modal-body">
                <div class="modal-error" data-contact-quick-error></div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="cq_name">Nama *</label>
                        <input type="text" id="cq_name" name="name" placeholder="Nama donatur..." required>
                    </div>
                    <div class="form-group">
                        <label for="cq_phone">No. WhatsApp *</label>
                        <input type="text" id="cq_phone" name="phone" placeholder="62812xxxxxxx" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="cq_branch_id">Cabang</label>
                        <select id="cq_branch_id" name="branch_id">
                            <option value="">— Pilih Cabang —</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cq_agen_id">Agent</label>
                        <select id="cq_agen_id" name="agen_id">
                            <option value="">— Pilih Agen —</option>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}" data-branch="{{ $agent->branch_id ?? '' }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="cq_status">Status</label>
                    <select id="cq_status" name="status">
                        <option value="prospect" selected>Prospek</option>
                        <option value="contacted">Simpan</option>
                        <option value="donated">Wakif</option>
                        <option value="churned">Stop</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-contact-quick-close>Batal</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Kontak</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var btn = document.querySelector('[data-contact-quick-add]');
        var modal = document.getElementById('contact-quick-modal');
        if (!btn || !modal) return;

        var errorBox = modal.querySelector('[data-contact-quick-error]');
        var form = modal.querySelector('form');
        var closeBtns = modal.querySelectorAll('[data-contact-quick-close]');
        var branchSel = modal.querySelector('select[name="branch_id"]');
        var agentSel = modal.querySelector('select[name="agen_id"]');

        function open() {
            errorBox.classList.remove('show');
            errorBox.textContent = '';
            modal.classList.add('open');
        }

        function close() {
            modal.classList.remove('open');
        }

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            open();
        });

        Array.prototype.forEach.call(closeBtns, function (b) {
            b.addEventListener('click', close);
        });

        modal.addEventListener('click', function (e) {
            if (e.target === modal) close();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('open')) close();
        });

        if (branchSel && agentSel) {
            var agentOptions = Array.prototype.slice.call(agentSel.options, 1);
            branchSel.addEventListener('change', function () {
                var b = branchSel.value;
                var anyVisible = false;
                agentOptions.forEach(function (o) {
                    var show = !b || o.getAttribute('data-branch') === b;
                    o.style.display = show ? '' : 'none';
                    if (show) anyVisible = true;
                });
                if (agentSel.value && !agentOptions.some(function (o) { return o.value === agentSel.value && o.style.display !== 'none'; })) {
                    agentSel.value = '';
                }
                if (!anyVisible) agentSel.value = '';
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            errorBox.classList.remove('show');
            errorBox.textContent = '';

            var token = document.querySelector('meta[name="csrf-token"]');
            var fd = new FormData(form);
            if (token) fd.set('_token', token.getAttribute('content'));

            fetch(form.getAttribute('action'), {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json' }
            })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (r) {
                if (!r.ok) {
                    var msg = (r.data && r.data.message) ? r.data.message : 'Gagal menyimpan kontak.';
                    if (r.data && r.data.errors) {
                        var keys = Object.keys(r.data.errors);
                        if (keys.length && r.data.errors[keys[0]] && r.data.errors[keys[0]][0]) {
                            msg = r.data.errors[keys[0]][0];
                        }
                    }
                    errorBox.textContent = msg;
                    errorBox.classList.add('show');
                    return;
                }

                var c = r.data.contact;
                var hidden = document.querySelector('[data-searchable-select] input[name="contact_id"]');
                if (hidden) {
                    var root = hidden.closest('[data-searchable-select]');
                    if (root) {
                        var list = root.querySelector('.searchable-select-list');
                        var li = document.createElement('li');
                        li.setAttribute('data-value', c.id);
                        li.setAttribute('data-search', c.name + ' ' + (c.phone || ''));
                        li.setAttribute('data-phone', (c.phone || '').replace(/\D+/g, ''));
                        li.textContent = c.name + (c.phone ? ' (' + c.phone + ')' : '');
                        list.appendChild(li);
                        root.dispatchEvent(new CustomEvent('searchable-items-refresh'));
                        root.querySelector('.searchable-select-input').value = c.name + (c.phone ? ' (' + c.phone + ')' : '');
                        hidden.value = c.id;
                        root.dispatchEvent(new CustomEvent('searchable-selected', { detail: { value: c.id } }));
                    }
                }

                close();
                form.reset();
                if (branchSel) branchSel.value = '';
                if (agentSel) agentSel.value = '';
                var statusSel = modal.querySelector('select[name="status"]');
                if (statusSel) statusSel.value = 'prospect';
            })
            .catch(function () {
                errorBox.textContent = 'Terjadi kesalahan jaringan. Coba lagi.';
                errorBox.classList.add('show');
            });
        });
    })();
</script>
