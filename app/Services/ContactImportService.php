<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Contact;
use App\Models\User;

class ContactImportService
{
    public function parsePaste(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = $this->splitLine($line);
        }

        return $rows;
    }

    public function parseFile(string $path, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        switch ($ext) {
            case 'csv':
            case 'txt':
                return $this->parseDelimitedText(file_get_contents($path));
            case 'xlsx':
                return $this->parseXlsx($path);
            case 'xls':
                return $this->parseXls($path);
            default:
                throw new \RuntimeException('Ekstensi file tidak didukung. Gunakan file .xls, .xlsx, .csv, atau .txt.');
        }
    }

    public function processRows(array $rows): array
    {
        $branches = Branch::all();
        $agents = User::where('role', 'agen')->get();

        $errors = [];
        $contacts = [];
        $headerMap = null;

        if (isset($rows[0]) && $this->isHeaderRow($rows[0])) {
            $headerMap = $this->mapColumns($rows[0]);
            array_shift($rows);
        }

        $existingPhones = $this->normalizedPhoneMap();
        $seenPhones = [];

        foreach ($rows as $idx => $cells) {
            $lineNo = $headerMap ? $idx + 2 : $idx + 1;
            $cells = array_map('trim', $cells);

            if (count($cells) === 1 && $cells[0] === '') {
                continue;
            }

            if ($headerMap) {
                $branchName = $cells[$headerMap['cabang']] ?? '';
                $agentName = $cells[$headerMap['agen']] ?? '';
                $name = $cells[$headerMap['nama']] ?? '';
                $phone = $cells[$headerMap['phone']] ?? '';
                $statusLabel = $cells[$headerMap['status']] ?? '';
                $notes = $cells[$headerMap['catatan']] ?? '';
            } else {
                $branchName = $cells[0] ?? '';
                $agentName = $cells[1] ?? '';
                $name = $cells[2] ?? '';
                $phone = $cells[3] ?? '';
                $statusLabel = $cells[4] ?? '';
                $notes = $cells[5] ?? '';
            }

            if ($name === '') {
                $errors[] = "Baris {$lineNo}: Nama wajib diisi.";
                continue;
            }

            if ($phone === '') {
                $errors[] = "Baris {$lineNo}: No. WhatsApp wajib diisi.";
                continue;
            }

            $normalizedPhone = $this->normalizePhone($phone);
            if ($normalizedPhone === null) {
                $errors[] = "Baris {$lineNo}: No. WhatsApp '{$phone}' tidak valid. Gunakan 10-15 digit angka (contoh: 62812xxxxxxx atau 0812xxxxxxx).";
                continue;
            }

            if (isset($seenPhones[$normalizedPhone])) {
                $errors[] = "Baris {$lineNo}: No. WhatsApp {$normalizedPhone} sama dengan baris {$seenPhones[$normalizedPhone]} pada tempelan/file ini.";
                continue;
            }
            $seenPhones[$normalizedPhone] = $lineNo;

            if (isset($existingPhones[$normalizedPhone])) {
                $errors[] = "Baris {$lineNo}: No. WhatsApp {$normalizedPhone} sudah terdaftar atas nama '{$existingPhones[$normalizedPhone]['name']}'.";
                continue;
            }

            $status = $this->mapStatus($statusLabel);
            if ($status === null) {
                $errors[] = "Baris {$lineNo}: Status '{$statusLabel}' tidak dikenali. Gunakan Prospek, Simpan, Wakif, atau Stop.";
                continue;
            }

            $branchId = null;
            if ($branchName !== '') {
                $branchId = $this->findBranchId($branchName, $branches);
                if ($branchId === false) {
                    $errors[] = "Baris {$lineNo}: Cabang '{$branchName}' tidak ditemukan. Tambahkan Cabang tersebut dulu atau perbaiki nama.";
                    continue;
                }
            }

            $agentId = null;
            if ($agentName !== '') {
                $matches = $this->findAgentIds($agentName, $agents, $branchId);
                if (count($matches) === 0) {
                    $errors[] = "Baris {$lineNo}: Agent '{$agentName}' tidak ditemukan. Tambahkan Agent tersebut dulu atau perbaiki nama.";
                    continue;
                }
                if (count($matches) > 1) {
                    $errors[] = "Baris {$lineNo}: Nama Agent '{$agentName}' tidak unik di cabang yang sama. Sebutkan nama yang lebih spesifik atau cantumkan Cabangnya.";
                    continue;
                }
                $agentId = $matches[0];
            }

            $contacts[] = [
                'name' => $name,
                'phone' => $normalizedPhone,
                'status' => $status,
                'agen_id' => $agentId,
                'branch_id' => $branchId,
                'notes' => $notes,
            ];
        }

        return ['contacts' => $contacts, 'errors' => $errors];
    }

    public function createContacts(array $contacts, ?User $user): int
    {
        $created = 0;

        foreach ($contacts as $data) {
            if ($user && $user->isAgen()) {
                $data['agen_id'] = $user->id;
                $data['branch_id'] = $user->branch_id;
            }
            $data['phone'] = $this->normalizePhone($data['phone'] ?? '') ?? ($data['phone'] ?? '');
            Contact::create($data);
            $created++;
        }

        return $created;
    }

    protected function splitLine(string $line): array
    {
        $cells = str_getcsv($line, ',');
        if (count($cells) > 1) {
            return $cells;
        }
        $cells = str_getcsv($line, ';');
        if (count($cells) > 1) {
            return $cells;
        }
        return str_getcsv($line, "\t");
    }

    protected function parseDelimitedText(string $content): array
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = $this->splitLine($line);
        }

        return $rows;
    }

    protected function parseXlsx(string $path): array
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('Ekstensi ZIP tidak tersedia untuk membaca .xlsx.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('File .xlsx tidak dapat dibuka atau rusak.');
        }

        $shared = [];
        $sstXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sstXml !== false) {
            $xml = simplexml_load_string($sstXml);
            foreach ($xml->si as $si) {
                $shared[] = $this->xlsxCellText($si);
            }
        }

        $sheetName = null;
        for ($i = 1; $i <= 20; $i++) {
            $candidate = 'xl/worksheets/sheet' . $i . '.xml';
            if ($zip->getFromName($candidate) !== false) {
                $sheetName = $candidate;
                break;
            }
        }

        if ($sheetName === null) {
            $zip->close();
            throw new \RuntimeException('Tidak ditemukan sheet pada file .xlsx.');
        }

        $xml = simplexml_load_string($zip->getFromName($sheetName));
        $zip->close();

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $colIdx = $this->columnIndex($ref);
                $type = (string) $c['t'];
                $value = trim((string) $c->v);

                if ($type === 's') {
                    $val = $shared[(int) $value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = $this->xlsxCellText($c->is);
                } else {
                    $val = $value;
                }

                $cells[$colIdx] = $val;
            }

            $flat = [];
            $max = $cells ? max(array_keys($cells)) : -1;
            for ($i = 0; $i <= $max; $i++) {
                $flat[] = $cells[$i] ?? '';
            }
            $rows[] = $flat;
        }

        return $rows;
    }

    protected function xlsxCellText($node): string
    {
        $text = '';
        if (isset($node->t)) {
            foreach ($node->t as $t) {
                $text .= (string) $t;
            }
        }
        if (isset($node->r)) {
            foreach ($node->r as $run) {
                if (isset($run->t)) {
                    $text .= (string) $run->t;
                }
            }
        }
        return $text;
    }

    protected function columnIndex(string $ref): int
    {
        $letters = '';
        for ($i = 0; $i < strlen($ref); $i++) {
            $ch = $ref[$i];
            if (ctype_alpha($ch)) {
                $letters .= $ch;
            } else {
                break;
            }
        }
        $index = 0;
        $letters = strtoupper($letters);
        for ($i = 0; $i < strlen($letters); $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }

    protected function parseXls(string $path): array
    {
        $data = @file_get_contents($path);
        if ($data === false) {
            throw new \RuntimeException('File .xls tidak dapat dibaca.');
        }

        if (strlen($data) < 512 || substr($data, 0, 8) !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
            return $this->parseDelimitedText($data);
        }

        $stream = $this->readOleWorkbookStream($data);
        if ($stream === null) {
            throw new \RuntimeException('Tidak dapat menemukan data Workbook pada file .xls. Simpan ulang sebagai .xlsx atau .csv.');
        }

        return $this->parseBiffCells($stream);
    }

    protected function readOleWorkbookStream(string $data): ?string
    {
        $sectorSize = 1 << (ord($data[0x1E]));
        $miniSectorSize = 1 << (ord($data[0x20]));

        $u = unpack('VnFAT/VfirstDir/VtransSig/VminiCut/VfirstMiniFAT/VnMiniFAT/VfirstDIFAT/VnDIFAT', substr($data, 0x2C, 32));
        $nFAT = $u['nFAT'];
        $firstDir = $u['firstDir'];
        $miniCut = $u['miniCut'];
        $firstMiniFAT = $u['firstMiniFAT'];
        $nMiniFAT = $u['nMiniFAT'];
        $firstDIFAT = $u['firstDIFAT'];
        $nDIFAT = $u['nDIFAT'];

        $fatSectors = array_values(unpack('V109', substr($data, 0x4C, 436)));
        $fatSectors = array_values(array_filter($fatSectors, function ($v) {
            return $v < 0xFFFFFFFC;
        }));

        if ($nDIFAT > 0 && $nDIFAT < 0xFFFFFFFC && $firstDIFAT >= 0 && $firstDIFAT < 0xFFFFFFFC) {
            $cur = $firstDIFAT;
            $remaining = $nDIFAT;
            while ($cur >= 0 && $cur < 0xFFFFFFFC && $remaining > 0) {
                $sectorData = substr($data, (1 + $cur) * $sectorSize, $sectorSize);
                $entries = array_values(unpack('V' . ($sectorSize / 4), $sectorData));
                $nextDIFAT = $entries[$sectorSize / 4 - 1];
                for ($i = 0; $i < $sectorSize / 4 - 1 && count($fatSectors) < $nFAT; $i++) {
                    if ($entries[$i] >= 0xFFFFFFFC) {
                        break;
                    }
                    $fatSectors[] = $entries[$i];
                }
                $cur = $nextDIFAT;
                $remaining--;
            }
        }

        $fatCache = [];
        $fatEntry = function (int $sectorNum) use (&$fatCache, $fatSectors, $sectorSize, $data) {
            $entriesPerSector = $sectorSize / 4;
            $fatSectorIdx = intdiv($sectorNum, $entriesPerSector);
            if (!isset($fatSectors[$fatSectorIdx])) {
                return 0xFFFFFFFE;
            }
            $fs = $fatSectors[$fatSectorIdx];
            if (!isset($fatCache[$fs])) {
                $fatCache[$fs] = array_values(unpack('V' . $entriesPerSector, substr($data, (1 + $fs) * $sectorSize, $sectorSize)));
            }
            return $fatCache[$fs][$sectorNum % $entriesPerSector] ?? 0xFFFFFFFE;
        };

        $chain = function (int $start) use ($fatEntry) {
            $sectors = [];
            $cur = $start;
            $guard = 0;
            while ($cur >= 0 && $cur !== 0xFFFFFFFE && $cur !== 0xFFFFFFFF && $guard++ < 200000) {
                $sectors[] = $cur;
                $cur = $fatEntry($cur);
            }
            return $sectors;
        };

        $readRegular = function (int $start, int $size) use ($chain, $sectorSize, $data) {
            $out = '';
            $remaining = $size;
            foreach ($chain($start) as $sec) {
                if ($remaining <= 0) {
                    break;
                }
                $out .= substr($data, (1 + $sec) * $sectorSize, min($sectorSize, $remaining));
                $remaining -= $sectorSize;
            }
            return $out;
        };

        $miniStream = '';
        $miniFatEntries = [];
        $rootStart = 0;
        $rootSize = 0;

        $dirStream = $readRegular($firstDir, PHP_INT_MAX);

        for ($offset = 0; $offset + 128 <= strlen($dirStream); $offset += 128) {
            $entry = substr($dirStream, $offset, 128);
            $type = ord($entry[0x42]);
            $startSector = unpack('V', substr($entry, 0x74, 4))[1];
            $size = unpack('V', substr($entry, 0x78, 4))[1];
            $nameLen = unpack('v', substr($entry, 0x40, 2))[1];
            $nameBytes = $nameLen > 2 ? substr($entry, 0, $nameLen - 2) : '';
            $name = $this->utf16leToUtf8($nameBytes);

            if ($type === 5) {
                $rootStart = $startSector;
                $rootSize = $size;
            } elseif ($type === 2 && ($name === 'Workbook' || $name === 'Book')) {
                if ($size >= $miniCut) {
                    return $readRegular($startSector, $size);
                }
                $miniStreamStart = $startSector;
                $miniStreamSize = $size;

                if ($rootSize > 0) {
                    $miniStream = $readRegular($rootStart, $rootSize);
                }

                if ($firstMiniFAT >= 0 && $nMiniFAT > 0) {
                    foreach ($chain($firstMiniFAT) as $sec) {
                        $miniFatEntries[] = array_values(unpack('V' . ($sectorSize / 4), substr($data, (1 + $sec) * $sectorSize, $sectorSize)));
                    }
                }

                $out = '';
                $remaining = $miniStreamSize;
                $cur = $miniStreamStart;
                $guard = 0;
                while ($cur >= 0 && $cur !== 0xFFFFFFFE && $guard++ < 200000) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $out .= substr($miniStream, $cur * $miniSectorSize, min($miniSectorSize, $remaining));
                    $remaining -= $miniSectorSize;
                    $fatSectorIdx = intdiv($cur, $sectorSize / 4);
                    $cur = $miniFatEntries[$fatSectorIdx][$cur % ($sectorSize / 4)] ?? 0xFFFFFFFE;
                }
                return $out;
            }
        }

        return null;
    }

    protected function utf16leToUtf8(string $s): string
    {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($s, 'UTF-8', 'UTF-16LE');
        }
        return @iconv('UTF-16LE', 'UTF-8', $s) ?: '';
    }

    protected function parseBiffCells(string $stream): array
    {
        $len = strlen($stream);
        $pos = 0;
        $sst = [];
        $currentRow = -1;
        $grid = [];
        $formulaString = null;

        while ($pos + 4 <= $len) {
            $u = unpack('vop/vlength', substr($stream, $pos, 4));
            $op = $u['op'];
            $length = $u['length'];
            $record = substr($stream, $pos + 4, $length);
            $pos += 4 + $length;

            switch ($op) {
                case 0x00FC:
                    $sst = $this->parseSst($record, $stream, $pos, $len);
                    break;
                case 0x0208:
                    $currentRow = unpack('v', substr($record, 0, 2))[1];
                    break;
                case 0x00FD:
                    $row = unpack('v', substr($record, 0, 2))[1];
                    $col = unpack('v', substr($record, 2, 2))[1];
                    $isst = unpack('V', substr($record, 6, 4))[1];
                    $grid[$row][$col] = $sst[$isst] ?? '';
                    break;
                case 0x0204:
                    $row = unpack('v', substr($record, 0, 2))[1];
                    $col = unpack('v', substr($record, 2, 2))[1];
                    $cch = unpack('v', substr($record, 6, 2))[1];
                    $flags = ord($record[8]);
                    $chars = ($flags & 0x01)
                        ? $this->utf16leToUtf8(substr($record, 9, $cch * 2))
                        : substr($record, 9, $cch);
                    $grid[$row][$col] = $chars;
                    break;
                case 0x0203:
                    $row = unpack('v', substr($record, 0, 2))[1];
                    $col = unpack('v', substr($record, 2, 2))[1];
                    $d = unpack('d', substr($record, 6, 8))[1];
                    $grid[$row][$col] = $this->formatNumber($d);
                    break;
                case 0x027E:
                    $row = unpack('v', substr($record, 0, 2))[1];
                    $col = unpack('v', substr($record, 2, 2))[1];
                    $grid[$row][$col] = $this->parseRk(substr($record, 6, 4));
                    break;
                case 0x00BD:
                    $row = unpack('v', substr($record, 0, 2))[1];
                    $colFirst = unpack('v', substr($record, 2, 2))[1];
                    $colLast = unpack('v', substr($record, -2, 2))[1];
                    for ($i = 0; $i <= $colLast - $colFirst; $i++) {
                        $grid[$row][$colFirst + $i] = $this->parseRk(substr($record, 6 + $i * 6, 4));
                    }
                    break;
                case 0x0201:
                    $row = unpack('v', substr($record, 0, 2))[1];
                    $col = unpack('v', substr($record, 2, 2))[1];
                    $grid[$row][$col] = '';
                    break;
                case 0x00BE:
                    $row = unpack('v', substr($record, 0, 2))[1];
                    $colFirst = unpack('v', substr($record, 2, 2))[1];
                    $colLast = unpack('v', substr($record, -2, 2))[1];
                    for ($i = 0; $i <= $colLast - $colFirst; $i++) {
                        $grid[$row][$colFirst + $i] = '';
                    }
                    break;
                case 0x0006:
                    $row = unpack('v', substr($record, 0, 2))[1];
                    $col = unpack('v', substr($record, 2, 2))[1];
                    $res = substr($record, 6, 8);
                    if (substr($res, 0, 2) === "\xFF\xFF") {
                        $formulaString = ['row' => $row, 'col' => $col];
                    } else {
                        $d = unpack('d', $res)[1];
                        $grid[$row][$col] = $this->formatNumber($d);
                    }
                    break;
                case 0x0207:
                    $cch = unpack('v', substr($record, 0, 2))[1];
                    $flags = ord($record[2]);
                    $chars = ($flags & 0x01)
                        ? $this->utf16leToUtf8(substr($record, 3, $cch * 2))
                        : substr($record, 3, $cch);
                    if ($formulaString !== null) {
                        $grid[$formulaString['row']][$formulaString['col']] = $chars;
                        $formulaString = null;
                    }
                    break;
            }
        }

        return $grid;
    }

    protected function parseSst(string $firstRecord, string $stream, int &$pos, int $len): array
    {
        $count = unpack('V', $firstRecord)[1];
        $buf = $firstRecord;
        $bufPos = 8;
        $strings = [];

        $nextContinue = function () use (&$pos, $len, $stream) {
            if ($pos + 4 > $len) {
                return '';
            }
            $u = unpack('vop/vlength', substr($stream, $pos, 4));
            if ($u['op'] !== 0x003C) {
                return '';
            }
            $record = substr($stream, $pos + 4, $u['length']);
            $pos += 4 + $u['length'];
            return $record;
        };

        for ($i = 0; $i < $count; $i++) {
            while ($bufPos + 3 > strlen($buf)) {
                $buf = $nextContinue();
                $bufPos = 1;
                if ($buf === '') {
                    break;
                }
            }
            if ($buf === '') {
                break;
            }

            $cch = unpack('v', substr($buf, $bufPos, 2))[1];
            $flags = ord($buf[$bufPos + 2]);
            $bufPos += 3;
            $is16 = (bool) ($flags & 0x01);
            $remaining = $cch;
            $chars = '';

            while ($remaining > 0) {
                $charBytes = $is16 ? 2 : 1;
                $avail = max(0, strlen($buf) - $bufPos);
                $needed = $remaining * $charBytes;
                if ($avail >= $needed) {
                    $chars .= $is16
                        ? $this->utf16leToUtf8(substr($buf, $bufPos, $needed))
                        : substr($buf, $bufPos, $needed);
                    $bufPos += $needed;
                    $remaining = 0;
                } else {
                    $take = intdiv($avail, $charBytes) * $charBytes;
                    $chars .= $is16
                        ? $this->utf16leToUtf8(substr($buf, $bufPos, $take))
                        : substr($buf, $bufPos, $take);
                    $remaining -= intdiv($avail, $charBytes);
                    $buf = $nextContinue();
                    $bufPos = 1;
                    if ($buf !== '') {
                        $flags = ord($buf[0]);
                        $is16 = (bool) ($flags & 0x01);
                    } else {
                        $remaining = 0;
                    }
                }
            }

            $strings[] = $chars;

            $need = 0;
            if ($flags & 0x08) {
                $need += 2;
            }
            if ($flags & 0x04) {
                $need += 4;
            }
            while ($need > 0) {
                if ($buf === '') {
                    break;
                }
                $avail = max(0, strlen($buf) - $bufPos);
                if ($avail >= $need) {
                    $bufPos += $need;
                    $need = 0;
                } else {
                    $need -= $avail;
                    $buf = $nextContinue();
                    $bufPos = 1;
                }
            }
        }

        return $strings;
    }

    protected function parseRk(string $rk): string
    {
        $v = unpack('V', $rk)[1];
        $div100 = ($v >> 2) & 0x01;
        $isInt = ($v >> 1) & 0x01;

        if ($isInt) {
            $val = $v >> 2;
            if ($val & 0x20000000) {
                $val -= 0x40000000;
            }
            return $this->formatNumber($div100 ? $val / 100 : $val);
        }

        $d = unpack('d', pack('V', 0) . pack('V', $v & 0xFFFFFFFC))[1];
        return $this->formatNumber($div100 ? $d / 100 : $d);
    }

    protected function formatNumber(float $d): string
    {
        if (is_finite($d) && (float) (int) $d === $d && abs($d) < 1e15) {
            return (string) (int) $d;
        }
        return trim((string) $d);
    }

    protected function isHeaderRow(array $cells): bool
    {
        $exact = [
            'cabang', 'branch', 'agen', 'agent',
            'nama', 'name',
            'no', 'no whatsapp', 'no. whatsapp', 'no wa', 'no. wa', 'whatsapp', 'wa', 'phone', 'hp', 'telp', 'telepon',
            'status',
            'catatan', 'notes', 'note', 'keterangan', 'ket',
        ];
        $hits = 0;
        foreach ($cells as $cell) {
            $n = preg_replace('/\s+/', ' ', strtolower(trim($cell)));
            if (in_array($n, $exact, true)) {
                $hits++;
            }
        }
        return $hits >= 2;
    }

    protected function mapColumns(array $header): array
    {
        $map = ['cabang' => null, 'agen' => null, 'nama' => null, 'phone' => null, 'status' => null, 'catatan' => null];
        $rules = [
            'cabang' => ['cabang', 'branch'],
            'agen' => ['agen', 'agent'],
            'nama' => ['nama', 'name'],
            'phone' => ['whatsapp', 'no whatsapp', 'no. whatsapp', 'no wa', 'no. wa', 'phone', 'hp', 'telp', 'telepon'],
            'status' => ['status'],
            'catatan' => ['catatan', 'notes', 'note', 'keterangan', 'ket'],
        ];

        foreach ($header as $i => $cell) {
            $norm = preg_replace('/\s+/', ' ', strtolower(trim($cell)));
            foreach ($rules as $field => $keys) {
                foreach ($keys as $key) {
                    if ($norm === $key || strpos($norm, $key) !== false) {
                        $map[$field] = $i;
                        break 2;
                    }
                }
            }
        }

        return $map;
    }

    public function normalizePhone(string $phone): ?string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return null;
        }

        if (preg_match('/[^\d\s\-().+]/', $phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return null;
        }

        if (substr($digits, 0, 2) === '62') {
            // already in international format
        } elseif ($digits[0] === '0') {
            $digits = '62' . substr($digits, 1);
        } elseif ($digits[0] === '8') {
            $digits = '62' . $digits;
        } else {
            return null;
        }

        $len = strlen($digits);
        if ($len < 10 || $len > 15) {
            return null;
        }

        return $digits;
    }

    public function normalizedPhoneMap(): array
    {
        $map = [];
        foreach (Contact::all(['id', 'phone', 'name']) as $contact) {
            $norm = $this->normalizePhone($contact->phone);
            if ($norm !== null && !isset($map[$norm])) {
                $map[$norm] = ['id' => (int) $contact->id, 'name' => $contact->name];
            }
        }
        return $map;
    }

    protected function findBranchId(string $name, $branches)
    {
        $norm = $this->normalizeName($name);
        foreach ($branches as $branch) {
            $bn = $this->normalizeName($branch->name);
            $bnStripped = $this->normalizeName(preg_replace('/^cabang\s*/i', '', $branch->name));
            if ($norm === $bn || $norm === $bnStripped) {
                return $branch->id;
            }
        }
        return false;
    }

    protected function findAgentIds(string $name, $agents, ?int $branchId = null): array
    {
        $norm = $this->normalizeName($name);
        $matches = [];
        foreach ($agents as $agent) {
            if ($branchId !== null && (int) $agent->branch_id !== $branchId) {
                continue;
            }
            $an = $this->normalizeName($agent->name);
            $anStripped = $this->normalizeName(preg_replace('/^agen\s*/i', '', $agent->name));
            if ($norm === $an || $norm === $anStripped) {
                $matches[] = (int) $agent->id;
            }
        }
        return array_values(array_unique($matches));
    }

    protected function normalizeName(string $name): string
    {
        return preg_replace('/\s+/', ' ', strtolower(trim($name)));
    }

    protected function mapStatus(string $label): ?string
    {
        $map = [
            'prospek' => 'prospect',
            'prospect' => 'prospect',
            'simpan' => 'contacted',
            'contacted' => 'contacted',
            'wakif' => 'donated',
            'donated' => 'donated',
            'donatur' => 'donated',
            'stop' => 'churned',
            'churned' => 'churned',
            'berhenti' => 'churned',
        ];
        return $map[strtolower(trim($label))] ?? null;
    }
}
