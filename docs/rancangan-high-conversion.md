# Rancangan Berbagi.or.id — Struktur High-Conversion

> Funnel lengkap: **Homepage → Detail Program → Halaman Agen → CTA WhatsApp & Follow-up → Transparansi**.
> Mekanisme konversi tetap berbasis **WhatsApp** (arsitektur saat ini), tanpa payment gateway.
> Dokumen ini menjadi acuan implementasi bertahap.

---

## 1. Ringkasan Eksekutif

Donasi online berbasis WhatsApp adalah **funnel dua-langkah**: pengunjung diubah menjadi
*"prospek yang sudah siap donasi"* (klik WA + follow-up), lalu agen/tim menyelesaikan transaksi
di chat. Konversi naik ketika halaman berhasil menurunkan **hambatan psikologis** calon donatur:

1. **"Apakah ini aman & legal?"** → transparansi, legalitas, bukti penyaluran.
2. **"Apakah donasi saya berdampak?"** → storytelling dampak nyata & harga dampak spesifik.
3. **"Apakah sekarang atau nanti?"** → urgensi wajar (target, tenggat), bukan tekanan.
4. **"Apakah saya harus repot?"** → tombol WA dengan pesan terisi otomatis, tanpa isi form.

Rancangan ini mengatur ulang setiap halaman publik agar 4 hambatan itu terjawab berurutan
dalam satu gulir (one-scroll narrative), plus sistem pengukuran untuk terus dioptimalkan.

---

## 2. Prinsip High-Conversion Terbukti

### 2.1 Triad Donasi: Emosi → Kepercayaan → Aksi
| Prinsip | Terjemahan di halaman |
|---|---|
| **Emosi** | Foto & cerita penerima manfaat, dampak konkret ("Rp 50.000 = 1 mushaf Al-Qur'an") |
| **Kepercayaan** | Legalitas, laporan penyaluran, testimoni, jumlah donatur, nama terverifikasi |
| **Aksi** | CTA dominan + tunggal per layar, pesan WA terisi otomatis, sticky bar mobile |

### 2.2 Aturan CRO untuk situs donasi
1. **CTA di atas lipatan** (above-the-fold) — pengunjung bisa beraksi < 10 detik pertama.
2. **Satu tujuan utama per halaman** — jangan menawarkan 3 hal sekaligus.
3. **Kurangi friksi** — WA dengan `wa.me` + teks terisi otomatis = 1 klik, 0 field.
4. **Bukti sosial** — angka total, jumlah donatur, donor terbaru (bukan cuma "sudah 4 program").
5. **Urgensi wajar** — progress bar target & sisa waktu, bukan countdown palsu.
6. **Harga dampak** — jual hasil, bukan "silakan donasi". Contoh: "1 lembar wakaf mushaf = Rp 2.500.000".
7. **Mobile-first** — mayoritas trafik Indonesia via HP; seluruh layout diuji 360px.
8. **Transparansi** — link "Lihat laporan penyaluran" di dekat setiap CTA.
9. **Post-aksi** — ucapan terima kasih + ajakan berbagi (viral loop).
10. **Track micro-conversion** — klik WA, submit follow-up, chat balasan → dasar A/B test.

### 2.3 Struktur "Satu Gulir" per halaman
Setiap halaman memakai pola blok berurutan:

```
1. Hero (emosi + CTA) → 2. Bukti sosial → 3. Isi/dampak → 4. Cara berdonasi
→ 5. Urgensi/transparansi → 6. CTA penutup → 7. FAQ + trust footer
```

---

## 3. Arsitektur Funnel & Alur Konversi

```
                     (awareness)             (consideration)             (decision)          (action)          (retention)
  [Google/IG/WA share] → Homepage  →  Detail Program  →  Halaman Agen  →  Klik WA  →  Chat & transfer  →  Terima kasih + laporan
       │                      │                │                │            │            │
       └── social proof       │                └─ konteks agen ─┘            └─ WaFollowup tercatat
                             └── (langsung /program/{slug} dari iklan)                        │
                                                                                            └─ follow-up otomatis (scheduler)
```

