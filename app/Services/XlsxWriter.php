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

        $zip->close();
    }

    protected function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
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
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

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

            $xml .= '<row r="' . $rowNumber . '">';

            foreach ($row as $columnIndex => $cell) {
                $reference = $this->columnLetter($columnIndex + 1) . $rowNumber;
                $xml .= $this->cellXml($reference, $cell['value'], (int) $cell['style']);
            }

            $xml .= '</row>';
        }

        $xml .= '</sheetData></worksheet>';

        return $xml;
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
