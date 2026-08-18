(function (Vue) {
    'use strict';
    if (!Vue) return;
    window.BerbagiComponents = window.BerbagiComponents || {};

    window.BerbagiComponents.DonutChart = {
        props: {
            value: { type: Number, default: 0 },
            size: { type: Number, default: 140 },
            stroke: { type: Number, default: 14 },
            label: { type: String, default: '' }
        },
        data: function () {
            return { display: 0 };
        },
        computed: {
            r: function () { return (this.size - this.stroke) / 2; },
            c: function () { return 2 * Math.PI * this.r; },
            offset: function () {
                var pct = Math.max(0, Math.min(100, this.display));
                return this.c - (pct / 100) * this.c;
            }
        },
        mounted: function () {
            var self = this;
            var from = 0;
            var to = Math.max(0, Math.min(100, this.value));
            var start = null;
            var dur = 1200;
            function step(ts) {
                if (start === null) start = ts;
                var p = Math.min(1, (ts - start) / dur);
                var eased = 1 - Math.pow(1 - p, 3);
                self.display = from + (to - from) * eased;
                if (p < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        },
        template: '<div class="flex flex-col items-center">' +
            '<div class="relative" :style="{ width: size + \'px\', height: size + \'px\' }">' +
            '  <svg :width="size" :height="size" class="-rotate-90">' +
            '    <circle :cx="size/2" :cy="size/2" :r="r" fill="none" stroke="#e6efee" :stroke-width="stroke"></circle>' +
            '    <circle :cx="size/2" :cy="size/2" :r="r" fill="none" stroke="url(#donutGrad)" :stroke-width="stroke" stroke-linecap="round" :stroke-dasharray="c" :stroke-dashoffset="offset"></circle>' +
            '    <defs><linearGradient id="donutGrad" x1="0%" y1="0%" x2="100%" y2="100%">' +
            '      <stop offset="0%" stop-color="#08a899"></stop><stop offset="100%" stop-color="#086e66"></stop>' +
            '    </linearGradient></defs>' +
            '  </svg>' +
            '  <div class="absolute inset-0 flex flex-col items-center justify-center">' +
            '    <span class="text-2xl font-bold text-primary-700">{{ Math.round(display) }}%</span>' +
            '  </div>' +
            '</div>' +
            '<p v-if="label" class="mt-2 text-xs text-gray-500">{{ label }}</p>' +
            '</div>'
    };
})(window.Vue);
