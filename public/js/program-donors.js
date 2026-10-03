(function () {
    'use strict';

    var modal = document.getElementById('program-donors-modal');
    var body = document.getElementById('program-donors-body');
    var nameEl = document.getElementById('program-donors-name');
    var closeBtns = document.querySelectorAll('[data-program-donors-close]');

    if (!modal || !body) return;

    var cfg = window.ProgramDonorsConfig || {};
    var url = cfg.url || '';

    function open() {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    function buildUrl(id) {
        return url.replace('__ID__', encodeURIComponent(id));
    }

    function loadDonors(id, name) {
        if (nameEl) nameEl.textContent = name || '';
        body.innerHTML = '<div class="modal-loading"><i class="fas fa-spinner fa-spin"></i> Memuat donatur...</div>';

        fetch(buildUrl(id), { headers: { 'Accept': 'application/json' } })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (r) {
                if (!r.ok) {
                    var msg = (r.data && r.data.message) ? r.data.message : 'Gagal memuat data donatur.';
                    body.innerHTML = '<div class="modal-error show">' + msg + '</div>';
                    return;
                }
                body.innerHTML = r.data.html || '';
            })
            .catch(function () {
                body.innerHTML = '<div class="modal-error show">Terjadi kesalahan jaringan. Coba lagi.</div>';
            });
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest ? e.target.closest('[data-program-donors]') : null;
        if (!trigger) return;

        e.preventDefault();
        open();
        loadDonors(
            trigger.getAttribute('data-program-donors'),
            trigger.getAttribute('data-program-name')
        );
    });

    Array.prototype.forEach.call(closeBtns, function (btn) {
        btn.addEventListener('click', close);
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) close();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape' || !modal.classList.contains('open')) return;

        var contactModal = document.getElementById('contact-detail-modal');
        if (contactModal && contactModal.classList.contains('open')) {
            return;
        }

        close();
    });
})();
