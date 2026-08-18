(function (Vue) {
    'use strict';
    if (!Vue) return;
    window.BerbagiComponents = window.BerbagiComponents || {};

    window.BerbagiComponents.BannerSlider = {
        props: {
            slides: { type: Array, default: function () { return []; } },
            interval: { type: Number, default: 5000 }
        },
        data: function () {
            return { current: 0, timer: null, startX: null };
        },
        computed: {
            count: function () { return this.slides.length; }
        },
        methods: {
            go: function (i) {
                if (!this.count) return;
                this.current = (i + this.count) % this.count;
            },
            next: function () { this.go(this.current + 1); this.restart(); },
            prev: function () { this.go(this.current - 1); this.restart(); },
            restart: function () {
                this.stop();
                this.start();
            },
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
            }
        },
        mounted: function () {
            if (this.count > 1) { this.start(); }
        },
        beforeUnmount: function () { this.stop(); },
        template: '<div class="banner-shell">' +
            '<div v-if="count" class="relative overflow-hidden rounded-3xl shadow-glow">' +
            '  <div class="flex transition-transform duration-700 ease-out" :style="{ transform: \'translateX(-\' + (current * 100) + \'%)\' }" @touchstart.passive="onTouchStart" @touchend="onTouchEnd">' +
            '    <div v-for="(s, i) in slides" :key="i" class="relative w-full flex-shrink-0">' +
            '      <a v-if="s.url" :href="s.url" target="_blank" rel="noopener" class="block aspect-[16/10] w-full overflow-hidden">' +
            '        <img :src="s.image" :alt="s.title" class="h-full w-full object-cover">' +
            '        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>' +
            '        <div class="absolute bottom-0 left-0 right-0 p-6 text-white"><h3 class="text-xl font-bold md:text-2xl">{{ s.title }}</h3></div>' +
            '      </a>' +
            '      <div v-else class="relative block aspect-[16/10] w-full overflow-hidden">' +
            '        <img :src="s.image" :alt="s.title" class="h-full w-full object-cover">' +
            '        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>' +
            '        <div class="absolute bottom-0 left-0 right-0 p-6 text-white"><h3 class="text-xl font-bold md:text-2xl">{{ s.title }}</h3></div>' +
            '      </div>' +
            '    </div>' +
            '  </div>' +
            '  <button v-if="count > 1" type="button" class="absolute left-3 top-1/2 -translate-y-1/2 grid h-10 w-10 place-items-center rounded-full bg-white/80 text-primary-700 shadow-md transition hover:bg-white" @click="prev" aria-label="Sebelumnya"><i class="fas fa-chevron-left"></i></button>' +
            '  <button v-if="count > 1" type="button" class="absolute right-3 top-1/2 -translate-y-1/2 grid h-10 w-10 place-items-center rounded-full bg-white/80 text-primary-700 shadow-md transition hover:bg-white" @click="next" aria-label="Berikutnya"><i class="fas fa-chevron-right"></i></button>' +
            '  <div v-if="count > 1" class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-2">' +
            '    <button v-for="(s, i) in slides" :key="i" type="button" class="h-2 rounded-full transition-all" :class="i === current ? \'w-6 bg-white\' : \'w-2 bg-white/60\'" @click="go(i)" :aria-label="\'Slide \' + (i + 1)"></button>' +
            '  </div>' +
            '</div>' +
            '<div v-else class="grid aspect-[16/10] w-full place-items-center rounded-3xl bg-gradient-to-br from-primary-600 to-primary-500 text-white shadow-glow">' +
            '  <i class="fas fa-hand-holding-heart" style="font-size:56px;"></i>' +
            '</div>' +
            '</div>'
    };
})(window.Vue);
