(function () {
    'use strict';

    function mountAll() {
        if (!window.Vue) return;
        document.querySelectorAll('[data-vue-app]').forEach(function (el) {
            var name = el.getAttribute('data-vue-app');
            var def = window.BerbagiComponents && window.BerbagiComponents[name];
            if (!def) return;

            var props = {};
            var dataEl = el.querySelector('script[type="application/json"]');
            if (dataEl) {
                try { props = JSON.parse(dataEl.textContent); } catch (e) { /* ignore */ }
            }

            window.Vue.createApp(def, props).mount(el);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountAll);
    } else {
        mountAll();
    }
})();
