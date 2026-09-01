(function (Vue) {
    'use strict';
    if (!Vue) return;
    window.BerbagiComponents = window.BerbagiComponents || {};

    window.BerbagiComponents.BannerSlider = {
        props: {
            slides: { type: Array, default: function () { return []; } },
            interval: { type: Number, default: 3000 }
        },
        data: function () {
            return { current: 0, timer: null, startX: null, tilt: null };
        },
        computed: {
            count: function () { return this.slides.length; },
            stageStyle: function () {
                var s = 'transform: translateZ(0)';
                if (this.tilt) {
                    s += ' rotateX(' + this.tilt.rx + 'deg) rotateY(' + this.tilt.ry + 'deg)';
                }
                return s;
            }
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
            onTouchStart: function (e) { this.startX = e.touches[0].clientX; this.stop(); },
            onTouchEnd: function (e) {
                if (this.startX === null) return;
                var dx = e.changedTouches[0].clientX - this.startX;
                if (Math.abs(dx) > 40) { dx < 0 ? this.next() : this.prev(); }
                this.startX = null;
                this.start();
            },
            onMove: function (e) {
                if (!this.count) return;
                if (window.matchMedia && window.matchMedia('(hover: none)').matches) return;
                var rect = this.$el.getBoundingClientRect();
                if (!rect.width || !rect.height) return;
                var x = (e.clientX - rect.left) / rect.width - 0.5;
                var y = (e.clientY - rect.top) / rect.height - 0.5;
                this.tilt = { rx: (-y * 7).toFixed(2), ry: (x * 9).toFixed(2) };
            },
            onLeave: function () { this.tilt = null; },
            onEnter: function () { this.stop(); },
            onMouseOut: function () {
                this.tilt = null;
                if (this.count > 1) this.start();
            }
        },
        mounted: function () {
            if (this.count > 1) { this.start(); }
        },
        beforeUnmount: function () { this.stop(); },
        template: '<div class="hero-cutout-shell" @mousemove="onMove" @mouseenter="onEnter" @mouseleave="onMouseOut">' +
            '<div class="hero-cutout-glow"></div>' +
            '<div class="hero-cutout-stage" :style="stageStyle" @touchstart.passive="onTouchStart" @touchend="onTouchEnd">' +
            '  <div v-if="count" v-for="(s, i) in slides" :key="i" class="hero-cutout-slide" :class="{ active: i === current }">' +
            '    <a v-if="s.url" :href="s.url" target="_blank" rel="noopener" class="hero-cutout-imgwrap">' +
            '      <img :src="s.image" :alt="s.title" class="hero-cutout-img" draggable="false">' +
            '    </a>' +
            '    <div v-else class="hero-cutout-imgwrap">' +
            '      <img :src="s.image" :alt="s.title" class="hero-cutout-img" draggable="false">' +
            '    </div>' +
            '  </div>' +
            '  <div v-if="!count" class="hero-cutout-imgwrap">' +
            '    <div class="grid h-64 w-64 place-items-center rounded-full bg-gradient-to-br from-primary-600 to-primary-500 text-white shadow-glow">' +
            '      <i class="fas fa-hand-holding-heart" style="font-size:56px;"></i>' +
            '    </div>' +
            '  </div>' +
            '</div>' +
            '</div>'
    };
})(window.Vue);
