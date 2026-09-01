(function () {
    'use strict';

    var modal = document.getElementById('donation-detail-modal');
    var body = document.getElementById('donation-detail-body');
    var errorBox = document.getElementById('donation-detail-error');
    var editBtn = document.getElementById('donation-detail-edit-btn');
    var saveBtn = document.getElementById('donation-detail-save-btn');
    var closeBtns = document.querySelectorAll('[data-donation-detail-close]');
    var currentId = null;
    var editing = false;
    var busy = false;

    if (!modal || !body) return;

    var cfg = window.DonationDetailConfig || {};
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

    function renderDetail(d) {
        var itemsHtml = (d.items || []).map(function (it) {
            return '<tr>' +
                '<td>' + (it.category_label && it.category_label !== '-' ? '<span class="badge">' + escapeHtml(it.category_label) + '</span>' : '<span class="muted">-</span>') + '</td>' +
                '<td>' + escapeHtml(it.program_name) + '</td>' +
                '<td class="detail-amount">' + escapeHtml(it.amount_formatted) + '</td>' +
                '</tr>';
        }).join('');

        var proofHtml = d.proof_url
            ? '<a href="' + escapeAttr(d.proof_url) + '" target="_blank" rel="noopener"><img src="' + escapeAttr(d.proof_url) + '" alt="Bukti Pembayaran" class="detail-proof"></a>'
            : '<span class="muted">Tidak ada</span>';

        var contactValue = d.contact && d.contact !== '-'
            ? escapeHtml(d.contact) + (d.contact_phone && d.contact_phone !== '-' ? ' (' + escapeHtml(d.contact_phone) + ')' : '')
            : '<span class="muted">-</span>';

        body.innerHTML =
            '<div class="donation-detail">' +
                '<div class="detail-grid">' +
                    detailItem('Cabang', d.branch) +
                    detailItem('Agent', d.agen) +
                '</div>' +
                detailItem('Tanggal Donasi', d.donation_date_formatted) +
                detailItem('Kontak Donatur', contactValue) +
                '<div class="detail-section">' +
                    '<h4>Info Donatur</h4>' +
                    '<p>' + (d.donor_info ? escapeHtml(d.donor_info) : '<span class="muted">-</span>') + '</p>' +
                '</div>' +
                '<div class="detail-section">' +
                    '<h4>Program Donasi</h4>' +
                    '<table class="detail-items-table">' +
                        '<thead><tr><th>Kategori Program</th><th>Program</th><th>Nominal</th></tr></thead>' +
                        '<tbody>' + (itemsHtml || '<tr><td colspan="3" class="muted">-</td></tr>') + '</tbody>' +
                    '</table>' +
                '</div>' +
                detailItem('Total Donasi', '<strong>' + escapeHtml(d.amount_formatted) + '</strong>') +
                detailItem('Metode Pembayaran', d.payment_method_label) +
                '<div class="detail-section">' +
                    '<h4>Catatan</h4>' +
                    '<p>' + (d.note ? escapeHtml(d.note) : '<span class="muted">-</span>') + '</p>' +
                '</div>' +
                '<div class="detail-section">' +
                    '<h4>Bukti Pembayaran</h4>' +
                    proofHtml +
                '</div>' +
                detailItem('Dicatat oleh', (d.creator || '-') + ' · ' + (d.created_at_formatted || '-')) +
            '</div>';
    }

    function detailItem(label, value) {
        return '<div class="detail-item">' +
            '<span class="detail-label">' + escapeHtml(label) + '</span>' +
            '<span class="detail-value">' + (value || '<span class="muted">-</span>') + '</span>' +
            '</div>';
    }

    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttr(s) {
        return escapeHtml(s).replace(/`/g, '&#096;');
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
                    showError((r.data && r.data.message) ? r.data.message : 'Gagal memuat detail donasi.');
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
                if (window.DonationForm) {
                    window.DonationForm.init();
                }
            })
            .catch(function () {
                showError('Terjadi kesalahan jaringan. Coba lagi.');
                setMode(true);
            });
    }

    function doSave() {
        if (busy) return;
        var form = document.getElementById('donation-modal-form');
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
        var trigger = e.target.closest ? e.target.closest('[data-donation-detail]') : null;
        if (trigger) {
            e.preventDefault();
            currentId = trigger.getAttribute('data-donation-detail');
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
