(function () {
    'use strict';

    var modal = document.getElementById('program-share-modal');
    var openBtns = document.querySelectorAll('[data-program-share]');

    if (!modal || openBtns.length === 0) return;

    var nameEl = modal.querySelector('[data-program-share-name]');
    var copiedEl = modal.querySelector('[data-share-copied]');
    var shareLinks = modal.querySelectorAll('[data-share]');
    var closeBtns = modal.querySelectorAll('[data-program-share-close]');

    var current = { url: '', title: '' };

    function buildUrl(kind) {
        var url = encodeURIComponent(current.url);
        var title = encodeURIComponent(current.title);
        var text = encodeURIComponent(current.title + ' - ' + current.url);

        switch (kind) {
            case 'whatsapp':
                return 'https://wa.me/?text=' + text;
            case 'facebook':
                return 'https://www.facebook.com/sharer/sharer.php?u=' + url;
            case 'telegram':
                return 'https://t.me/share/url?url=' + url + '&text=' + title;
            case 'x':
                return 'https://twitter.com/intent/tweet?url=' + url + '&text=' + title;
            case 'linkedin':
                return 'https://www.linkedin.com/sharing/share-offsite/?url=' + url;
        }

        return current.url;
    }

    function showCopied() {
        if (!copiedEl) return;

        copiedEl.hidden = false;
        setTimeout(function () {
            copiedEl.hidden = true;
        }, 2500);
    }

    function copyLink() {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(current.url).then(showCopied);
            return;
        }

        var input = document.createElement('input');
        input.value = current.url;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        showCopied();
    }

    function open() {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    Array.prototype.forEach.call(openBtns, function (btn) {
        btn.addEventListener('click', function () {
            current.url = btn.getAttribute('data-share-url') || '';
            current.title = btn.getAttribute('data-share-title') || '';

            if (nameEl) nameEl.textContent = current.title;
            if (copiedEl) copiedEl.hidden = true;

            Array.prototype.forEach.call(shareLinks, function (link) {
                var kind = link.getAttribute('data-share');

                if (kind === 'copy' || link.hasAttribute('data-copy-only')) return;

                link.setAttribute('href', buildUrl(kind));
            });

            open();
        });
    });

    Array.prototype.forEach.call(closeBtns, function (btn) {
        btn.addEventListener('click', close);
    });

    Array.prototype.forEach.call(shareLinks, function (link) {
        link.addEventListener('click', function (e) {
            var kind = link.getAttribute('data-share');

            if (kind === 'copy' || link.hasAttribute('data-copy-only')) {
                e.preventDefault();
                copyLink();
            }
        });
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) close();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('open')) close();
    });
})();
