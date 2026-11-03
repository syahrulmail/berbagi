<?php
/**
 * Dumper database produksi murni PHP (tanpa shell_exec/mysqldump).
 *
 * Dipakai karena di hosting cPanel produksi seluruh fungsi shell dinonaktifkan
 * (proc_open/exec/shell_exec/popen). Skrip ini memakai PDO + zlib, menulis
 * gzip bertahap, sehingga aman untuk memory_limit 256M.
 *
 * CARA PAKAI (di server, lewat cPanel Terminal):
 *   php scripts/db-dump-php.php /path/output.sql.gz
 *
 * Atau via web sementara: salin ke public/, buka dengan token, lalu hapus.
 *
 * Hasil: file .sql.gz berisi DDL + INSERT per tabel, siap direstore dengan:
 *   gunzip -c output.sql.gz | mysql -u USER -p DATABASE
 */
if (PHP_SAPI !== 'cli') {
    // Pengaman: bila diakses via web, wajib token.
    header('Content-Type: text/plain');
    if (($_GET['token'] ?? '') !== getenv('DB_DUMP_TOKEN')) {
        http_response_code(404);
        exit("not found\n");
    }
}

@set_time_limit(0);
@ini_set('memory_limit', '256M');

$root = dirname(__DIR__);
$outFile = $argv[1] ?? ($root . '/backup_' . date('Ymd_His') . '.sql.gz');

// Muat kredensial dari .env Laravel.
$env = [];
foreach (file($root . '/.env') as $line) {
    if (preg_match('/^\s*([A-Z0-9_]+)\s*=\s*(.*)$/', $line, $m)) {
        $env[$m[1]] = trim($m[2], "\"' ");
    }
}

$host = $env['DB_HOST'] ?? 'localhost';
$port = $env['DB_PORT'] ?? '3306';
$db   = $env['DB_DATABASE'] ?? '';
$user = $env['DB_USERNAME'] ?? '';
$pass = $env['DB_PASSWORD'] ?? '';

if ($db === '' || $user === '') {
    fwrite(STDERR, "DB_DATABASE/DB_USERNAME kosong; periksa .env\n");
    exit(1);
}

$pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
]);

$gz = gzopen($outFile, 'wb9');
if (! $gz) {
    fwrite(STDERR, "Gagal membuka $outFile\n");
    exit(1);
}
$w = function ($s) use ($gz) { gzwrite($gz, $s); };

$w("-- Dump $db\n-- Generated: " . date('c') . "\n\n");
$w("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

$tables = $pdo->query(
    "SELECT table_name FROM information_schema.tables
     WHERE table_schema=DATABASE() AND table_type='BASE TABLE' ORDER BY table_name"
)->fetchAll(PDO::FETCH_COLUMN);

$rowTotal = 0;
foreach ($tables as $t) {
    $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_ASSOC);
    $w("DROP TABLE IF EXISTS `$t`;\n" . $create['Create Table'] . ";\n\n");

    $stmt = $pdo->query("SELECT * FROM `$t`");
    $cols = null;
    $buf = [];
    $n = 0;

    $flush = function () use (&$buf, &$cols, $t, $w) {
        if (! $buf) {
            return;
        }
        $w("INSERT INTO `$t` (`" . implode('`,`', $cols) . "`) VALUES\n"
            . implode(",\n", $buf) . ";\n");
        $buf = [];
    };

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($cols === null) {
            $cols = array_keys($row);
        }
        $vals = [];
        foreach ($row as $v) {
            if ($v === null) {
                $vals[] = 'NULL';
            } elseif (is_int($v) || is_float($v)) {
                $vals[] = $v;
            } else {
                $vals[] = $pdo->quote($v);
            }
        }
        $buf[] = '(' . implode(',', $vals) . ')';
        $n++;
        $rowTotal++;
        if (count($buf) >= 100) {
            $flush();
        }
    }
    $flush();

    if ($n === 0) {
        $w("-- (empty)\n");
    }
    $w("\n");
}

$w("SET FOREIGN_KEY_CHECKS=1;\n");
gzclose($gz);

$size = filesize($outFile);
fwrite(STDERR, sprintf(
    "OK tables=%d rows=%d size=%.2fMB file=%s\n",
    count($tables), $rowTotal, $size / 1048576, $outFile
));
