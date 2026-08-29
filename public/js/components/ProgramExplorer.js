(function (Vue) {
    'use strict';
    if (!Vue) return;
    window.BerbagiComponents = window.BerbagiComponents || {};

    window.BerbagiComponents.ProgramExplorer = {
        props: {
            programs: { type: Array, default: function () { return []; } },
            tags: { type: Array, default: function () { return []; } },
            sticky: { type: Boolean, default: false }
        },
        data: function () {
            return { q: '', active: 'semua', detail: null, showFilters: false };
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
                            show = (p.tags || []).map(function (x) { return x.name.toLowerCase(); }).indexOf(t) !== -1;
                        }
                    }
                    if (show && q) {
                        var hay = (p.name + ' ' + (p.description || '') + ' ' + (p.tags || []).map(function (x) { return x.name; }).join(' ')).toLowerCase();
                        show = hay.indexOf(q) !== -1;
                    }
                    return show;
                });
            }
        },
        methods: {
            setFilter: function (f) { this.active = f; },
            toggleFilters: function () { this.showFilters = !this.showFilters; },
            openDetail: function (p) { this.detail = p; },
            closeDetail: function () { this.detail = null; },
            onKey: function (e) {
                if (e.key === 'Escape') this.closeDetail();
            },
            popular: function (p) {
                var arr = this.filtered;
                if (!arr.length) return false;
                var best = arr[0];
                for (var i = 1; i < arr.length; i++) {
                    if (arr[i].progress > best.progress) best = arr[i];
                }
                return p.slug === best.slug;
            },
            defaultTag: function (p) {
                var arr = p.tags || [];
                for (var i = 0; i < arr.length; i++) {
                    if (arr[i].is_default) return arr[i];
                }
                return null;
            },
            otherTags: function (p) {
                return (p.tags || []).filter(function (t) { return !t.is_default; });
            },
            defaultTags: function (p) {
                return (p.tags || []).filter(function (t) { return t.is_default; });
            },
            textColor: function (hex) {
                if (!hex) return '#ffffff';
                var h = String(hex).replace('#', '');
                if (h.length === 3) h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
                var n = parseInt(h, 16);
                var r = (n >> 16) & 255, g = (n >> 8) & 255, b = n & 255;
                var lum = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
                return lum > 0.6 ? '#0b2f2d' : '#ffffff';
            }
        },
        watch: {
            detail: function (val) {
                document.body.style.overflow = val ? 'hidden' : '';
            }
        },
        mounted: function () {
            document.addEventListener('keydown', this.onKey);
        },
        beforeUnmount: function () {
            document.removeEventListener('keydown', this.onKey);
            document.body.style.overflow = '';
        },
        template: '<div>' +
            '<div class="filter-bar" :class="{ \'filter-bar-sticky\': sticky }">' +
            '  <div class="filter-bar-row">' +
            '    <div class="search-box">' +
            '      <i class="fas fa-magnifying-glass"></i>' +
            '      <input type="search" v-model="q" placeholder="Cari program..." autocomplete="off">' +
            '    </div>' +
            '    <button type="button" class="filter-toggle" :class="{ active: showFilters }" @click="toggleFilters" :aria-expanded="showFilters ? \'true\' : \'false\'" aria-label="Tampilkan atau sembunyikan filter">' +
            '      <i class="fas fa-sliders"></i> <span>Filter</span> <i class="fas" :class="showFilters ? \'fa-chevron-up\' : \'fa-chevron-down\'"></i>' +
            '    </button>' +
            '  </div>' +
            '  <transition name="filter-fade">' +
            '  <div v-show="showFilters" class="filter-pills">' +
            '    <button type="button" class="pill" :class="{ active: active === \'semua\' }" @click="setFilter(\'semua\')">Semua</button>' +
            '    <button type="button" class="pill" :class="{ active: active === \'penggalangan\' }" @click="setFilter(\'penggalangan\')"><i class="fas fa-hand-holding-dollar"></i> Penggalangan</button>' +
            '    <button type="button" class="pill" :class="{ active: active === \'penyaluran\' }" @click="setFilter(\'penyaluran\')"><i class="fas fa-box-open"></i> Penyaluran</button>' +
            '    <button v-for="t in tags" :key="t" type="button" class="pill" :class="{ active: active === \'tag:\' + t.toLowerCase() }" @click="setFilter(\'tag:\' + t.toLowerCase())">{{ t }}</button>' +
            '  </div>' +
            '  </transition>' +
            '</div>' +
            '<div v-if="filtered.length" class="program-grid">' +
            '  <article v-for="p in filtered" :key="p.slug" class="program-card group flex flex-col overflow-hidden rounded-3xl border border-black/5 bg-white shadow-card transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">' +
            '    <button type="button" class="relative block w-full aspect-[4/3] overflow-hidden cursor-pointer card-shine" @click="openDetail(p)" :aria-label="\'Lihat detail \' + p.name">' +
            '      <img v-if="p.image" :src="p.image" :alt="p.name" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">' +
            '      <div v-else class="grid h-full w-full place-items-center bg-gradient-to-br from-primary-100 to-primary-50 text-primary-400"><i class="fas fa-book-quran" style="font-size:40px;"></i></div>' +
            '      <span v-if="popular(p)" class="popular-badge"><i class="fas fa-fire"></i> Terpopuler</span>' +
            '      <span class="absolute left-3 top-3 flex items-center gap-1.5">' +
            '        <span class="rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide text-white" :class="p.category === \'penyaluran\' ? \'bg-gold-500\' : \'bg-primary-500\'">{{ p.category }}</span>' +
            '        <span v-if="defaultTag(p)" class="rounded-full px-3 py-1 text-xs font-bold text-white" :style="{ background: defaultTag(p).color, color: textColor(defaultTag(p).color) }">{{ defaultTag(p).name }}</span>' +
            '      </span>' +
            '      <span v-if="p.is_complete" class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-emerald-500 px-3 py-1 text-xs font-bold text-white"><i class="fas fa-check-circle"></i> Tercapai</span>' +
            '      <span v-else-if="p.progress >= 90" class="absolute right-3 top-3 inline-flex items-center gap-1 rounded-full bg-gold-500 px-3 py-1 text-xs font-bold text-white"><i class="fas fa-fire"></i> Hampir Tercapai</span>' +
            '    </button>' +
            '    <div class="flex flex-1 flex-col p-5">' +
            '      <div v-if="otherTags(p).length" class="mb-2 flex flex-wrap gap-1.5">' +
            '        <span v-for="(t, i) in otherTags(p)" :key="i" class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold" :style="{ background: t.color, color: textColor(t.color) }">{{ t.name }}</span>' +
            '      </div>' +
            '      <h3 class="mb-1.5 text-lg font-bold text-primary-900"><button type="button" class="cursor-pointer text-left transition-colors hover:text-primary-600" @click="openDetail(p)">{{ p.name }}</button></h3>' +
            '      <p class="mb-4 line-clamp-2 text-sm text-gray-600">{{ p.description }}</p>' +
            '      <div class="mt-auto">' +
            '        <div class="mb-1.5 flex items-center justify-between gap-2 text-xs text-gray-500">' +
            '          <span>Terkumpul <strong class="text-primary-700">{{ p.collected }}</strong></span>' +
            '          <span>Target {{ p.goal }}</span>' +
            '        </div>' +
            '        <div class="h-2 w-full overflow-hidden rounded-full bg-primary-100">' +
            '          <div class="program-progress-anim h-full rounded-full bg-gradient-to-r from-primary-500 to-emerald-400" :style="{ \'--p\': Math.max(4, p.progress) + \'%\', width: Math.max(4, p.progress) + \'%\' }"></div>' +
            '        </div>' +
            '        <div class="mt-1.5 flex items-center justify-between gap-2 text-[11px]">' +
            '          <span v-if="p.is_complete" class="font-semibold text-emerald-600"><i class="fas fa-check-circle"></i> Target tercapai</span>' +
            '          <span v-else-if="p.remaining" class="truncate font-semibold text-gray-500">Masih perlu <strong class="text-primary-700">{{ p.remaining }}</strong></span>' +
            '          <span v-else></span>' +
            '          <span class="shrink-0 font-semibold text-primary-700">{{ p.progress }}%</span>' +
            '        </div>' +
            '        <div class="mt-4 flex gap-2">' +
            '          <a :href="p.wa_url" target="_blank" rel="noopener" class="btn btn-wa btn-sm flex-1" data-wa-log="1" :data-wa-source="p.wa_source" :data-wa-program="p.wa_program" :data-wa-agen="p.wa_agen || null"><i class="fab fa-whatsapp"></i> Berbagi</a>' +
            '          <button type="button" class="btn btn-outline btn-sm" @click="openDetail(p)"><i class="fas fa-circle-info"></i> Detail</button>' +
            '        </div>' +
            '        <a v-if="p.edit_url" :href="p.edit_url" class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-gray-400 transition-colors hover:text-primary-600"><i class="fas fa-pen-to-square"></i> Edit program</a>' +
            '      </div>' +
            '    </div>' +
            '  </article>' +
            '</div>' +
            '<div v-else class="empty-state">' +
            '  <i class="fas fa-magnifying-glass"></i>' +
            '  <p>Tidak ada program yang cocok.</p>' +
            '</div>' +
            '<teleport to="body">' +
            '<div v-if="detail" class="pdetail-overlay" @click.self="closeDetail">' +
            '  <div class="pdetail-modal" role="dialog" aria-modal="true">' +
            '    <div class="pdetail-media">' +
            '      <img v-if="detail.image" :src="detail.image" :alt="detail.name">' +
            '      <div v-else class="pdetail-media-ph"><i class="fas fa-book-quran"></i></div>' +
            '      <div class="pdetail-badges">' +
            '        <span class="pdetail-cat" :class="detail.category === \'penyaluran\' ? \'is-gold\' : \'\'">{{ detail.category }}</span>' +
            '        <span v-for="(t, i) in defaultTags(detail)" :key="i" class="pdetail-tag" :style="{ background: t.color, color: textColor(t.color) }">{{ t.name }}</span>' +
            '      </div>' +
            '      <span v-if="detail.is_complete" class="pdetail-done"><i class="fas fa-check-circle"></i> Tercapai</span>' +
            '      <span v-else-if="detail.progress >= 90" class="pdetail-hot"><i class="fas fa-fire"></i> Hampir Tercapai</span>' +
            '      <button type="button" class="pdetail-close" @click="closeDetail" aria-label="Tutup"><i class="fas fa-xmark"></i></button>' +
            '    </div>' +
            '    <div class="pdetail-body">' +
            '      <h3 class="pdetail-title">{{ detail.name }}</h3>' +
            '      <div v-if="otherTags(detail).length" class="pdetail-tags">' +
            '        <span v-for="(t, i) in otherTags(detail)" :key="i" class="pdetail-tag" :style="{ background: t.color, color: textColor(t.color) }">{{ t.name }}</span>' +
            '      </div>' +
            '      <p class="pdetail-desc">{{ detail.description }}</p>' +
            '      <div class="pdetail-progress">' +
            '        <div class="pdetail-progress-track"><div :style="{ width: Math.max(4, detail.progress) + \'%\' }"></div></div>' +
            '        <div class="pdetail-progress-meta">' +
            '          <span class="pdetail-pct">{{ detail.progress }}%</span>' +
            '          <span v-if="detail.is_complete" class="pdetail-rem"><i class="fas fa-check-circle"></i> Target tercapai</span>' +
            '          <span v-else-if="detail.remaining" class="pdetail-rem">Sisa <strong>{{ detail.remaining }}</strong></span>' +
            '        </div>' +
            '      </div>' +
            '      <div class="pdetail-stats">' +
            '        <div><span>Terkumpul</span><strong>{{ detail.collected }}</strong></div>' +
            '        <div><span>Target</span><strong>{{ detail.goal }}</strong></div>' +
            '        <div v-if="!detail.is_complete && detail.remaining"><span>Masih Perlu</span><strong class="is-gold">{{ detail.remaining }}</strong></div>' +
            '      </div>' +
            '      <div class="pdetail-trust">' +
            '        <span><i class="fas fa-shield-halved"></i> Terdaftar &amp; Berizin</span>' +
            '        <span><i class="fas fa-circle-check"></i> Penyaluran tercatat resmi</span>' +
            '      </div>' +
            '      <a :href="detail.wa_url" target="_blank" rel="noopener" class="btn btn-wa btn-block pdetail-cta" data-wa-log="1" :data-wa-source="detail.wa_source" :data-wa-program="detail.wa_program" :data-wa-agen="detail.wa_agen || null">' +
            '        <i class="fab fa-whatsapp"></i> Berbagi Sekarang' +
            '      </a>' +
            '      <a :href="detail.url" class="pdetail-more">' +
            '        <i class="fas fa-circle-info"></i> Lihat halaman detail program lengkap <i class="fas fa-arrow-right pdetail-more-arrow"></i>' +
            '      </a>' +
            '    </div>' +
            '  </div>' +
            '</div>' +
            '</teleport>' +
            '</div>'
    };
})(window.Vue);
