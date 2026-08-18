(function (Vue) {
    'use strict';
    if (!Vue) return;
    window.BerbagiComponents = window.BerbagiComponents || {};

    window.BerbagiComponents.CountUp = {
        props: {
            value: { type: Number, default: 0 },
            prefix: { type: String, default: '' },
            suffix: { type: String, default: '' },
            duration: { type: Number, default: 1400 }
        },
        data: function () {
            return { display: 0, observer: null, started: false };
        },
        methods: {
            format: function (n) {
                return Math.round(n).toLocaleString('id-ID');
            },
            run: function () {
                if (this.started) return;
                this.started = true;
                var self = this;
                var start = null;
                var from = 0;
                var to = this.value;
                var dur = this.duration;
                function step(ts) {
                    if (start === null) start = ts;
                    var p = Math.min(1, (ts - start) / dur);
                    var eased = 1 - Math.pow(1 - p, 3);
                    self.display = from + (to - from) * eased;
                    if (p < 1) { requestAnimationFrame(step); }
                    else { self.display = to; }
                }
                requestAnimationFrame(step);
            }
        },
        mounted: function () {
            var self = this;
            if ('IntersectionObserver' in window) {
                this.observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) { self.run(); if (self.observer) self.observer.disconnect(); }
                    });
                }, { threshold: 0.3 });
                this.observer.observe(this.$el);
            } else {
                this.run();
            }
        },
        beforeUnmount: function () {
            if (this.observer) this.observer.disconnect();
        },
        template: '<span>{{ prefix }}{{ format(display) }}{{ suffix }}</span>'
    };
})(window.Vue);
