(function () {
    'use strict';

    var root = window.MoApp || {};

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    /* ---------- Bottom sheet manager ---------- */
    var sheets = {};

    function openSheet(id) {
        var sheet = document.getElementById(id);
        var backdrop = document.querySelector('.mo-sheet-backdrop[data-for="' + id + '"]');
        if (!sheet) return;
        sheet.classList.add('open');
        if (backdrop) backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeSheet(id) {
        var sheet = document.getElementById(id);
        var backdrop = document.querySelector('.mo-sheet-backdrop[data-for="' + id + '"]');
        if (sheet) sheet.classList.remove('open');
        if (backdrop) backdrop.classList.remove('open');
        document.body.style.overflow = '';
    }

    function closeAllSheets() {
        Object.keys(sheets).forEach(closeSheet);
        document.querySelectorAll('.mo-sheet.open').forEach(function (s) { s.classList.remove('open'); });
        document.querySelectorAll('.mo-sheet-backdrop.open').forEach(function (b) { b.classList.remove('open'); });
        document.body.style.overflow = '';
    }

    sheets.open = openSheet;
    sheets.close = closeSheet;

    /* ---------- Detail sheets (Donasi & Kontak) ---------- */
    function fmtRp(n) {
        n = Number(n) || 0;
        return 'Rp ' + Math.round(n).toLocaleString('id-ID');
    }

    function loadDonationDetail(id, cb) {
        var url = root.api + '/donasi/' + id + '/detail';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || data.error) throw new Error((data && data.error) || 'Gagal memuat');
                cb(data);
            })
            .catch(function (e) {
                var body = document.getElementById('mo-donation-sheet-body');
                if (body) body.innerHTML = '<div class="mo-empty"><i class="fas fa-circle-exclamation"></i><p>Gagal memuat data.</p></div>';
            });
    }

    function renderDonationDetail(data) {
        var itemsHtml = '';
        (data.items || []).forEach(function (it) {
            itemsHtml += '<div class="mo-row" style="box-shadow:none;background:#f6faf9;padding:10px 13px;border-radius:12px;margin-bottom:8px;">' +
                '<div class="mo-row-body"><div class="mo-row-sub" style="font-size:10.5px;">' + (it.category_label || '') + '</div>' +
                '<div class="mo-row-title" style="font-size:13px;">' + (it.program_name || '') + '</div></div>' +
                '<div class="mo-row-end"><span class="amount" style="font-size:12.5px;">' + (it.amount_formatted || '') + '</span></div></div>';
        });

        var body = document.getElementById('mo-donation-sheet-body');
        if (!body) return;

        body.innerHTML =
            '<div class="mo-detail-grid">' +
                '<div class="mo-detail-item"><div class="mo-detail-label">Donatur</div><div class="mo-detail-value">' + esc(data.contact || '-') + '</div></div>' +
                '<div class="mo-detail-item"><div class="mo-detail-label">Tanggal</div><div class="mo-detail-value">' + esc(data.donation_date_formatted || '-') + '</div></div>' +
                '<div class="mo-detail-item"><div class="mo-detail-label">Cabang</div><div class="mo-detail-value">' + esc(data.branch || '-') + '</div></div>' +
                '<div class="mo-detail-item"><div class="mo-detail-label">Agen</div><div class="mo-detail-value">' + esc(data.agen || '-') + '</div></div>' +
                '<div class="mo-detail-item"><div class="mo-detail-label">Metode</div><div class="mo-detail-value">' + esc(data.payment_method_label || '-') + '</div></div>' +
                '<div class="mo-detail-item"><div class="mo-detail-label">Dicatat oleh</div><div class="mo-detail-value">' + esc(data.creator || '-') + '</div></div>' +
            '</div>' +
            '<div class="mo-section-title">Program Donasi</div>' +
            (itemsHtml || '<div class="mo-empty"><p>Tanpa rincian program.</p></div>') +
            '<div class="mo-detail-item full" style="margin-top:4px;background:#eefaf8;"><div class="mo-detail-label">Total Donasi</div><div class="mo-detail-value amount">' + esc(data.amount_formatted || '-') + '</div></div>' +
            ((data.note) ? '<div class="mo-detail-item full" style="margin-top:10px;"><div class="mo-detail-label">Catatan</div><div class="mo-detail-value">' + esc(data.note) + '</div></div>' : '') +
            ((data.proof_url) ? '<a href="' + esc(data.proof_url) + '" target="_blank" rel="noopener" style="display:block;margin-top:12px;text-align:center;background:#eefaf8;color:var(--mo-primary);font-weight:600;font-size:12.5px;padding:11px;border-radius:12px;text-decoration:none;"><i class="fas fa-image"></i> Lihat Bukti Pembayaran</a>' : '');
    }

    function loadContactDetail(id, cb) {
        var url = root.api + '/kontak/' + id + '/detail';
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || data.error) throw new Error((data && data.error) || 'Gagal memuat');
                cb(data);
            })
            .catch(function (e) {
                var body = document.getElementById('mo-contact-sheet-body');
                if (body) body.innerHTML = '<div class="mo-empty"><i class="fas fa-circle-exclamation"></i><p>Gagal memuat data.</p></div>';
            });
    }

    function renderContactDetail(data) {
        var body = document.getElementById('mo-contact-sheet-body');
        if (!body) return;
        var statusCls = { prospect: 'gray', contacted: 'blue', donated: 'green', churned: 'red' }[data.status] || 'gray';
        var waHref = data.phone ? 'https://wa.me/' + data.phone.replace(/[^0-9]/g, '') : '#';

        body.innerHTML =
            '<div style="display:flex;align-items:center;gap:13px;margin-bottom:16px;">' +
                '<div class="mo-avatar">' + esc((data.name || '?').charAt(0).toUpperCase()) + '</div>' +
                '<div style="flex:1;min-width:0;">' +
                    '<div style="font-size:16px;font-weight:700;color:var(--mo-text);">' + esc(data.name || '-') + '</div>' +
                    '<div class="mo-row-sub" style="margin-top:3px;">' + esc(data.phone || '-') + '</div>' +
                '</div>' +
                '<span class="mo-badge ' + statusCls + '">' + esc(data.status_label || data.status || '') + '</span>' +
            '</div>' +
            '<div class="mo-detail-grid">' +
                '<div class="mo-detail-item"><div class="mo-detail-label">Cabang</div><div class="mo-detail-value">' + esc(data.branch || '-') + '</div></div>' +
                '<div class="mo-detail-item"><div class="mo-detail-label">Agen</div><div class="mo-detail-value">' + esc(data.agen || '-') + '</div></div>' +
                '<div class="mo-detail-item"><div class="mo-detail-label">Jumlah Donasi</div><div class="mo-detail-value">' + (data.donation_count != null ? data.donation_count : '-') + '</div></div>' +
                '<div class="mo-detail-item"><div class="mo-detail-label">Total Donasi</div><div class="mo-detail-value">' + esc(data.donation_total_formatted || '-') + '</div></div>' +
            '</div>' +
            ((data.notes) ? '<div class="mo-detail-item full" style="margin-top:10px;"><div class="mo-detail-label">Catatan</div><div class="mo-detail-value">' + esc(data.notes) + '</div></div>' : '') +
            ((data.phone) ? '<a href="' + waHref + '" target="_blank" rel="noopener" style="display:block;margin-top:14px;text-align:center;background:#25d366;color:#fff;font-weight:700;font-size:13px;padding:13px;border-radius:14px;text-decoration:none;"><i class="fab fa-whatsapp"></i> Chat WhatsApp</a>' : '');
    }

    function esc(s) {
        var div = document.createElement('div');
        div.textContent = s == null ? '' : String(s);
        return div.innerHTML;
    }

    /* ---------- Init ---------- */
    onReady(function () {
        // Sheet backdrop click
        document.addEventListener('click', function (e) {
            var backdrop = e.target.closest('.mo-sheet-backdrop');
            if (backdrop) {
                var target = backdrop.getAttribute('data-for');
                if (target) closeSheet(target);
            }
            var closeBtn = e.target.closest('.mo-sheet-close');
            if (closeBtn) {
                var sheet = closeBtn.closest('.mo-sheet');
                if (sheet) closeSheet(sheet.id);
            }
            var cancelBtn = e.target.closest('[data-sheet-cancel]');
            if (cancelBtn) {
                var sid = cancelBtn.getAttribute('data-sheet-cancel');
                closeSheet(sid);
            }
        });

        // Donation detail rows
        document.addEventListener('click', function (e) {
            var row = e.target.closest('[data-donation-detail]');
            if (row) {
                var id = row.getAttribute('data-donation-detail');
                var body = document.getElementById('mo-donation-sheet-body');
                if (body) body.innerHTML =
                    '<div style="padding:6px 2px 18px;">' +
                        '<div class="mo-skeleton" style="height:16px;width:60%;margin-bottom:10px;"></div>' +
                        '<div class="mo-skeleton" style="height:12px;width:100%;margin-bottom:8px;"></div>' +
                        '<div class="mo-skeleton" style="height:12px;width:85%;margin-bottom:8px;"></div>' +
                        '<div class="mo-skeleton" style="height:12px;width:70%;"></div>' +
                    '</div>';
                openSheet('mo-donation-sheet');
                loadDonationDetail(id, renderDonationDetail);
            }
        });

        // Contact detail rows
        document.addEventListener('click', function (e) {
            var row = e.target.closest('[data-contact-detail]');
            if (row) {
                var id = row.getAttribute('data-contact-detail');
                var body = document.getElementById('mo-contact-sheet-body');
                if (body) body.innerHTML =
                    '<div style="padding:6px 2px 18px;">' +
                        '<div class="mo-skeleton" style="height:18px;width:70%;margin-bottom:10px;"></div>' +
                        '<div class="mo-skeleton" style="height:12px;width:100%;margin-bottom:8px;"></div>' +
                        '<div class="mo-skeleton" style="height:12px;width:80%;"></div>' +
                    '</div>';
                openSheet('mo-contact-sheet');
                loadContactDetail(id, renderContactDetail);
            }
        });

        // Program cards open public page
        document.addEventListener('click', function (e) {
            var card = e.target.closest('[data-program-slug]');
            if (card) {
                var slug = card.getAttribute('data-program-slug');
                if (slug) window.location.href = '/program/' + slug;
            }
        });

        // Segmented control
        document.addEventListener('click', function (e) {
            var seg = e.target.closest('.mo-segmented-item');
            if (!seg) return;
            var group = seg.closest('.mo-segmented');
            group.querySelectorAll('.mo-segmented-item').forEach(function (i) { i.classList.remove('active'); });
            seg.classList.add('active');
            var form = group.closest('form');
            if (form) form.submit();
        });

        // FAB quick actions (jika data-href)
        document.addEventListener('click', function (e) {
            var fab = e.target.closest('.mo-fab');
            if (fab && fab.getAttribute('data-href')) {
                window.location.href = fab.getAttribute('data-href');
            }
        });

        // Auto close alerts
        document.querySelectorAll('.mo-flash').forEach(function (el) {
            setTimeout(function () { el.style.transition = 'opacity .5s'; el.style.opacity = '0'; }, 3500);
            setTimeout(function () { el.remove(); }, 4100);
        });
    });

    window.MoApp.sheets = sheets;
})();
