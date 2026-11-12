<?php
/**
 * Alat Diagnostik Email & Konfigurasi RAPI Sekolah
 * Hapus file ini setelah selesai pengujian.
 */

// Keamanan sederhana: nonaktifkan jika tidak diperlukan
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Deteksi folder root Laravel
$possibleRoots = [
    dirname(__DIR__),
    __DIR__,
    dirname(__DIR__, 2),
];

$root = null;
foreach ($possibleRoots as $path) {
    if (file_exists($path . '/.env')) {
        $root = $path;
        break;
    }
}

$cacheDir = $root ? $root . '/bootstrap/cache' : null;
$configCache = $cacheDir ? $cacheDir . '/config.php' : null;
$routesCache = $cacheDir ? $cacheDir . '/routes-v7.php' : null;

// Aksi: Hapus cache jika diminta
$actionMessage = '';
if (isset($_GET['action']) && $_GET['action'] === 'clear_cache') {
    $cleared = [];
    if ($configCache && file_exists($configCache)) {
        if (@unlink($configCache)) $cleared[] = 'config.php';
    }
    if ($routesCache && file_exists($routesCache)) {
        if (@unlink($routesCache)) $cleared[] = 'routes-v7.php';
    }
    if (!empty($cleared)) {
        $actionMessage = '<div class="alert alert-success">✅ Berhasil menghapus cache: ' . implode(', ', $cleared) . '. Laravel sekarang akan membaca ulang file .env!</div>';
    } else {
        $actionMessage = '<div class="alert alert-info">ℹ️ File cache sudah bersih (tidak ada yang perlu dihapus).</div>';
    }
}

// Baca .env langsung
$envData = [];
if ($root && file_exists($root . '/.env')) {
    $lines = file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Lepas tanda petik jika ada
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            $envData[$key] = $value;
        }
    }
}

// Cek status socket Gmail SMTP
$socket465Result = null;
$socket465Error = '';
$socket465Time = 0;
$t0 = microtime(true);
$fp = @fsockopen('ssl://smtp.gmail.com', 465, $errno, $errstr, 5);
if ($fp) {
    $socket465Time = round((microtime(true) - $t0) * 1000);
    $banner = trim((string) fgets($fp, 512));
    fclose($fp);
    $socket465Result = true;
} else {
    $socket465Result = false;
    $socket465Error = $errstr ?: 'Koneksi ditolak/timeout (Error ' . $errno . ')';
}

// Cek Port 587 (TLS)
$socket587Result = null;
$socket587Error = '';
$t0 = microtime(true);
$fp = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 5);
if ($fp) {
    fclose($fp);
    $socket587Result = true;
} else {
    $socket587Result = false;
    $socket587Error = $errstr ?: 'Koneksi ditolak/timeout (Error ' . $errno . ')';
}