### Micro-konversi yang diukur (funnel 5 tahap)
| # | Tahap | Event | Alat ukur |
|---|---|---|---|
| 1 | Kunjungan | Pageview | Web Analytics / log server |
| 2 | Minat program | Buka detail program | event per halaman |
| 3 | Minat agen | Buka `/cs/{slug}` / `/cs/{agen}/program/{slug}` | event per halaman |
| 4 | **Micro-conversion** | Klik `wa.me` (`data-wa-log`) | tabel `wa_followups` |
| 5 | Donasi | Konfirmasi di chat | input manual agen (`donations`) |

**Rasio yang dipantau:** `klik WA / pageview detail program`, `follow-up / klik WA`,
`donasi / follow-up`. Program dengan klik tinggi tapi follow-up rendah → masalah CTA/trust;
follow-up tinggi tapi donasi rendah → masalah kecepatan balas agen.

---

## 4. Rancangan per Halaman

### 4.1 Homepage `/`

**Tujuan:** membuktikan kredibilitas + memindahkan pengunjung ke detail program atau chat WA.
**Satu CTA utama:** tombol "Donasi via WhatsApp" yang menggulir ke daftar program.

Urutan blok (top → bottom):

| # | Blok | Isi | Alasan CRO |
|---|---|---|---|
| 1 | **Topbar tipis** | Info legal singkat: "Badan Wakaf Al Qur'an · Terdaftar Kemenkumham" + telepon | Kepercayaan instan sebelum apapun |
| 2 | **Hero emosional** | Foto penerima manfaat/wakaf; H1 dampak ("Wakaf untuk Ummat, dari Anda untuk Kebaikan"); sub copy spesifik; 2 CTA: `btn-gold` "Donasi Sekarang" (→ `#program`), `btn-light` WA "Tanya via WhatsApp"; **sticky top progress**: "Total terkumpul Rp X dari target Rp 1,5 M" | Emosi + urgensi + aksi di atas lipatan |
| 3 | **Statistik hidup (CountUp)** | 3 angka: Program aktif, Total terkumpul (Rp), Jumlah agen/donatur | Bukti sosial kuantitatif |
| 4 | **Trust strip** | Ikon+label: "Legalitas resmi", "Penyaluran transparan", "Penerima manfaat tersebar 34 provinsi", "Laporan berkala" | Jawab hambatan #1 |
| 5 | **Program Explorer** (ada) | Filter kategori/tag + pencarian; kartu program baru (lihat 4.1a) | Pilihan termudah menuju minat |
| 6 | **"Program Prioritas" carousel** | 1–2 program paling aktif dengan label "Sangat Membutuhkan" + progress tinggi | Urgensi wajar + social proof |
| 7 | **Cara berdonasi (3 langkah)** | ① Pilih program ② Chat WA (teks otomatis) ③ Transfer & konfirmasi | Menghilangkan keraguan proses |
| 8 | **Transparansi** | Preview laporan penyaluran + foto kegiatan + tombol "Lihat laporan" | Kepercayaan terdalam |
| 9 | **Testimoni donor & agen** | 2–3 kutipan + nama & kota (dengan izin) | Bukti sosial kualitatif |
| 10 | **FAQ** (pertahankan) | Jawab: legalitas, apakah aman, cara transfer, refund/penyaluran | Objeksi akhir |
| 11 | **CTA penutup** | Emosi singkat + tombol WA besar | Pengunjung yang "hampir yakin" |
| 12 | **Footer kaya** | Alamat, legalitas, sosmed, map, donasi info | Kepercayaan + SEO |

#### 4.1a Kartu Program (ProgramCard) — redaksi CRO
- **Progress bar selalu terlihat** (min. 4% agar tidak terlihat kosong; cap 100% = "Tercapai").
- Harga dampak singkat di badge kategori, mis. "Wakaf Mushaf" / "1 mushaf".
- Tag urgen: `bg-gold` "Hampir Tercapai" (>90%), `bg-primary` normal.
- Teks CTA kartu: **"Donasi Program Ini"** (a× halaman detail), bukan "Selengkapnya".
- Jika progress rendah, tampilkan target tersisa: "Masih perlu Rp 12 juta".

### 4.2 Detail Program `/program/{slug}` (generik, dari homepage)

**Tujuan:** membangun kepercayaan & emosi spesifik program, lalu konversi ke WA.
**Satu CTA utama:** tombol "Donasi via WhatsApp" (gulir ke panel donasi).

