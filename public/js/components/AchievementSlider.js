(function (Vue) {
    'use strict';
    if (!Vue) return;
    window.BerbagiComponents = window.BerbagiComponents || {};

    window.BerbagiComponents.AchievementSlider = {
        props: {
            items: { type: Array, default: function () { return []; } },
            interval: { type: Number, default: 4500 }
        },
        data: function () {
            return { page: 0, perView: 3, timer: null, resizeHandler: null, observer: null, counts: {} };
        },
        computed: {
            count: function () { return this.items.length; },
            pageCount: function () { return Math.max(1, Math.ceil(this.count / this.perView)); }
        },
        methods: {
            setPerView: function () {
                var w = window.innerWidth;
                this.perView = w >= 1024 ? 3 : 2;
                if (this.page >= this.pageCount) { this.page = this.pageCount - 1; }
            },
            trackOffset: function () {
                if (!this.count) return 0;
                return (this.page * this.perView * 100) / this.count;
            },
            go: function (i) { this.page = (i + this.pageCount) % this.pageCount; },
            next: function () { this.go(this.page + 1); this.restart(); },
            prev: function () { this.go(this.page - 1); this.restart(); },
            restart: function () { this.stop(); this.start(); },
            start: function () {
                var self = this;
                this.stop();
                if (this.pageCount > 1) {
                    this.timer = setInterval(function () { self.go(self.page + 1); }, self.interval);
                }
            },
            stop: function () {
                if (this.timer) { clearInterval(this.timer); this.timer = null; }
            },
            pause: function () { this.stop(); },
            resume: function () { if (this.pageCount > 1) { this.start(); } },
            colorOf: function (item) { return item.color || '#08A899'; },
            textColor: function (hex) {
                var h = String(hex || '').replace('#', '');
                if (h.length === 3) { h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2]; }
                if (!/^[0-9a-fA-F]{6}$/.test(h)) return '#ffffff';
                var n = parseInt(h, 16);
                var r = (n >> 16) & 255, g = (n >> 8) & 255, b = n & 255;
                return ((0.299 * r + 0.587 * g + 0.114 * b) / 255) > 0.6 ? '#0b2f2d' : '#ffffff';
            },
            number: function (item) {
                return (typeof item.number === 'number') ? item.number : null;
            },
            decimals: function (item) {
                return typeof item.decimals === 'number' ? item.decimals : 0;
            },
            formatNumber: function (item, n) {
                var d = this.decimals(item);
                return n.toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d });
            },
            valueText: function (item, i) {
                var num = this.number(item);
                if (num === null) { return item.value || ''; }
                var val = (i in this.counts) ? this.counts[i] : 0;
                var s = '';
                if (item.prefix) { s += item.prefix + ' '; }
                s += this.formatNumber(item, val);
                if (item.suffix) { s += (/^[A-Za-z0-9]/.test(item.suffix) ? ' ' : '') + item.suffix; }
                return s;
            },
            runCount: function (item, i) {
                var num = this.number(item);
                if (num === null) return;
                var self = this;
                var duration = 1500;
                var start = null;
                var from = 0;
                var to = num;
                function step(ts) {
                    if (start === null) start = ts;
                    var p = Math.min(1, (ts - start) / duration);
                    var eased = 1 - Math.pow(1 - p, 3);
                    self.counts[i] = from + (to - from) * eased;
                    if (p < 1) { requestAnimationFrame(step); }
                    else { self.counts[i] = to; }
                }
                requestAnimationFrame(step);
            }
        },
        mounted: function () {
            this.setPerView();
            var self = this;
            this.resizeHandler = function () { self.setPerView(); };
            window.addEventListener('resize', this.resizeHandler);
            this.start();
            if ('IntersectionObserver' in window) {
                this.observer = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            for (var i = 0; i < self.items.length; i++) { self.runCount(self.items[i], i); }
                            if (self.observer) self.observer.disconnect();
                        }
                    });
                }, { threshold: 0.25 });
                this.observer.observe(this.$el);
            } else {
                for (var j = 0; j < this.items.length; j++) { this.counts[j] = this.number(this.items[j]) || 0; }
            }
        },
        beforeUnmount: function () {
            this.stop();
            if (this.resizeHandler) { window.removeEventListener('resize', this.resizeHandler); }
            if (this.observer) { this.observer.disconnect(); }
        },
        template: '<div class="achievement-carousel" @mouseenter="pause" @mouseleave="resume">' +
            '<div v-if="count" class="relative">' +
            '  <div class="overflow-hidden rounded-3xl border border-black/5 bg-white/70 shadow-card">' +
            '    <div class="flex transition-transform duration-500 ease-out" :style="{ transform: \'translateX(-\' + trackOffset() + \'%)\' }">' +
            '      <div v-for="(item, i) in items" :key="i" class="achievement-card" :style="{ flex: \'0 0 calc(100% / \' + perView + \')\' }">' +
            '        <div class="flex h-full flex-col items-center justify-center gap-2 rounded-2xl border border-black/5 bg-white p-4 text-center shadow-card transition duration-300 hover:-translate-y-1 hover:shadow-xl">' +
            '          <div class="grid h-12 w-12 place-items-center overflow-hidden rounded-xl text-white shadow-md" :style="{ background: colorOf(item) }">' +
            '            <img v-if="item.image" :src="item.image" :alt="item.value" class="h-full w-full object-cover">' +
            '            <i v-else :class="\'fas \' + (item.icon || \'fa-trophy\')" class="ach-icon"></i>' +
            '          </div>' +
            '          <div class="ach-value" :style="{ color: colorOf(item) }">{{ valueText(item, i) }}</div>' +
            '          <div class="ach-label">{{ item.label }}</div>' +
            '        </div>' +
            '      </div>' +
            '    </div>' +
            '  </div>' +
            '  <div v-if="pageCount > 1" class="mt-5 flex items-center justify-center gap-3">' +
            '    <button type="button" class="grid h-10 w-10 place-items-center rounded-full border border-black/5 bg-white text-primary-700 shadow-md transition hover:shadow-xl" @click="prev" aria-label="Sebelumnya"><i class="fas fa-chevron-left"></i></button>' +
            '    <div class="flex gap-2">' +
            '      <button v-for="(p, i) in pageCount" :key="i" type="button" class="h-2 rounded-full transition-all" :class="i === page ? \'w-6 bg-primary-700\' : \'w-2 bg-primary-200\'" @click="go(i)" :aria-label="\'Pencapaian \' + (i + 1)"></button>' +
            '    </div>' +
            '    <button type="button" class="grid h-10 w-10 place-items-center rounded-full border border-black/5 bg-white text-primary-700 shadow-md transition hover:shadow-xl" @click="next" aria-label="Berikutnya"><i class="fas fa-chevron-right"></i></button>' +
            '  </div>' +
            '</div>' +
            '<div v-else class="grid h-24 place-items-center rounded-3xl border border-dashed border-black/5 bg-white/60 text-sm text-gray-400">Belum ada pencapaian.</div>' +
            '</div>'
    };
})(window.Vue);
