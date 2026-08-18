(function (Vue) {
    'use strict';
    if (!Vue) return;
    window.BerbagiComponents = window.BerbagiComponents || {};

    window.BerbagiComponents.BarChart = {
        props: {
            data: { type: Array, default: function () { return []; } },
            format: { type: String, default: 'currency' }
        },
        computed: {
            max: function () {
                var m = 1;
                this.data.forEach(function (d) { m = Math.max(m, Number(d.value) || 0); });
                return m;
            }
        },
        methods: {
            label: function (v) {
                v = Number(v) || 0;
                if (this.format === 'currency') {
                    return 'Rp ' + Math.round(v).toLocaleString('id-ID');
                }
                return Math.round(v).toLocaleString('id-ID');
            },
            height: function (v) {
                return Math.max(4, Math.round((Number(v) || 0) / this.max * 120));
            }
        },
        template: '<div class="trend-bars">' +
            '<div v-for="(d, i) in data" :key="i" class="trend-col">' +
            '  <div class="trend-bar-wrap">' +
            '    <div class="trend-bar" :style="{ height: height(d.value) + \'px\' }" :title="label(d.value)"></div>' +
            '  </div>' +
            '  <div class="trend-label">{{ d.label }}</div>' +
            '</div>' +
            '</div>'
    };
})(window.Vue);