| # | Blok | Alasan CRO |
|---|---|---|
| 1 | Breadcrumb + kategori/tag | Orientasi + SEO |
| 2 | **Hero program** (2 kolom): kiri = foto/area media besar; kanan = nama, deskripsi singkat, **progress bar besar** (Terkumpul / Target / sisa / %), **CTA donasi + tombol WA**, "Bagikan" | Emosi + urgensi + aksi segera |
| 3 | **Panel "Donasi cepat"** (sticky di kolom kanan, `lg:sticky top`): ringkasan progress + tombol WA `btn-wa` + nomor alternatif + tombol "Bagikan" | Aksi tetap terlihat saat scroll (mirip pola cuan.ninja) |
| 4 | **Cerita / deskripsi program** (rich): tujuan, penerima manfaat, rincian penggunaan dana, foto kegiatan | Emosi & transparansi |
| 5 | **Rincian anggaran** ("Dana digunakan untuk…"): daftar item + nominal | Transparansi (kunci konversi donasi) |
| 6 | **Laporan penyaluran** program terkait (gambar/keterangan) | Bukti dampak |
| 7 | Program lain yang serupa (tags sama) | Dorong lanjut eksplorasi |
| 8 | **FAQ program** (details) | Objeksi |
| 9 | CTA penutup | Aksi terakhir |

Catatan: halaman `/cs/{agen}/program/{program}` memakai layout sama, hanya **konteks agen**:
- Nomor WA = nomor agen (sudah ada di `agentProgram()`).
- Banner kecil "Anda dibantu oleh Agen [Nama]" + tombol "Program lain dari [Nama]".
- Template pesan menyertakan `{agen}`.

### 4.3 Halaman Agen `/cs/{slug}`

**Tujuan:** mengubah kunjungan ber-agen menjadi chat pribadi ke agen (atribusi) + eksplorasi program.
**Satu CTA utama:** "Chat Agen via WhatsApp".

| # | Blok | Alasan CRO |
|---|---|---|
| 1 | Hero agen: foto/avatar, nama, cabang, badge "Agen Resmi BWA", **tombol WA besar** (`btn-wa`, `data-wa-source="agent"`) | Trust personel (manusia = lebih dipercaya) |
| 2 | Strip micro-profil: "Membantu donatur di [kota] · [n] donasi · bergabung [tahun]" | Bukti sosial agen |
| 3 | Program yang dibawa agen (ProgramExplorer, `data-wa-agen`) | Eksplorasi + atribusi |
| 4 | Cara berdonasi via agen (3 langkah) | Kurangi friksi proses |
| 5 | Testimoni donor atas nama agen | Trust personel |
| 6 | CTA penutup chat agen | Aksi |

### 4.4 Halaman "Cara Berdonasi" `/cara-donasi` (baru)

**Tujuan:** menjawab seluruh proses secara visual untuk pengunjung ragu (dari homepage/agen).
- 3 langkah besar + diagram.
- Daftar metode: WA + nomor, transfer bank (panduan), rekening resmi atas nama BWA.
- Callout legalitas + link laporan.
- CTA: "Mulai Donasi Sekarang" → home `#program` atau WA langsung.

### 4.5 Halaman Transparansi `/transparansi` (baru)

**Tujuan:** pusat kepercayaan; link dari setiap trust strip & footer.
- Legalitas (SK, akta, NPWP) dalam bentuk kartu + file/dokumen.
- Ringkasan: total terkumpul, total tersalurkan, biaya operasional, sisa dana.
- Grafik (komponen `BarChart`/`DonutChart` yang sudah ada) distribusi per program & per tahun.
- Arsip laporan bulanan/berkala (pdf/preview).
- CTA: "Mau bertanya? Chat kami".

### 4.6 Halaman Terima Kasih / follow-up (post-WA)

Karena transaksi di chat, halaman ini adalah **halaman konfirmasi intent** (dari tombol WA biasa
bisa diarahkan `target="_blank"` agar tetap di situs):
- Ringkasan program + nomor yang akan dihubungi.
- Apa yang terjadi selanjutnya: "Tim/agen kami akan membalas dalam 1×24 jam".
- Nomor telepon alternatif + jam operasional.
- Ajakan berbagi (WA share, Facebook) → **viral loop**.
- (Opsional) tombol "Kirim pengingat ke WA saya" → menulis ke `wa_followups`.

