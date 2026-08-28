(function (Vue) {
    'use strict';
    if (!Vue) return;
    window.BerbagiComponents = window.BerbagiComponents || {};

    window.BerbagiComponents.ProgramSearchModal = {
        props: {
            programs: { type: Array, default: function () { return []; } },
            tags: { type: Array, default: function () { return []; } }
        },
        data: function () {
            return { open: false, q: '', active: 'semua', showFab: false };
        },
        computed: {
            filtered: function () {
                var q = this.q.toLowerCase().trim();
                var active = this.active;
                return this.programs.filter(function (p) {
                    var show = true;
                    if (active !== 'semua') {
                        if (active === 'penggalangan' || active === 'penyaluran') {
                            show = p.category === active;
                        } else if (active.indexOf('tag:') === 0) {
                            var t = active.slice(4);
                            show = (p.tags || []).map(function (x) { return x.toLowerCase(); }).indexOf(t) !== -1;
                        }
                    }
                    if (show && q) {
                        var hay = (p.name + ' ' + (p.description || '') + ' ' + (p.tags || []).join(' ')).toLowerCase();
                        show = hay.indexOf(q) !== -1;
                    }
                    return show;
                });
            }
        },
        methods: {
            setFilter: function (f) { this.active = f; },
            openModal: function () { this.open = true; },
            close: function () { this.open = false; },
            onScroll: function () {
                this.showFab = window.scrollY > 520;
            },
            onKey: function (e) {
                if (e.key === 'Escape') this.close();
            }
        },
        watch: {
            open: function (val) {
                if (val) {
                    document.body.style.overflow = 'hidden';
                    var self = this;
                    this.$nextTick(function () {
                        if (self.$refs.searchInput) self.$refs.searchInput.focus();
                    });
                } else {
                    document.body.style.overflow = '';
                }
            }
        },
        mounted: function () {
            window.addEventListener('scroll', this.onScroll, { passive: true });
            document.addEventListener('keydown', this.onKey);
            this.onScroll();
        },
        beforeUnmount: function () {
            window.removeEventListener('scroll', this.onScroll);
            document.removeEventListener('keydown', this.onKey);
            document.body.style.overflow = '';
        },
        template: '<div>' +
            '<div v-if="open" class="psm-overlay" @click.self="close">' +
            '  <div class="psm-modal" role="dialog" aria-modal="true">' +
            '    <div class="psm-head">' +
            '      <div>' +
            '        <h3 class="psm-title"><i class="fas fa-magnifying-glass"></i> Cari Program</h3>' +
            '        <p class="psm-sub">Temukan program wakaf berdasarkan kata kunci atau tag.</p>' +
            '      </div>' +
            '      <button type="button" class="psm-close" @click="close" aria-label="Tutup"><i class="fas fa-xmark"></i></button>' +
            '    </div>' +
            '    <div class="psm-filter-wrap">' +
            '      <div class="search-box">' +
            '        <i class="fas fa-magnifying-glass"></i>' +
            '        <input type="search" v-model="q" placeholder="Cari program..." autocomplete="off" ref="searchInput">' +
            '      </div>' +
            '      <div class="filter-pills">' +
            '        <button type="button" class="pill" :class="{ active: active === \'semua\' }" @click="setFilter(\'semua\')">Semua</button>' +
            '        <button type="button" class="pill" :class="{ active: active === \'penggalangan\' }" @click="setFilter(\'penggalangan\')"><i class="fas fa-hand-holding-dollar"></i> Penggalangan</button>' +
            '        <button type="button" class="pill" :class="{ active: active === \'penyaluran\' }" @click="setFilter(\'penyaluran\')"><i class="fas fa-box-open"></i> Penyaluran</button>' +
            '        <button v-for="t in tags" :key="t" type="button" class="pill" :class="{ active: active === \'tag:\' + t.toLowerCase() }" @click="setFilter(\'tag:\' + t.toLowerCase())">{{ t }}</button>' +
            '      </div>' +
            '    </div>' +
            '    <div class="psm-results">' +
            '      <div v-if="filtered.length">' +
            '        <div v-for="p in filtered" :key="p.slug" class="psm-item">' +
            '          <div class="psm-item-top">' +
            '            <a :href="p.url" class="psm-item-thumb">' +
            '              <img v-if="p.image" :src="p.image" :alt="p.name" loading="lazy">' +
            '              <div v-else class="psm-item-ph"><i class="fas fa-book-quran"></i></div>' +
            '            </a>' +
            '            <div class="psm-item-main">' +
            '              <a :href="p.url" class="psm-item-title">{{ p.name }}</a>' +
            '              <div class="psm-item-meta">' +
            '                <span class="psm-cat" :class="p.category === \'penyaluran\' ? \'is-gold\' : \'\'">{{ p.category }}</span>' +
            '                <span v-for="(t, i) in p.tags" :key="i" class="psm-tag">{{ t }}</span>' +
            '              </div>' +
            '              <div class="psm-progress"><div :style="{ width: Math.max(4, p.progress) + \'%\' }"></div></div>' +
            '              <div class="psm-item-stats">' +
            '                <span>Terkumpul <strong>{{ p.collected }}</strong></span>' +
            '                <span>Target {{ p.goal }}</span>' +
            '                <span class="psm-pct">{{ p.progress }}%</span>' +
            '              </div>' +
            '            </div>' +
            '          </div>' +
            '          <div class="psm-item-actions">' +
            '            <a :href="p.wa_url" target="_blank" rel="noopener" class="btn btn-wa btn-sm flex-1" data-wa-log="1" :data-wa-source="p.wa_source" :data-wa-program="p.wa_program" :data-wa-agen="p.wa_agen || null"><i class="fab fa-whatsapp"></i> Donasi Sekarang</a>' +
            '            <a :href="p.url" class="btn btn-outline btn-sm"><i class="fas fa-circle-info"></i> Detail</a>' +
            '          </div>' +
            '        </div>' +
            '      </div>' +
            '      <div v-else class="empty-state">' +
            '        <i class="fas fa-magnifying-glass"></i>' +
            '        <p>Tidak ada program yang cocok.</p>' +
            '      </div>' +
            '    </div>' +
            '  </div>' +
            '</div>' +
            '<button v-if="showFab && !open" type="button" class="psm-fab" @click="openModal">' +
            '  <i class="fas fa-magnifying-glass"></i><span>Cari Program</span>' +
            '</button>' +
            '</div>'
    };
})(window.Vue);
