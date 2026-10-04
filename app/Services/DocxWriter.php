<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

/**
 * Penulis file DOCX minimalis tanpa dependensi eksternal.
 *
 * Cukup untuk satu gambar + teks per halaman. Ukuran halaman & margin
 * diatur dalam satuan twip (1 cm = 567 twip).
 */
class DocxWriter
{
    /** 1 twip = 635 EMU. */
    protected const EMU_PER_TWIP = 635;

    /** @var int */
    protected $pageWidthTwips;

    /** @var int */
    protected $pageHeightTwips;

    /** @var int */
    protected $marginTwips;

    /** @var int */
    protected $maxImageWidthEmu;

    /** @var int */
    protected $maxImageHeightEmu;

    /**
     * @var array<int, array{bytes: ?string, ext: ?string, note: string, cx: int, cy: int}>
     */
    protected $pages = [];

    public function __construct(int $pageWidthTwips = 6237, int $pageHeightTwips = 8505, int $marginTwips = 284, int $noteReserveTwips = 1134)
    {
        $this->pageWidthTwips = $pageWidthTwips;
        $this->pageHeightTwips = $pageHeightTwips;
        $this->marginTwips = $marginTwips;

        $contentWidthTwips = $pageWidthTwips - (2 * $marginTwips);
        $contentHeightTwips = $pageHeightTwips - (2 * $marginTwips);

        $this->maxImageWidthEmu = max(1, $contentWidthTwips) * self::EMU_PER_TWIP;
        $this->maxImageHeightEmu = max(1, $contentHeightTwips - $noteReserveTwips) * self::EMU_PER_TWIP;
    }

    /**
     * Tambah satu halaman berisi gambar (opsional) dan catatan.
     */
    public function addPage(?string $imagePath, string $note): self
    {
        $page = ['bytes' => null, 'ext' => null, 'note' => $note, 'cx' => 0, 'cy' => 0];

        if ($imagePath !== null) {
            $bytes = @file_get_contents($imagePath);
            $info = @getimagesize($imagePath);

            if ($bytes !== false && $info) {
                [$cx, $cy] = $this->fitImage((int) $info[0], (int) $info[1]);

                $page['bytes'] = $bytes;
                $page['ext'] = $this->imageExtension($info[2] ?? 0);
                $page['cx'] = $cx;
                $page['cy'] = $cy;
            }
        }

        $this->pages[] = $page;

        return $this;
    }

    /**
     * Tulis berkas DOCX ke path tujuan.
     */
    public function save(string $path): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('Ekstensi PHP zip diperlukan untuk membuat berkas DOCX.');
        }

        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat berkas DOCX.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('word/document.xml', $this->documentXml());
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRelsXml());

        foreach ($this->pages as $index => $page) {
            if ($page['bytes'] === null) {
                continue;
            }

            $zip->addFromString('word/media/image' . ($index + 1) . '.' . $page['ext'], $page['bytes']);
        }

        $zip->close();
    }

    /**
     * Hitung ukuran gambar agar pas di area gambar, mempertahankan rasio.
     *
     * @return array{0: int, 1: int}
     */
    protected function fitImage(int $width, int $height): array
    {
        if ($width <= 0 || $height <= 0) {
            return [$this->maxImageWidthEmu, $this->maxImageHeightEmu];
        }

        $scale = min($this->maxImageWidthEmu / $width, $this->maxImageHeightEmu / $height);

        return [
            max(1, (int) round($width * $scale)),
            max(1, (int) round($height * $scale)),
        ];
    }

    protected function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Default Extension="png" ContentType="image/png"/>'
            . '<Default Extension="jpeg" ContentType="image/jpeg"/>'
            . '<Default Extension="jpg" ContentType="image/jpeg"/>'
            . '<Default Extension="gif" ContentType="image/gif"/>'
            . '<Default Extension="webp" ContentType="image/webp"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '</Types>';
    }

    protected function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';
    }

    protected function documentRelsXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

        foreach ($this->pages as $index => $page) {
            if ($page['bytes'] === null) {
                continue;
            }

            $xml .= '<Relationship Id="rId' . ($index + 1) . '" '
                . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" '
                . 'Target="media/image' . ($index + 1) . '.' . $page['ext'] . '"/>';
        }

        $xml .= '</Relationships>';

        return $xml;
    }

    protected function documentXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" '
            . 'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" '
            . 'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" '
            . 'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<w:body>';

        $lastIndex = count($this->pages) - 1;

        foreach ($this->pages as $index => $page) {
            $xml .= '<w:p><w:pPr><w:spacing w:before="120" w:after="120"/><w:jc w:val="center"/></w:pPr>';

            if ($page['bytes'] !== null) {
                $xml .= '<w:r>' . $this->drawingXml($index, $page['cx'], $page['cy']) . '</w:r>';
            }

            $xml .= '</w:p>';

            $xml .= '<w:p><w:pPr><w:spacing w:before="120" w:after="120"/><w:jc w:val="center"/></w:pPr>'
                . '<w:r><w:rPr><w:b/><w:sz w:val="28"/><w:szCs w:val="28"/></w:rPr>'
                . '<w:t xml:space="preserve">' . $this->escape($page['note']) . '</w:t></w:r></w:p>';

            if ($index < $lastIndex) {
                $xml .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
            }
        }

        $xml .= '<w:sectPr>'
            . '<w:pgSz w:w="' . $this->pageWidthTwips . '" w:h="' . $this->pageHeightTwips . '"/>'
            . '<w:pgMar w:top="' . $this->marginTwips . '" w:right="' . $this->marginTwips . '" '
            . 'w:bottom="' . $this->marginTwips . '" w:left="' . $this->marginTwips . '" '
            . 'w:header="0" w:footer="0" w:gutter="0"/>'
            . '</w:sectPr>';

        $xml .= '</w:body></w:document>';

        return $xml;
    }

    protected function drawingXml(int $index, int $cx, int $cy): string
    {
        $id = $index + 1;

        return '<w:drawing>'
            . '<wp:inline distT="0" distB="0" distL="0" distR="0">'
            . '<wp:extent cx="' . $cx . '" cy="' . $cy . '"/>'
            . '<wp:effectExtent l="0" t="0" r="0" b="0"/>'
            . '<wp:docPr id="' . $id . '" name="Bukti Pembayaran ' . $id . '"/>'
            . '<wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>'
            . '<a:graphic>'
            . '<a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">'
            . '<pic:pic>'
            . '<pic:nvPicPr>'
            . '<pic:cNvPr id="' . $id . '" name="Bukti Pembayaran ' . $id . '"/>'
            . '<pic:cNvPicPr/>'
            . '</pic:nvPicPr>'
            . '<pic:blipFill>'
            . '<a:blip r:embed="rId' . $id . '"/>'
            . '<a:stretch><a:fillRect/></a:stretch>'
            . '</pic:blipFill>'
            . '<pic:spPr>'
            . '<a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
            . '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>'
            . '</pic:spPr>'
            . '</pic:pic>'
            . '</a:graphicData>'
            . '</a:graphic>'
            . '</wp:inline>'
            . '</w:drawing>';
    }

    protected function imageExtension(int $type): string
    {
        switch ($type) {
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

    protected function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