---

## 5. Komponen Reusable (dibangun sekali, dipakai semua halaman)

| Komponen | Fungsi CRO | Status |
|---|---|---|
| `ProgramCard` | Kartu program baru (4.1a) | Rewrite dari card existing di `ProgramExplorer` |
| `DonasiBar` | Sticky bar bawah mobile: program + tombol WA + nominal tersisa | **Baru** |
| `QuickDonasiPanel` | Panel donasi sticky kanan di detail program | **Baru** |
| `SocialProofStrip` | Statistik + trust icon | Refactor dari home |
| `ProgressBar` | Progress besar/indikator | Ada, perbarui versi tampilan |
| `WACtaButton` | Tombol `wa.me` dengan `data-wa-log`, source, agen, program; teks otomatis | Ada (`btn-wa`), standardisasi |
| `TrustBadge` | Ikon legalitas/transparansi | Ada sebagian, buat komponen |
| `Testimoni` | Kutipan donor/agen | **Baru** (data via `settings` atau tabel baru) |
| `ShareWidget` | Bagikan ke WA/FB/X + copy link | **Baru** |
| `CountUp` / `BarChart` / `DonutChart` | Angka animasi & grafik | Ada (Vue `data-vue-app`) |

Semua interaksi memakai pola `data-vue-app` + Vue 3 (CDN `vue.esm-browser.prod.js`) yang
sudah berjalan, sehingga tidak perlu toolchain build tambahan.

---

## 6. Optimalisasi CTA WhatsApp & Follow-up (mesin konversi)

1. **Satu format tombol WA** (`WACtaButton`) dengan atribut pelacakan lengkap:
   `data-wa-log`, `data-wa-source` (`home|program|agent|cara-donasi`), `data-wa-agen`, `data-wa-program`.
2. **Teks pesan terisi otomatis** per konteks:
   - Home/umum: `waTemplate` → ganti `{program}`.
   - Agen: `wa_agent_template` → ganti `{agen}` + `{program}`.
3. **Nomor dinamis**: halaman ber-agen pakai nomor agen; selain itu nomor publik
   (sudah diterapkan di `PublicController`).
4. **Follow-up otomatis** (sudah ada `wa_followups` + scheduler): pastikan cron produksi aktif
   (`artisan schedule:run`) dan `WHATSAPP_API_URL/TOKEN` terisi — follow-up lambat = konversi turun.
5. **Kapan pun tombol WA diklik**, browser dibuka tab baru (`target="_blank"`) agar situs tetap
   terbuka → pengunjung bisa kembali dan mengeksplor program lain.
6. **Nomor tampilan yang konsisten** — satu nomor resmi di seluruh situs (via `settings`),
   hindari menampilkan nomor berubah-ubah yang merusak kepercayaan.

---

## 7. Trust, Legalitas & Transparansi (kunci konversi donasi)

Posisikan trust **sebelum** CTA, bukan hanya di footer:

- Topbar + footer: nama badan, jenis badan (yayasan), status terdaftar, kota, kontak.
- Trust strip memuat tautan ke `/transparansi`.
- Detail program memuat **rincian penggunaan dana** dan **laporan penyaluran**.
- Jumlah yang ditampilkan (total terkumpul, target) diambil real-time dari `donations`
  (bukan angka statis) — hindari klaim yang tidak sesuai dengan `Setting`.

> Prinsip: **jangan pernah menampilkan angka yang tidak dapat dibuktikan** — ini perusak
> kepercayaan nomor satu di sektor donasi.

---

## 8. Storytelling & Bukti Sosial

- Gunakan **foto asli** penerima manfaat/kegiatan (izinkan kredit). Foto real > ilustrasi.
- Pola cerita: **Situasi → Masalah → Solusi program → Dampak yang sudah terjadi**.
- Setiap program punya **harga dampak**: "Rp X = satu unit manfaat". Tulis di kartu & detail.
- Testimoni dengan **nama + kota** (sesuai izin); tanpa izin gunakan "Donatur dari [kota]".
- Tampilkan **donor terbaru** (anonim: "Sdr. A***, Jakarta · Rp 500.000") sebagai bukti sosial
  berjalan — data dari `donations` (tampilkan nama samaran, jangan penuh tanpa izin).

