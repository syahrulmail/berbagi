<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

/**
 * Penulis file XLSX minimalis tanpa dependensi eksternal.
 *
 * Cukup untuk ekspor tabel sederhana: baris teks (inline string) dan
 * baris angka. Tidak memakai shared strings agar struktur berkas tetap
 * ringkas dan aman untuk deploy tanpa composer/vendor tambahan.
 */
class XlsxWriter
{
    public const STYLE_DEFAULT = 0;
    public const STYLE_HEADER = 1;
    public const STYLE_WRAP = 2;
    public const STYLE_NUMBER = 3;

    /** @var string */
    protected $sheetName;

    /** @var array<int, array<int, array{value: mixed, style: int}>> */
    protected $rows = [];

    /** @var array<int, float> */
    protected $columnWidths = [];

    /**
     * Gambar yang disematkan.
     *
     * @var array<int, array{path: string, col: int, row: int, cx: int, cy: int, colOff: int, rowOff: int}>
     */
    protected $images = [];

    /** @var array<int, float> Tinggi baris dalam poin, key = nomor baris (1-based). */
    protected $rowHeights = [];

    /** @var array<int, float>|null Urutan: left, right, top, bottom, header, footer (inci). */
    protected $pageMargins = null;

    /** @var array{paperSize: int, orientation: string}|null */
    protected $pageSetup = null;

    public function __construct(string $sheetName = 'Sheet1')
    {
        $clean = preg_replace('#[\\\\/\?\*\[\]:]#', ' ', $sheetName);
        $clean = trim((string) $clean);

        $this->sheetName = $clean !== '' ? mb_substr($clean, 0, 31) : 'Sheet1';
    }

    /**
     * Atur lebar kolom, key = nomor kolom (1-based), value = lebar.
     *
     * @param  array<int, float>  $widths
     * @return $this
     */
    public function setColumnWidths(array $widths): self
    {
        $this->columnWidths = $widths;

        return $this;
    }

    /**
     * Atur tinggi satu baris (dalam poin; 1 px = 0,75 poin).
     *
     * @return $this
     */
    public function setRowHeight(int $rowNumber, float $heightPoints): self
    {
        if ($rowNumber >= 1 && $heightPoints > 0) {
            $this->rowHeights[$rowNumber] = $heightPoints;
        }

        return $this;
    }

    /**
     * Sematkan gambar pada sel (kolom/baris 0-based) dengan ukuran piksel.
     *
     * @return $this
     */
    public function addImage(string $path, int $col, int $row, int $widthPx, int $heightPx, int $colOffsetPx = 0, int $rowOffsetPx = 0): self
    {
        $this->images[] = [
            'path' => $path,
            'col' => max(0, $col),
            'row' => max(0, $row),
            'cx' => (int) round(max(1, $widthPx) * 9525),
            'cy' => (int) round(max(1, $heightPx) * 9525),
            'colOff' => (int) round(max(0, $colOffsetPx) * 9525),
            'rowOff' => (int) round(max(0, $rowOffsetPx) * 9525),
        ];

        return $this;
    }

    /**
     * Jumlah baris yang sudah ditambahkan.
     */
    public function rowCount(): int
    {
        return count($this->rows);
    }

    /**
     * Atur margin halaman (dalam inci).
     *
     * @return $this
     */
    public function setPageMargins(float $left, float $right, float $top, float $bottom, float $header = 0.0, float $footer = 0.0): self
    {
        $this->pageMargins = [$left, $right, $top, $bottom, $header, $footer];

        return $this;
    }

    /**
     * Atur ukuran kertas & orientasi. paperSize 9 = A4.
     *
     * @return $this
     */
    public function setPageSetup(int $paperSize = 9, string $orientation = 'portrait'): self
    {
        $this->pageSetup = [
            'paperSize' => $paperSize,
            'orientation' => $orientation === 'landscape' ? 'landscape' : 'portrait',
        ];

        return $this;
    }

