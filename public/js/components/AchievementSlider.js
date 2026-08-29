(function (Vue) {
    'use strict';
    if (!Vue) return;
    window.BerbagiComponents = window.BerbagiComponents || {};

    window.BerbagiComponents.AchievementSlider = {
        props: {
            items: { type: Array, default: function () { return []; } }
        },
        computed: {
            count: function () { return this.items.length; }
        },
        methods: {
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
        template: '<div class="achievement-grid">' +
            '<div v-if="count" class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">' +
            '  <div v-for="(item, i) in items" :key="i" class="flex items-center gap-3 rounded-2xl border border-black/5 bg-white p-3 shadow-card md:p-4">' +
            '    <div class="flex-shrink-0 grid h-11 w-11 place-items-center overflow-hidden rounded-xl text-white shadow-md md:h-12 md:w-12" :style="{ background: colorOf(item) }">' +
            '      <img v-if="item.image" :src="item.image" :alt="item.value" class="h-full w-full object-cover">' +
            '      <i v-else :class="\'fas \' + (item.icon || \'fa-trophy\')" class="ach-icon"></i>' +
            '    </div>' +
            '    <div class="min-w-0">' +
            '      <div class="ach-value" :style="{ color: colorOf(item) }">{{ item.value }}</div>' +
            '      <div class="ach-label">{{ item.label }}</div>' +
            '    </div>' +
            '  </div>' +
            '</div>' +
            '<div v-else class="grid h-24 place-items-center rounded-3xl border border-dashed border-black/5 bg-white/60 text-sm text-gray-400">Belum ada pencapaian.</div>' +
            '</div>'
    };
})(window.Vue);