// Kirim tes email jika diminta
$sendResult = null;
if (isset($_GET['action']) && $_GET['action'] === 'test_mail') {
    $targetEmail = $envData['MAIL_USERNAME'] ?? 'aditpw88@gmail.com';
    $targetEmail = filter_var($_GET['to'] ?? $targetEmail, FILTER_VALIDATE_EMAIL) ?: 'aditpw88@gmail.com';
    
    // Coba via mail() bawaan server
    $subject = 'Uji Coba Email RAPI (' . date('Y-m-d H:i:s') . ')';
    $message = "Halo!\n\nIni adalah pesan uji coba dari script diagnostik cPanel RAPI Sekolah.\nJika email ini sampai ke inbox/spam Anda, berarti server berhasil mengirim email!\n\nWaktu: " . date('Y-m-d H:i:s');
    $headers = "From: " . ($envData['MAIL_FROM_ADDRESS'] ?? 'no-reply@skansaba-rapi.my.id') . "\r\n" .
               "Reply-To: " . ($envData['MAIL_FROM_ADDRESS'] ?? 'no-reply@skansaba-rapi.my.id') . "\r\n" .
               "X-Mailer: PHP/" . phpversion();

    if (@mail($targetEmail, $subject, $message, $headers)) {
        $sendResult = ['success' => true, 'msg' => "Email berhasil dikirim via fungsi mail() bawaan server ke $targetEmail. Silakan periksa Inbox dan folder SPAM!"];
    } else {
        $lastErr = error_get_last();
        $sendResult = ['success' => false, 'msg' => "Fungsi mail() gagal: " . ($lastErr['message'] ?? 'Unknown error')];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostik Email & Konfigurasi RAPI</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f3f4f6; color: #1f2937; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); padding: 28px; }
        h1 { margin-top: 0; font-size: 22px; color: #111827; border-bottom: 2px solid #e5e7eb; padding-bottom: 12px; }
        h2 { font-size: 16px; margin-top: 24px; margin-bottom: 12px; color: #374151; }
        .alert { padding: 14px 18px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; line-height: 1.5; }
        .alert-danger { background: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }
        .alert-warning { background: #fef3c7; color: #92400e; border-left: 5px solid #f59e0b; }
        .alert-success { background: #dcfce7; color: #166534; border-left: 5px solid #22c55e; }
        .alert-info { background: #e0e7ff; color: #3730a3; border-left: 5px solid #6366f1; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 13px; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-weight: 600; color: #4b5563; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 13px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 9999px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #15803d; }
        .badge-danger { background: #fee2e2; color: #b91c1c; }
        .badge-warning { background: #fef3c7; color: #b45309; }
        .btn { display: inline-block; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; cursor: pointer; border: none; }
        .btn-primary { background: #ea580c; color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-secondary { background: #4b5563; color: #fff; }
        .btn:hover { opacity: 0.9; }
        .mt-4 { margin-top: 16px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Diagnostik Email & Konfigurasi RAPI cPanel</h1>
    <?= $actionMessage ?>

    <!-- 1. STATUS CACHE CONFIG -->
    <h2>1. Status Cache Konfigurasi Laravel</h2>
    <?php if ($configCache && file_exists($configCache)): ?>
        <div class="alert alert-danger">
            <strong>⚠️ DITEMUKAN FILE CACHE KONFIGURASI:</strong><br>
            File <code><?= htmlspecialchars($configCache) ?></code> sedang aktif!<br>
            <strong>DAMPAK:</strong> Semua perubahan yang Anda ketik di file <code>.env</code> <strong>TIDAK AKAN DIBACA SAMA SEKALI</strong> oleh Laravel karena Laravel selalu memakai cache lama ini.<br><br>
            <a href="?action=clear_cache" class="btn btn-danger">🗑️ Hapus Cache Sekarang (1-Klik)</a>
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            <strong>✅ Cache konfigurasi bersih!</strong> Laravel membaca langsung isi file <code>.env</code> Anda saat ini.
        </div>
    <?php endif; ?>

    <!-- 2. STATUS KONEKSI SMTP KE GMAIL DARI SERVER INI -->
    <h2>2. Uji Koneksi Port Firewall Hosting ke Gmail SMTP</h2>
    <table>
        <tr>
            <th>Tujuan Port</th>
            <th>Status Koneksi</th>
            <th>Keterangan</th>
        </tr>
        <tr>
            <td><strong>smtp.gmail.com:465 (SSL)</strong></td>
            <td>
                <?php if ($socket465Result): ?>
                    <span class="badge badge-success">✅ Terbuka (<?= $socket465Time ?> ms)</span>
                <?php else: ?>
                    <span class="badge badge-danger">❌ Terblokir / Timeout</span>
                <?php endif; ?>
            </td>
            <td><?= $socket465Result ? 'Server hosting diizinkan menghubungi Gmail port 465.' : '<strong>Firewall cPanel memblokir port 465!</strong> (' . htmlspecialchars($socket465Error) . ')' ?></td>
        </tr>
        <tr>
            <td><strong>smtp.gmail.com:587 (TLS)</strong></td>
            <td>
                <?php if ($socket587Result): ?>
                    <span class="badge badge-success">✅ Terbuka</span>
                <?php else: ?>
                    <span class="badge badge-danger">❌ Terblokir / Timeout</span>
                <?php endif; ?>
            </td>
            <td><?= $socket587Result ? 'Server hosting diizinkan menghubungi Gmail port 587.' : 'Firewall cPanel memblokir port 587 (' . htmlspecialchars($socket587Error) . ').' ?></td>
        </tr>
    </table>

    <?php if (!$socket465Result && !$socket587Result): ?>
        <div class="alert alert-warning">
            <strong>⚠️ PERHATIAN: Hosting cPanel Anda Memblokir SMTP Keluar!</strong><br>
            Server hosting shared cPanel Anda mengaktifkan <em>SMTP_BLOCK</em> sehingga PHP di server ini tidak dapat menghubungi server luar (Google).<br>
            <strong>Solusi 100% Ampuh:</strong> Ubah <code>MAIL_MAILER</code> di <code>.env</code> menjadi <code>sendmail</code> (menggunakan mail server internal cPanel):
            <pre style="background: #1e293b; color: #f8fafc; padding: 12px; border-radius: 6px; overflow-x: auto;">
MAIL_MAILER=sendmail
MAIL_HOST=127.0.0.1
MAIL_PORT=25
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="admin@skansaba-rapi.my.id"
MAIL_FROM_NAME="Sistem RAPI"</pre>
        </div>
    <?php endif; ?>

    <!-- 3. ISI KONFIGURASI .ENV -->
    <h2>3. Nilai Pengaturan di File <code>.env</code></h2>
    <table>
        <tr><th>Kunci</th><th>Nilai Saat Ini</th><th>Analisis / Rekomendasi</th></tr>
        <?php
        $keys = [
            'MAIL_MAILER' => 'smtp / sendmail',
            'MAIL_HOST' => 'smtp.gmail.com',
            'MAIL_PORT' => '465 / 587',
            'MAIL_USERNAME' => 'aditpw88@gmail.com',
            'MAIL_PASSWORD' => '16 karakter App Password',
            'MAIL_ENCRYPTION' => 'ssl (untuk 465) / tls (untuk 587)',
            'MAIL_FROM_ADDRESS' => 'aditpw88@gmail.com',
            'MAIL_FROM_NAME' => 'Sistem RAPI',
        ];
        foreach ($keys as $k => $desc):
            $val = $envData[$k] ?? '<em>(Belum diisi)</em>';
            $notice = '';
            if ($k === 'MAIL_PASSWORD') {
                $rawVal = $val;
                $len = strlen($rawVal);
                $hasSpace = str_contains($rawVal, ' ');
                $hasQuotes = str_starts_with($rawVal, '"') || str_starts_with($rawVal, "'");
                if ($hasSpace) {
                    $notice = '<span class="badge badge-danger">❌ MASIH ADA SPASI! Hapus semua spasi dari password!</span>';
                } elseif ($len < 16) {
                    $notice = '<span class="badge badge-warning">⚠️ Panjang password kurang dari 16 karakter</span>';
                } else {
                    $notice = '<span class="badge badge-success">✅ Format valid (16 karakter tanpa spasi)</span>';
                }
                $val = substr($rawVal, 0, 4) . '••••••••' . substr($rawVal, -4) . " (Panjang: $len)";
            } elseif ($k === 'MAIL_PORT' && $val == '465') {
                $notice = ($envData['MAIL_ENCRYPTION'] ?? '') === 'ssl' ? '<span class="badge badge-success">✅ Cocok dengan SSL</span>' : '<span class="badge badge-warning">⚠️ Port 465 sebaiknya MAIL_ENCRYPTION=ssl</span>';
            }
        ?>
        <tr>
            <td><code><?= $k ?></code></td>
            <td><strong><?= htmlspecialchars($val) ?></strong></td>
            <td><?= $notice ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- 4. TES PENGIRIMAN EMAIL SEDERHANA -->
    <h2>4. Uji Coba Pengiriman Email Server</h2>
    <?php if ($sendResult): ?>
        <div class="alert <?= $sendResult['success'] ? 'alert-success' : 'alert-danger' ?>">
            <?= $sendResult['msg'] ?>
        </div>
    <?php endif; ?>
    <p>Uji coba fungsi <code>mail()</code> lokal server hosting ke <strong><?= htmlspecialchars($envData['MAIL_USERNAME'] ?? 'aditpw88@gmail.com') ?></strong>:</p>
    <a href="?action=test_mail" class="btn btn-primary">✉️ Kirim Email Uji Coba Sekarang</a>

    <div class="mt-4" style="border-top: 1px solid #e5e7eb; padding-top: 14px; font-size: 12px; color: #6b7280;">
        ⚠️ <strong>Catatan Keamanan:</strong> Setelah masalah email teratasi, hapus file <code>cek_email.php</code> ini dari File Manager demi keamanan website.
    </div>
</div>
</body>
</html>

