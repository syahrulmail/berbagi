(function (Vue) {
    'use strict';
    if (!Vue) return;
    window.BerbagiComponents = window.BerbagiComponents || {};

    window.BerbagiComponents.ProgramExplorer = {
        props: {
            programs: { type: Array, default: function () { return []; } },
            tags: { type: Array, default: function () { return []; } }
        },
        data: function () {
            return { q: '', active: 'semua' };
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
            setFilter: function (f) { this.active = f; }
        },
        template: '<div>' +
            '<div class="filter-bar">' +
            '  <div class="search-box">' +
            '    <i class="fas fa-magnifying-glass"></i>' +
            '    <input type="search" v-model="q" placeholder="Cari program..." autocomplete="off">' +
            '  </div>' +
            '  <div class="filter-pills">' +
            '    <button type="button" class="pill" :class="{ active: active === \'semua\' }" @click="setFilter(\'semua\')">Semua</button>' +
            '    <button type="button" class="pill" :class="{ active: active === \'penggalangan\' }" @click="setFilter(\'penggalangan\')"><i class="fas fa-hand-holding-dollar"></i> Penggalangan</button>' +
            '    <button type="button" class="pill" :class="{ active: active === \'penyaluran\' }" @click="setFilter(\'penyaluran\')"><i class="fas fa-box-open"></i> Penyaluran</button>' +
            '    <button v-for="t in tags" :key="t" type="button" class="pill" :class="{ active: active === \'tag:\' + t.toLowerCase() }" @click="setFilter(\'tag:\' + t.toLowerCase())">{{ t }}</button>' +
            '  </div>' +
            '</div>' +
            '<div v-if="filtered.length" class="program-grid">' +
            '  <article v-for="p in filtered" :key="p.slug" class="program-card group flex flex-col overflow-hidden rounded-3xl border border-black/5 bg-white shadow-card transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">' +
            '    <a :href="p.url" class="relative block aspect-[4/3] overflow-hidden">' +
            '      <img v-if="p.image" :src="p.image" :alt="p.name" loading="lazy" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">' +
            '      <div v-else class="grid h-full w-full place-items-center bg-gradient-to-br from-primary-100 to-primary-50 text-primary-400"><i class="fas fa-book-quran" style="font-size:40px;"></i></div>' +
            '      <span class="absolute left-3 top-3 rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide text-white" :class="p.category === \'penyaluran\' ? \'bg-gold-500\' : \'bg-primary-500\'">{{ p.category }}</span>' +
            '    </a>' +
            '    <div class="flex flex-1 flex-col p-5">' +
            '      <div v-if="p.tags.length" class="mb-2 flex flex-wrap gap-1.5">' +
            '        <span v-for="(t, i) in p.tags" :key="i" class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold" :class="i === 0 ? \'bg-gold-100 text-gold-700\' : \'bg-primary-100 text-primary-700\'">{{ t }}</span>' +
            '      </div>' +
            '      <h3 class="mb-1.5 text-lg font-bold text-primary-900"><a :href="p.url" class="transition-colors hover:text-primary-600">{{ p.name }}</a></h3>' +
            '      <p class="mb-4 line-clamp-2 text-sm text-gray-600">{{ p.description }}</p>' +
            '      <div class="mt-auto">' +
            '        <div class="mb-1.5 flex justify-between text-xs text-gray-500">' +
            '          <span>Terkumpul <strong class="text-primary-700">{{ p.collected }}</strong></span>' +
            '          <span>Target {{ p.goal }}</span>' +
            '        </div>' +
            '        <div class="h-2 w-full overflow-hidden rounded-full bg-primary-100">' +
            '          <div class="h-full rounded-full bg-gradient-to-r from-primary-500 to-emerald-400" :style="{ width: p.progress + \'%\' }"></div>' +
            '        </div>' +
            '        <div class="mt-4 flex gap-2">' +
            '          <a :href="p.url" class="btn btn-outline btn-sm flex-1">Detail</a>' +
            '          <a :href="p.wa_url" target="_blank" rel="noopener" class="btn btn-ghost-wa btn-sm" aria-label="Donasi via WhatsApp" data-wa-log="1" :data-wa-source="p.wa_source" :data-wa-program="p.wa_program" :data-wa-agen="p.wa_agen || null"><i class="fab fa-whatsapp"></i></a>' +
            '        </div>' +
            '      </div>' +
            '    </div>' +
            '  </article>' +
            '</div>' +
            '<div v-else class="empty-state">' +
            '  <i class="fas fa-magnifying-glass"></i>' +
            '  <p>Tidak ada program yang cocok.</p>' +
            '</div>' +
            '</div>'
    };
})(window.Vue);