    /**
     * Tambah satu baris.
     *
     * Setiap sel boleh berupa nilai langsung, atau array
     * ['value' => mixed, 'style' => int] untuk mengatur gaya per sel.
     *
     * @param  array<int, mixed>  $cells
     * @return $this
     */
    public function addRow(array $cells, int $defaultStyle = self::STYLE_DEFAULT): self
    {
        $row = [];

        foreach ($cells as $cell) {
            if (is_array($cell)) {
                $row[] = [
                    'value' => $cell['value'] ?? '',
                    'style' => (int) ($cell['style'] ?? $defaultStyle),
                ];

                continue;
            }

            $row[] = [
                'value' => $cell,
                'style' => $defaultStyle,
            ];
        }

        $this->rows[] = $row;

        return $this;
    }

    /**
     * Tulis berkas XLSX ke path tujuan.
     */
    public function save(string $path): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP zip diperlukan untuk membuat berkas XLSX.');
        }

        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat berkas XLSX.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheetXml());

        $images = $this->resolvedImages();

        if (!empty($images)) {
            $zip->addFromString('xl/drawings/drawing1.xml', $this->drawingXml($images));
            $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $this->drawingRelsXml($images));
            $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $this->worksheetRelsXml());

            foreach ($images as $index => $image) {
                $zip->addFromString(
                    'xl/media/image' . ($index + 1) . '.' . $image['ext'],
                    $image['bytes']
                );
            }
        }

        $zip->close();
    }

    /**
     * Saring gambar yang benar-benar dapat dibaca beserta tipe & ukurannya.
     *
     * @return array<int, array{path: string, col: int, row: int, cx: int, cy: int, colOff: int, rowOff: int, ext: string, bytes: string}>
     */
    protected function resolvedImages(): array
    {
        $resolved = [];

        foreach ($this->images as $image) {
            $bytes = @file_get_contents($image['path']);

            if ($bytes === false) {
                continue;
            }

            $resolved[] = $image + [
                'ext' => $this->imageExtension($image['path']),
                'bytes' => $bytes,
            ];
        }

        return $resolved;
    }

    protected function contentTypesXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>';

        if (!empty($this->images)) {
            $xml .= '<Default Extension="png" ContentType="image/png"/>'
                . '<Default Extension="jpeg" ContentType="image/jpeg"/>'
                . '<Default Extension="jpg" ContentType="image/jpeg"/>'
                . '<Default Extension="gif" ContentType="image/gif"/>'
                . '<Default Extension="webp" ContentType="image/webp"/>';
        }

        $xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';

        if (!empty($this->images)) {
            $xml .= '<Override PartName="/xl/drawings/drawing1.xml" ContentType="application/vnd.openxmlformats-officedocument.drawing+xml"/>';
        }

        $xml .= '</Types>';

        return $xml;
    }

    protected function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    protected function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->escape($this->sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    protected function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    protected function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF08A899"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1">'
            . '<alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1">'
            . '<alignment vertical="top" wrapText="1"/></xf>'
            . '<xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1">'
            . '<alignment vertical="top"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    protected function sheetXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';

        if (!empty($this->columnWidths)) {
            $xml .= '<cols>';

            foreach ($this->columnWidths as $index => $width) {
                $xml .= '<col min="' . (int) $index . '" max="' . (int) $index . '" width="'
                    . $this->escape((string) $width) . '" customWidth="1"/>';
            }

            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';

        foreach ($this->rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;

            $attributes = ' r="' . $rowNumber . '"';

            if (isset($this->rowHeights[$rowNumber])) {
                $attributes .= ' ht="' . $this->escape($this->numberToString($this->rowHeights[$rowNumber])) . '" customHeight="1"';
            }

            $xml .= '<row' . $attributes . '>';

            foreach ($row as $columnIndex => $cell) {
                $reference = $this->columnLetter($columnIndex + 1) . $rowNumber;
                $xml .= $this->cellXml($reference, $cell['value'], (int) $cell['style']);
            }

            $xml .= '</row>';
        }

        $xml .= '</sheetData>';

        if ($this->pageMargins !== null) {
            [$left, $right, $top, $bottom, $header, $footer] = $this->pageMargins;

            $xml .= '<pageMargins left="' . $this->escape($this->numberToString($left))
                . '" right="' . $this->escape($this->numberToString($right))
                . '" top="' . $this->escape($this->numberToString($top))
                . '" bottom="' . $this->escape($this->numberToString($bottom))
                . '" header="' . $this->escape($this->numberToString($header))
                . '" footer="' . $this->escape($this->numberToString($footer)) . '"/>';
        }

        if ($this->pageSetup !== null) {
            $xml .= '<pageSetup paperSize="' . (int) $this->pageSetup['paperSize']
                . '" orientation="' . $this->escape($this->pageSetup['orientation']) . '"/>';
        }

        if (!empty($this->images)) {
            $xml .= '<drawing r:id="rId1"/>';
        }

        $xml .= '</worksheet>';

        return $xml;
    }

    /**
     * Buat bagian xl/drawings/drawing1.xml.
     *
     * @param  array<int, array<string, mixed>>  $images
     */
    protected function drawingXml(array $images): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" '
            . 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';

        foreach ($images as $index => $image) {
            $id = $index + 1;

            $xml .= '<xdr:oneCellAnchor>'
                . '<xdr:from>'
                . '<xdr:col>' . $image['col'] . '</xdr:col>'
                . '<xdr:colOff>' . $image['colOff'] . '</xdr:colOff>'
                . '<xdr:row>' . $image['row'] . '</xdr:row>'
                . '<xdr:rowOff>' . $image['rowOff'] . '</xdr:rowOff>'
                . '</xdr:from>'
                . '<xdr:ext cx="' . $image['cx'] . '" cy="' . $image['cy'] . '"/>'
                . '<xdr:pic>'
                . '<xdr:nvPicPr>'
                . '<xdr:cNvPr id="' . $id . '" name="Bukti Pembayaran ' . $id . '"/>'
                . '<xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr>'
                . '</xdr:nvPicPr>'
                . '<xdr:blipFill>'
                . '<a:blip r:embed="rId' . $id . '"/>'
                . '<a:stretch><a:fillRect/></a:stretch>'
                . '</xdr:blipFill>'
                . '<xdr:spPr>'
                . '<a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $image['cx'] . '" cy="' . $image['cy'] . '"/></a:xfrm>'
                . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>'
                . '</xdr:spPr>'
                . '</xdr:pic>'
                . '<xdr:clientData/>'
                . '</xdr:oneCellAnchor>';
        }

        $xml .= '</xdr:wsDr>';

        return $xml;
    }

    /**
     * Buat bagian xl/drawings/_rels/drawing1.xml.rels.
     *
     * @param  array<int, array<string, mixed>>  $images
     */
    protected function drawingRelsXml(array $images): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

        foreach ($images as $index => $image) {
            $id = $index + 1;

            $xml .= '<Relationship Id="rId' . $id . '" '
                . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" '
                . 'Target="../media/image' . $id . '.' . $image['ext'] . '"/>';
        }

        $xml .= '</Relationships>';

        return $xml;
    }

    protected function worksheetRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/drawing" Target="../drawings/drawing1.xml"/>'
            . '</Relationships>';
    }

    protected function imageExtension(string $path): string
    {
        $info = @getimagesize($path);

        switch ($info[2] ?? 0) {
            case IMAGETYPE_PNG:
                return 'png';
            case IMAGETYPE_GIF:
                return 'gif';
            case IMAGETYPE_WEBP:
                return 'webp';
            case IMAGETYPE_JPEG:
            default:
                return 'jpeg';
        }
    }

    protected function cellXml(string $reference, $value, int $style): string
    {
        $styleAttribute = $style > 0 ? ' s="' . $style . '"' : '';

        if ($value === null || $value === '') {
            return '<c r="' . $reference . '"' . $styleAttribute . '/>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="' . $reference . '"' . $styleAttribute . '><v>'
                . $this->escape($this->numberToString($value)) . '</v></c>';
        }

        return '<c r="' . $reference . '"' . $styleAttribute . ' t="inlineStr"><is><t xml:space="preserve">'
            . $this->escape((string) $value) . '</t></is></c>';
    }

    /**
     * Konversi indeks kolom (1-based) menjadi huruf, mis. 1 => A, 27 => AA.
     */
    protected function columnLetter(int $index): string
    {
        $letters = '';

        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)) . $letters;
            $index = intdiv($index, 26);
        }

        return $letters;
    }

    protected function numberToString($value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        $formatted = number_format((float) $value, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
