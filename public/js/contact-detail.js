(function () {
    'use strict';

    var modal = document.getElementById('contact-detail-modal');
    var body = document.getElementById('contact-detail-body');
    var errorBox = document.getElementById('contact-detail-error');
    var editBtn = document.getElementById('contact-detail-edit-btn');
    var saveBtn = document.getElementById('contact-detail-save-btn');
    var closeBtns = document.querySelectorAll('[data-contact-detail-close]');
    var currentId = null;
    var editing = false;
    var busy = false;

    if (!modal || !body) return;

    var cfg = window.ContactDetailConfig || {};
    var urlDetail = cfg.detailUrl || '';
    var urlEditFields = cfg.editFieldsUrl || '';
    var urlUpdate = cfg.updateUrl || '';

    function buildUrl(base, id) {
        return base.replace('__ID__', encodeURIComponent(id));
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function fetchJson(url, options) {
        return fetch(url, options).then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, data: data };
            });
        });
    }

    function showError(msg) {
        errorBox.textContent = msg || '';
        errorBox.classList.toggle('show', !!msg);
    }

    function open() {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
        errorBox.classList.remove('show');
        errorBox.textContent = '';
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderDetail(d) {
        var statusHtml = '<span class="badge ' + escapeHtml(d.status_color) + '">' + escapeHtml(d.status_label) + '</span>';
        var notesHtml = d.notes
            ? escapeHtml(d.notes)
            : '<span class="muted">-</span>';

        body.innerHTML =
            '<div class="donation-detail">' +
                '<div class="detail-grid">' +
                    detailItem('Cabang', d.branch) +
                    detailItem('Agent', d.agen) +
                '</div>' +
                detailItem('Nama', d.name) +
                '<div class="detail-item">' +
                    '<span class="detail-label">No. WhatsApp</span>' +
                    '<span class="detail-value">' + escapeHtml(d.phone) +
                        (d.phone && d.phone !== '-' ? ' <a href="https://wa.me/' + escapeHtml(String(d.phone).replace(/\D/g, '')) + '" target="_blank" rel="noopener" title="Chat WhatsApp"><i class="fab fa-whatsapp"></i></a>' : '') +
                    '</span>' +
                '</div>' +
                detailItem('Status', statusHtml) +
                '<div class="detail-section">' +
                    '<h4>Catatan</h4>' +
                    '<p>' + notesHtml + '</p>' +
                '</div>' +
                '<div class="detail-grid">' +
                    detailItem('Total Donasi', d.donation_total_formatted) +
                    detailItem('Jumlah Donasi', String(d.donation_count)) +
                '</div>' +
                '<div class="detail-grid">' +
                    detailItem('Dibuat pada', d.created_at_formatted) +
                    detailItem('Diperbarui pada', d.updated_at_formatted) +
                '</div>' +
            '</div>';
    }

    function detailItem(label, value) {
        return '<div class="detail-item">' +
            '<span class="detail-label">' + escapeHtml(label) + '</span>' +
            '<span class="detail-value">' + (value || '<span class="muted">-</span>') + '</span>' +
            '</div>';
    }

    function setMode(viewMode) {
        editing = !viewMode;
        if (editBtn) editBtn.style.display = viewMode ? '' : 'none';
        if (saveBtn) saveBtn.style.display = viewMode ? 'none' : '';
    }

    function loadDetail(id) {
        showError('');
        setMode(true);
        body.innerHTML = '<div class="modal-loading"><i class="fas fa-spinner fa-spin"></i> Memuat detail...</div>';

        fetchJson(buildUrl(urlDetail, id), { headers: { 'Accept': 'application/json' } })
            .then(function (r) {
                if (!r.ok) {
                    showError((r.data && r.data.message) ? r.data.message : 'Gagal memuat detail kontak.');
                    return;
                }
                renderDetail(r.data);
            })
            .catch(function () {
                showError('Terjadi kesalahan jaringan. Coba lagi.');
            });
    }

    function loadEdit(id) {
        showError('');
        editing = true;
        if (editBtn) editBtn.style.display = 'none';
        if (saveBtn) saveBtn.style.display = '';
        body.innerHTML = '<div class="modal-loading"><i class="fas fa-spinner fa-spin"></i> Memuat form edit...</div>';

        fetchJson(buildUrl(urlEditFields, id), { headers: { 'Accept': 'application/json' } })
            .then(function (r) {
                if (!r.ok) {
                    showError((r.data && r.data.message) ? r.data.message : 'Gagal memuat form edit.');
                    setMode(true);
                    return;
                }
                body.innerHTML = r.data.html;
                initBranchAgentCascade();
            })
            .catch(function () {
                showError('Terjadi kesalahan jaringan. Coba lagi.');
                setMode(true);
            });
    }

    function initBranchAgentCascade() {
        var branch = document.getElementById('branch_id');
        var agen = document.getElementById('agen_id');
        if (!branch || !agen) return;

        function apply() {
            var b = branch.value;
            var sel = agen.selectedOptions && agen.selectedOptions[0];
            var keep = sel && (sel.getAttribute('data-branch') === b || !sel.getAttribute('data-branch'));
            Array.prototype.forEach.call(agen.options, function (o) {
                var br = o.getAttribute('data-branch');
                o.style.display = (br === null || br === '' || br === b) ? '' : 'none';
            });
            if (agen.value && !keep) agen.value = '';
        }

        branch.addEventListener('change', apply);
        apply();
    }

    function doSave() {
        if (busy) return;
        var form = document.getElementById('contact-modal-form');
        if (!form) return;

        showError('');
        var fd = new FormData(form);
        fd.set('_method', 'PUT');
        var token = csrfToken();
        if (token) fd.set('_token', token);

        busy = true;
        if (saveBtn) saveBtn.disabled = true;

        fetchJson(buildUrl(urlUpdate, currentId), {
            method: 'POST',
            body: fd,
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token }
        })
            .then(function (r) {
                if (!r.ok) {
                    var msg = (r.data && r.data.message) ? r.data.message : 'Gagal menyimpan perubahan.';
                    if (r.data && r.data.errors) {
                        var keys = Object.keys(r.data.errors);
                        if (keys.length && r.data.errors[keys[0]] && r.data.errors[keys[0]][0]) {
                            msg = r.data.errors[keys[0]][0];
                        }
                    }
                    showError(msg);
                    return;
                }
                loadDetail(currentId);
            })
            .catch(function () {
                showError('Terjadi kesalahan jaringan. Coba lagi.');
            })
            .finally(function () {
                busy = false;
                if (saveBtn) saveBtn.disabled = false;
            });
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest ? e.target.closest('[data-contact-detail]') : null;
        if (trigger) {
            e.preventDefault();
            currentId = trigger.getAttribute('data-contact-detail');
            open();
            loadDetail(currentId);
        }
    });

    Array.prototype.forEach.call(closeBtns, function (b) {
        b.addEventListener('click', function () {
            close();
        });
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) close();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('open')) close();
    });

    if (editBtn) {
        editBtn.addEventListener('click', function () {
            if (currentId) loadEdit(currentId);
        });
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', doSave);
    }
})();
