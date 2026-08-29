(function (Vue) {
    'use strict';
    if (!Vue) return;
    window.BerbagiComponents = window.BerbagiComponents || {};

    window.BerbagiComponents.HeroStats = {
        props: {
            stats: { type: Array, default: function () { return []; } },
            duration: { type: Number, default: 1600 }
        },
        data: function () {
            return { observer: null, started: false, counts: {} };
        },
        methods: {
            format: function (item, n) {
                var d = typeof item.decimals === 'number' ? item.decimals : 0;
                return n.toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d });
            },
            display: function (item, i) {
                if (typeof item.number !== 'number') return item.value || '';
                var val = this.counts[i] || 0;
                var s = '';
                if (item.prefix) { s += item.prefix + ' '; }
                s += this.format(item, val);
                if (item.suffix) { s += (/^[A-Za-z0-9]/.test(item.suffix) ? ' ' : '') + item.suffix; }
                return s;
            },
            run: function () {
                if (this.started) return;
                this.started = true;
                var self = this;
                var startedAt = null;
                var dur = this.duration;
                function step(ts) {
                    if (startedAt === null) startedAt = ts;
                    var p = Math.min(1, (ts - startedAt) / dur);
                    var eased = 1 - Math.pow(1 - p, 3);
                    self.stats.forEach(function (item, i) {
                        if (typeof item.number === 'number') {
                            self.counts[i] = item.number * eased;
                        }
                    });
                    if (p < 1) { requestAnimationFrame(step); }
                    else {
                        self.stats.forEach(function (item, i) {
                            if (typeof item.number === 'number') { self.counts[i] = item.number; }
                        });
                    }
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
        template: '<div class="hero-stats-grid">' +
            '  <div v-for="(item, i) in stats" :key="i" class="hero-stat">' +
            '    <div class="hero-stat-icon"><i :class="\'fas \' + (item.icon || \'fa-hand-holding-heart\')"></i></div>' +
            '    <div class="hero-stat-value">{{ display(item, i) }}</div>' +
            '    <div class="hero-stat-label">{{ item.label }}</div>' +
            '  </div>' +
            '</div>'
    };
})(window.Vue);
