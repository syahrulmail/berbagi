<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Kwitansi - {{ $wakifName }}</title>
<style>
    :root {
        --green: #2f7a52;
        --green-dark: #245c3e;
        --line: #333;
        --muted: #666;
    }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: "Segoe UI", Arial, Helvetica, sans-serif;
        background: #e9eef0;
        color: #222;
        font-size: 12px;
    }
    .toolbar { padding: 12px; text-align: right; }
    .toolbar button {
        background: var(--green);
        color: #fff;
        border: none;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    .sheet {
        width: 194mm;
        min-height: 135mm;
        margin: 0 auto 24px;
        background: #fff;
        padding: 6mm;
        box-shadow: 0 4px 18px rgba(0, 0, 0, .15);
        position: relative;
    }
    .sheet-inner { border: 1px solid var(--line); display: flex; }
    .col-left { width: 71%; border-right: 1px dashed #999; }
    .col-right { width: 29%; padding: 10px; position: relative; overflow: hidden; }
    .col-left { padding-bottom: 10px; }

    .header {
        background: var(--green);
        color: #fff;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .logo { font-weight: 800; font-size: 24px; letter-spacing: 1px; line-height: 1; }
    .logo small { display: block; font-size: 8px; font-weight: 400; letter-spacing: 1.5px; margin-top: 2px; }
    .header-addr { font-size: 8.5px; line-height: 1.55; }
    .header-title { flex: 1; text-align: center; font-size: 23px; font-weight: 800; letter-spacing: 1px; }
    .header-sub {
        font-size: 8.5px;
        text-align: center;
        color: var(--green-dark);
        padding: 3px 0;
        border-bottom: 1px solid #ccd;
    }

    .statement { font-size: 11.5px; margin: 12px 12px 10px; }

    .frow { display: flex; align-items: flex-end; gap: 6px; margin: 0 12px 5px; }
    .flabel { width: 122px; font-size: 10.5px; padding-bottom: 2px; }
    .flabel.sm { width: auto; }
    .fval {
        flex: 1;
        border: 1px solid var(--line);
        border-radius: 3px;
        min-height: 19px;
        padding: 3px 6px;
        font-size: 11px;
        word-break: break-word;
    }
    .fval.sm { flex: 0 0 88px; }
    .fval.mask { letter-spacing: 1px; }

    .table-wrap { display: flex; gap: 8px; margin: 12px 12px 0; }
    .items { flex: 1; border-collapse: collapse; }
    .items th, .items td { border: 1px solid var(--line); padding: 4px 5px; font-size: 10.5px; }
    .items th { background: var(--green); color: #fff; text-align: center; font-weight: 600; }
    .items td.num { text-align: right; }
    .items tr.total-row td { font-weight: 700; background: #f2f7f4; }

    .verif { width: 148px; border-collapse: collapse; align-self: flex-start; }
    .verif th, .verif td { border: 1px solid var(--line); padding: 3px 5px; font-size: 9.5px; }
    .verif th { background: var(--green); color: #fff; }
    .verif-note { font-size: 8.5px; font-style: italic; margin-top: 3px; }

    .syarat { margin: 14px 12px 0; font-size: 9px; line-height: 1.55; }
    .syarat ol { margin: 4px 0 0; padding-left: 16px; }

    .stub-logo { text-align: center; margin: 6px 0 4px; }
    .stub-logo .logo { display: inline-block; color: var(--green); font-size: 42px; }
    .tagline { font-size: 9.5px; font-style: italic; line-height: 1.5; margin: 8px 0 12px; }
    .officer { font-size: 9px; color: var(--muted); margin-bottom: 6px; }
    .col-right .label { font-size: 10.5px; margin-top: 8px; }
    .amount-box {
        border: 1px solid var(--line);
        min-height: 34px;
        margin: 4px 0 4px;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        padding: 0 8px;
        font-weight: 700;
        font-size: 12px;
    }
    .line-field { border-bottom: 1px dotted #666; min-height: 16px; font-size: 10.5px; padding: 2px 4px 0; }
    .pay-date { font-size: 9px; color: var(--muted); margin-top: 3px; }
    .watermark {
        position: absolute;
        bottom: 24px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 72px;
        font-weight: 800;
        letter-spacing: 3px;
        color: rgba(47, 122, 82, .12);
        pointer-events: none;
    }
    .org-name {
        position: absolute;
        bottom: 8px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 1px;
        color: var(--muted);
    }

    @media print {
        body { background: #fff; }
        .toolbar { display: none; }
        .sheet {
            width: auto;
            min-height: auto;
            margin: 0;
            padding: 0;
            box-shadow: none;
        }
        @page { size: A4 portrait; margin: 8mm; }
    }
</style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()"><i>&#128424;</i> Cetak / Simpan PDF</button>
    </div>

    <div class="sheet">
        <div class="sheet-inner">
            <div class="col-left">
                <div class="header">
                    <div class="logo">BWA<small>Inovasi Wakaf</small></div>
                    <div class="header-addr">
                        Jl. Tebet Timur Dalam I, No. 1<br>
                        Tebet &ndash; Jakarta Selatan 12820<br>
                        Telp. (021) 8350084 &middot; Fax (021) 83796421<br>
                        admin@wakafalquran.org &middot; www.wakafalquran.org
                    </div>
                    <div class="header-title">FORMULIR WAKAF</div>
                </div>
                <div class="header-sub">www.facebook.com/wakafalquran &nbsp;|&nbsp; www.twitter.com/wakafalquran</div>

                <div class="statement">Saya amanahkan wakaf/sedekah Saya kepada Badan Wakaf Al Qur'an sebagai berikut :</div>

                <div class="frow">
                    <span class="flabel">Nama Wakif / Donatur <b>*</b></span>
                    <span class="fval">{{ $wakifName }}</span>
                    <span class="flabel sm">Gelar</span>
                    <span class="fval sm"></span>
                </div>
                <div class="frow">
                    <span class="flabel">Nama Pendaftar <b>*</b></span>
                    <span class="fval">{{ $pendaftar }}</span>
                </div>
                <div class="frow">
                    <span class="flabel">Alamat Surat</span>
                    <span class="fval"></span>
                    <span class="flabel sm">Kode Pos</span>
                    <span class="fval sm"></span>
                </div>
                <div class="frow">
                    <span class="flabel">Kota / Kabupaten</span>
                    <span class="fval"></span>
                    <span class="flabel sm">Propinsi</span>
                    <span class="fval sm"></span>
                </div>
                <div class="frow">
                    <span class="flabel">No. Handphone</span>
                    <span class="fval mask">{{ $phoneMasked }}</span>
                    <span class="flabel sm">Telepon Rumah</span>
                    <span class="fval sm"></span>
                </div>
                <div class="frow">
                    <span class="flabel">email (wajib diisi)</span>
                    <span class="fval"></span>
                </div>

                <div class="table-wrap">
                    <table class="items">
                        <thead>
                            <tr>
                                <th style="width:24%;">Jenis Donasi</th>
                                <th style="width:18%;">Kode Unik Proyek</th>
                                <th style="width:12%;">Banyak</th>
                                <th style="width:20%;">Nilai (Rp)</th>
                                <th style="width:26%;">Jumlah (Rp)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td>{{ $item['category_label'] }}</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="num">{{ number_format($item['amount'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                            @for($i = count($items); $i < 5; $i++)
                                <tr><td></td><td></td><td></td><td></td><td></td></tr>
                            @endfor
                            <tr class="total-row">
                                <td colspan="4">Total: {{ $terbilang }}</td>
                                <td class="num">{{ number_format($total, 0, ',', '.') }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div>
                        <table class="verif">
                            <thead>
                                <tr><th>Keterangan</th><th>Paraf</th><th>Tanggal</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>Terima</td><td></td><td></td></tr>
                                <tr><td>Konfirmasi</td><td></td><td></td></tr>
                                <tr><td>Input</td><td></td><td></td></tr>
                                <tr><td>Verifikasi</td><td></td><td></td></tr>
                            </tbody>
                        </table>
                        <div class="verif-note">* Diisi oleh Bag. Keuangan</div>
                    </div>
                </div>

                <div class="syarat">
                    <strong>Syarat &amp; Ketentuan :</strong>
                    <ol>
                        <li>Dana wakaf/sedekah tersebut diatas sudah termasuk : biaya produksi, distribusi, pembinaan, pengembangan dan operasional.</li>
                        <li>Konfirmasi dana wakaf/sedekah kepada Anda, kami lakukan dalam waktu maksimal 3 (tiga) hari setelah bukti setoran wakaf/sedekah terverifikasi oleh Bagian keuangan kami.</li>
                        <li>Apabila proyek wakaf/sedekah pilihan anda ternyata telah terpenuhi, maka dengan ini anda menyerahkan kepada kami (BWA) untuk memanfaatkan dana anda dalam pembiayaan proyek wakaf/sedekah BWA lainnya.</li>
                    </ol>
                </div>
            </div>

            <div class="col-right">
                <div class="stub-logo"><span class="logo">BWA<small style="color:var(--green);">Inovasi Wakaf</small></span></div>
                <div class="tagline">&ldquo;Wakaf yang Anda tunaikan tak hanya berbuah pahala yang mengalir abadi, namun akan membangkitkan saudara kita meraih impiannya&rdquo;</div>
                <div class="officer">Diisi oleh petugas</div>
                <div class="label">Sudah terima uang sebesar :</div>
                <div class="amount-box">Rp {{ number_format($total, 0, ',', '.') }}</div>
                <div class="label">terbilang :</div>
                <div class="line-field">{{ $terbilang }}</div>
                <div class="pay-date">Tanggal Pembayaran : {{ $paymentDateFormatted }}</div>
                <div class="label">dari :</div>
                <div class="line-field">{{ $wakifName }}</div>
                <div class="label" style="margin-top:16px;">Tanggal : {{ $donationDateFormatted }}</div>
                <div class="watermark">BWA</div>
                <div class="org-name">BADAN WAKAF AL QUR'AN</div>
            </div>
        </div>
    </div>
</body>
</html>