---

## 9. Pengukuran & Metrik Funnel

**Instrumentasi (tanpa tool baru):**
- Log server / Web Analytics untuk pageview per halaman.
- `wa_followups` (sudah ada) sebagai sumber kebenaran micro-conversion.
- `donations` untuk konversi akhir.
- Admin dashboard menampilkan rasio funnel (bisa pakai komponen `BarChart`/`DonutChart`).

**KPI yang di-track:**
| Metrik | Target awal (hipotesis) |
|---|---|
| Bounce rate home | < 50% |
| Klik WA per 100 pengunjung | ≥ 4 (benchmark sektor donasi 3–6%) |
| Follow-up per klik WA | ≥ 60% |
| Donasi per follow-up | ≥ 25% |
| Trafik mobile share | dicatat (sebagian besar) |

**Ritual optimasi:** setiap 2 minggu, review 5 tahap funnel; perbaiki blok dengan drop terbesar.

---

## 10. Prioritas Implementasi (Fase)

| Fase | Isi | Estimasi |
|---|---|---|
| **Fase 1 — Fondasi CRO** | Topbar trust, hero + stat CountUp live, trust strip, kartu program baru, CTA WA terstandardisasi (`WACtaButton` + `data-wa-*`), sticky DonasiBar mobile, link `/transparansi` dari footer | 1 siklus |
| **Fase 2 — Detail program** | Hero 2 kolom + QuickDonasiPanel sticky, rincian anggaran, laporan penyaluran, program serupa, FAQ program | 1 siklus |
| **Fase 3 — Trust & sosial** | Halaman `/cara-donasi` + `/transparansi` (legalitas + laporan + grafik), testimoni, donor terbaru | 1 siklus |
| **Fase 4 — Optimasi konten** | Copywriting hero & CTA, foto asli, harga dampak per program, metrik funnel di dashboard admin | berkelanjutan |

Setiap fase berakhir dengan: **uji lokal → screenshot mobile & desktop → A/B sederhana
(mana yang naik) → deploy**.

---

## 11. Checklist QA Konversi (sebelum deploy)

- [ ] CTA utama terlihat tanpa scroll di mobile 360px & desktop.
- [ ] Tombol WA membuka tab baru dengan teks otomatis benar (program/agen).
- [ ] Nomor WA benar di setiap konteks (agen vs publik).
- [ ] Semua angka (terkumpul, target, total) real-time dari DB.
- [ ] Progress bar >100% menampilkan "Tercapai" (bukan 100% terus).
- [ ] `data-wa-log` menulis `wa_followups` untuk setiap klik.
- [ ] Link `/transparansi` & `/cara-donasi` ada di footer & trust strip.
- [ ] Legalitas & alamat lengkap di footer.
- [ ] Tidak ada klaim angka tanpa bukti.
- [ ] Halaman tidak di-block oleh adblock/tracker; JS Vue berfungsi tanpa konsol error.

---

## Lampiran: Mapping ke Struktur Kode Saat Ini

| Kebutuhan | File/sumber sekarang |
|---|---|
| Halaman publik | `resources/views/public/*`, `app/Http/Controllers/PublicController.php` |
| Route | `routes/web.php` (tambah `/cara-donasi`, `/transparansi`) |
| Data program/tags | `App\Models\Program`, `CampaignTag`, `programs`, `campaign_tags` |
| Angka real-time | `donations.sum(amount)`, `Program::withSum('donations')` |
| Template WA | `settings` (`wa_public_template`, `wa_agent_template`, `wa_public_number`) |
| Tracking follow-up | `WaFollowup` (`wa_followups`) + `data-wa-*` di view |
| Komponen Vue | `public/js/components/*.js` (CountUp, ProgramExplorer, BarChart, DonutChart) |
| Cron WA | `app/Console/Kernel.php` → `whatsapp:send` tiap 5 menit |
| Gaya UI | Tailwind (primary `#08A899`, gold `#D4911E`), `resources/views/layouts/public.blade.php` |
