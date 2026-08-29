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
            return { current: 0, timer: null };
        },
        computed: {
            count: function () { return this.items.length; }
        },
        methods: {
            go: function (i) {
                if (!this.count) return;
                this.current = (i + this.count) % this.count;
            },
            next: function () { this.go(this.current + 1); this.restart(); },
            prev: function () { this.go(this.current - 1); this.restart(); },
            restart: function () { this.stop(); this.start(); },
            start: function () {
                var self = this;
                this.timer = setInterval(function () { self.go(self.current + 1); }, self.interval);
            },
            stop: function () {
                if (this.timer) { clearInterval(this.timer); this.timer = null; }
            },
            pause: function () { this.stop(); },
            resume: function () { if (this.count > 1) { this.start(); } },
            colorOf: function (item) { return item.color || '#08A899'; },
            textColor: function (hex) {
                var h = String(hex || '').replace('#', '');
                if (h.length === 3) { h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2]; }
                if (!/^[0-9a-fA-F]{6}$/.test(h)) return '#ffffff';
                var n = parseInt(h, 16);
                var r = (n >> 16) & 255, g = (n >> 8) & 255, b = n & 255;
                return ((0.299 * r + 0.587 * g + 0.114 * b) / 255) > 0.6 ? '#0b2f2d' : '#ffffff';
            }
        },
        mounted: function () {
            if (this.count > 1) { this.start(); }
        },
        beforeUnmount: function () { this.stop(); },
        template: '<div class="achievement-shell">' +
            '<div v-if="count" class="relative">' +
            '  <div class="overflow-hidden rounded-3xl border border-black/5 bg-white shadow-card">' +
            '    <transition name="ach" mode="out-in">' +
            '      <div :key="current" class="flex items-center gap-5 p-6 md:gap-8 md:p-10">' +
            '        <div class="flex-shrink-0 grid h-20 w-20 place-items-center rounded-2xl text-white shadow-lg" :style="{ background: colorOf(items[current]) }">' +
            '          <img v-if="items[current].image" :src="items[current].image" :alt="items[current].value" class="h-full w-full rounded-2xl object-cover">' +
            '          <i v-else :class="\'fas \' + (items[current].icon || \'fa-trophy\')" class="ach-icon"></i>' +
            '        </div>' +
            '        <div class="min-w-0">' +
            '          <div class="ach-value" :style="{ color: colorOf(items[current]) }">{{ items[current].value }}</div>' +
            '          <div class="mt-1 text-sm text-gray-500">{{ items[current].label }}</div>' +
            '        </div>' +
            '      </div>' +
            '    </transition>' +
            '  </div>' +
            '  <div v-if="count > 1" class="mt-5 flex items-center justify-center gap-3">' +
            '    <button type="button" class="grid h-10 w-10 place-items-center rounded-full border border-black/5 bg-white text-primary-700 shadow-md transition hover:shadow-xl" @click="prev" aria-label="Sebelumnya"><i class="fas fa-chevron-left"></i></button>' +
            '    <div class="flex gap-2">' +
            '      <button v-for="(s, i) in items" :key="i" type="button" class="h-2 rounded-full transition-all" :class="i === current ? \'w-6 bg-primary-700\' : \'w-2 bg-primary-200\'" @click="go(i)" :aria-label="\'Pencapaian \' + (i + 1)"></button>' +
            '    </div>' +
            '    <button type="button" class="grid h-10 w-10 place-items-center rounded-full border border-black/5 bg-white text-primary-700 shadow-md transition hover:shadow-xl" @click="next" aria-label="Berikutnya"><i class="fas fa-chevron-right"></i></button>' +
            '  </div>' +
            '</div>' +
            '<div v-else class="grid h-24 place-items-center rounded-3xl border border-dashed border-black/5 bg-white/60 text-sm text-gray-400">Belum ada pencapaian.</div>' +
            '</div>'
    };
})(window.Vue);
