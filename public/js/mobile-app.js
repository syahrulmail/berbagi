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
                '<div class="mo-row-body"><div class="mo-row-sub" style="font-size:10.5px;">' + esc(it.category_label || '') + '</div>' +
                '<div class="mo-row-title" style="font-size:13px;">' + esc(it.program_name || '') + '</div></div>' +
                '<div class="mo-row-end"><span class="amount" style="font-size:12.5px;">' + esc(it.amount_formatted || '') + '</span></div></div>';
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
            ((data.proof_url) ? '<a href="' + esc(data.proof_url) + '" target="_blank" rel="noopener" style="display:block;margin-top:12px;text-align:center;background:#eefaf8;color:var(--mo-primary);font-weight:600;font-size:12.5px;padding:11px;border-radius:12px;text-decoration:none;"><i class="fas fa-image"></i> Lihat Bukti Pembayaran</a>' : '') +
            ((data.can_edit && data.edit_url) ? '<a href="' + esc(data.edit_url) + '" style="display:block;margin-top:12px;text-align:center;background:var(--mo-primary);color:#fff;font-weight:700;font-size:13px;padding:13px;border-radius:14px;text-decoration:none;"><i class="fas fa-pen"></i> Edit Donasi</a>' : '');
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

        var donationRows = '';
        (data.donations || []).forEach(function (d) {
            donationRows += '<div class="mo-contact-donation">' +
                '<div class="mo-contact-donation-top">' +
                    '<span class="cd-date">' + esc(d.date || '-') + '</span>' +
                    '<span class="cd-cat">' + esc(d.category || '-') + '</span>' +
                    '<span class="cd-amount">' + esc(d.amount_formatted || '') + '</span>' +
                '</div>' +
                '<div class="cd-program">' + esc(d.program_name || '-') + '</div>' +
            '</div>';
        });

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
                '<div class="mo-detail-item"><div class="mo-detail-label">Jumlah Donasi</div><div class="mo-detail-value">' + esc(data.donation_count != null ? data.donation_count : '-') + '</div></div>' +
                '<div class="mo-detail-item"><div class="mo-detail-label">Total Donasi</div><div class="mo-detail-value">' + esc(data.donation_total_formatted || '-') + '</div></div>' +
            '</div>' +
            ((data.notes) ? '<div class="mo-detail-item full" style="margin-top:10px;"><div class="mo-detail-label">Catatan</div><div class="mo-detail-value">' + esc(data.notes) + '</div></div>' : '') +
            ((donationRows) ? '<div class="mo-section-title">Rincian Donasi (' + data.donations.length + ')</div>' + donationRows : '') +
            ((data.phone) ? '<a href="' + waHref + '" target="_blank" rel="noopener" style="display:block;margin-top:14px;text-align:center;background:#25d366;color:#fff;font-weight:700;font-size:13px;padding:13px;border-radius:14px;text-decoration:none;"><i class="fab fa-whatsapp"></i> Chat WhatsApp</a>' : '') +
            ((data.can_edit && data.edit_url) ? '<a href="' + esc(data.edit_url) + '" style="display:block;margin-top:10px;text-align:center;background:var(--mo-primary);color:#fff;font-weight:700;font-size:13px;padding:13px;border-radius:14px;text-decoration:none;"><i class="fas fa-pen"></i> Edit Kontak</a>' : '');
    }

    function esc(s) {
        var div = document.createElement('div');
        div.textContent = s == null ? '' : String(s);
        return div.innerHTML;
    }

    /* ---------- Program donors sheet ---------- */
    function loadProgramDonors(url, cb) {
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || data.error) throw new Error((data && data.error) || 'Gagal memuat');
                cb(data);
            })
            .catch(function () {
                var body = document.getElementById('mo-donor-sheet-body');
                if (body) body.innerHTML = '<div class="mo-empty"><i class="fas fa-circle-exclamation"></i><p>Gagal memuat data.</p></div>';
            });
    }

    function renderProgramDonors(data) {
        var title = document.getElementById('mo-donor-sheet-title');
        if (title) title.textContent = 'Donatur · ' + (data.program_name || '');

        var body = document.getElementById('mo-donor-sheet-body');
        if (!body) return;

        if (!data.donors || !data.donors.length) {
            body.innerHTML = '<div class="mo-empty"><i class="fas fa-users"></i><p>Belum ada donatur pada program ini.</p></div>';
            return;
        }

        var html = '<div style="font-size:12px;color:var(--mo-muted);margin-bottom:10px;">' + data.count + ' donatur</div>';
        data.donors.forEach(function (d) {
            html += '<div class="mo-row" style="box-shadow:none;background:#f6faf9;padding:11px 13px;border-radius:12px;margin-bottom:8px;align-items:center;">' +
                '<div class="mo-row-icon blue">' + esc((d.name || '?').charAt(0).toUpperCase()) + '</div>' +
                '<div class="mo-row-body">' +
                    '<div class="mo-row-title" style="font-size:13.5px;">' + esc(d.name || '-') + '</div>' +
                    '<div class="mo-row-sub">' + esc(d.phone || '-') + ' · ' + esc(d.agen || '-') + '</div>' +
                '</div>' +
                '<div class="mo-row-end"><div class="amount" style="font-size:12.5px;">' + esc(d.total_formatted || '') + '</div>' +
                '<div class="date">' + esc(d.count || 0) + 'x donasi</div></div></div>';
        });
        body.innerHTML = html;
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

        // Quick add tab (tombol + tengah)
        var addTab = document.getElementById('mo-add-tab');
        if (addTab) {
            addTab.addEventListener('click', function () { openSheet('mo-add-sheet'); });
        }

        // Collapsible filter panel
        document.querySelectorAll('[data-filter-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = document.getElementById(btn.getAttribute('data-filter-toggle'));
                if (!target) return;
                var opening = target.hasAttribute('hidden');
                if (opening) {
                    target.removeAttribute('hidden');
                } else {
                    target.setAttribute('hidden', '');
                }
                btn.classList.toggle('open', opening);
                btn.setAttribute('aria-expanded', opening ? 'true' : 'false');
            });
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
            if (e.target.closest('.mo-program-edit') || e.target.closest('.mo-program-edit-btn') || e.target.closest('.mo-program-donors') || e.target.closest('.mo-program-share')) return;
            var card = e.target.closest('[data-program-slug]');
            if (card) {
                var slug = card.getAttribute('data-program-slug');
                if (slug) window.location.href = '/program/' + slug;
            }
        });

        // Program donors sheet
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-program-donors]');
            if (!btn) return;
            e.preventDefault();
            var url = btn.getAttribute('data-program-donors');
            var body = document.getElementById('mo-donor-sheet-body');
            if (body) body.innerHTML =
                '<div style="padding:6px 2px 18px;">' +
                    '<div class="mo-skeleton" style="height:16px;width:55%;margin-bottom:12px;"></div>' +
                    '<div class="mo-skeleton" style="height:56px;margin-bottom:8px;border-radius:12px;"></div>' +
                    '<div class="mo-skeleton" style="height:56px;margin-bottom:8px;border-radius:12px;"></div>' +
                    '<div class="mo-skeleton" style="height:56px;border-radius:12px;"></div>' +
                '</div>';
            openSheet('mo-donor-sheet');
            loadProgramDonors(url, renderProgramDonors);
        });

        // Program share (Web Share API, fallback copy)
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-program-share]');
            if (!btn) return;
            e.preventDefault();
            var url = btn.getAttribute('data-program-share');
            var title = btn.getAttribute('data-share-title') || 'Program';
            if (navigator.share) {
                navigator.share({ title: title, url: url }).catch(function () {});
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function () {
                    var toast = document.createElement('div');
                    toast.className = 'mo-flash-stack';
                    toast.innerHTML = '<div class="mo-toast mo-toast--success"><i class="fas fa-circle-check"></i><span>Link program disalin.</span></div>';
                    document.querySelector('.mo-app').appendChild(toast);
                    setTimeout(function () { toast.remove(); }, 2600);
                }).catch(function () { window.prompt('Salin link program:', url); });
            } else {
                window.prompt('Salin link program:', url);
            }
        });

        // Segmented control
        document.addEventListener('click', function (e) {
            var seg = e.target.closest('.mo-segmented-item');
            if (!seg) return;
            var group = seg.closest('.mo-segmented');
            if (group) {
                group.querySelectorAll('.mo-segmented-item').forEach(function (i) { i.classList.remove('active'); });
            }
            seg.classList.add('active');
            if (seg.tagName === 'BUTTON' && (seg.getAttribute('type') || 'submit') === 'submit') {
                return;
            }
            var form = group ? group.closest('form') : null;
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
        document.querySelectorAll('.mo-flash, .mo-toast').forEach(function (el) {
            setTimeout(function () { el.style.transition = 'opacity .5s'; el.style.opacity = '0'; }, 3500);
            setTimeout(function () { el.remove(); }, 4100);
        });
    });

    window.MoApp.sheets = sheets;
})();
