<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/repositories.php';
require __DIR__ . '/includes/seed.php';

$_SESSION['install_csrf'] ??= bin2hex(random_bytes(24));
$success = false;
$message = '';
$config = databaseConfig();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['install_csrf'], (string) ($_POST['csrf_token'] ?? ''))) {
        $message = 'Phiên cài đặt không hợp lệ. Vui lòng tải lại trang.';
    } else {
        try {
            $server = databaseServer();
            $databaseName = preg_replace('/[^a-zA-Z0-9_]/', '', $config['name']);
            $server->exec("CREATE DATABASE IF NOT EXISTS `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $schema = file_get_contents(__DIR__ . '/database/schema.sql');
            foreach (preg_split('/;\s*(?:\r?\n|$)/', (string) $schema) as $statement) {
                $statement = trim($statement);
                if ($statement !== '') {
                    database()->exec($statement);
                }
            }
            dbSeed(database(), $movies);
            $success = true;
            $message = 'Cài đặt MySQL và dữ liệu mẫu thành công.';
        } catch (Throwable $exception) {
            $message = 'Không thể cài đặt: ' . $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cài đặt MySQL — CineNeon</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<main class="installer-page">
    <section class="installer-card">
        <a class="brand" href="index.php"><span class="brand-mark">▶</span><span>Cine<span>Neon</span></span></a>
        <span class="kicker">MAMP · MySQL</span>
        <h1>Cài đặt cơ sở dữ liệu</h1>
        <p>Trang này tạo database, các bảng cần thiết và dữ liệu mẫu để bạn bắt đầu sử dụng trang quản trị.</p>
        <dl class="installer-config">
            <div><dt>Máy chủ</dt><dd><?= h($config['host']) ?>:<?= h($config['port']) ?></dd></div>
            <div><dt>Database</dt><dd><?= h($config['name']) ?></dd></div>
            <div><dt>Tài khoản</dt><dd><?= h($config['user']) ?></dd></div>
        </dl>
        <?php if ($message): ?><div class="install-message <?= $success ? 'success' : 'error' ?>"><?= h($message) ?></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="admin-credentials"><strong>Tài khoản quản trị mẫu</strong><span>Email: admin@cineneon.vn</span><span>Mật khẩu: Admin@123</span><small>Hãy đổi mật khẩu sau lần đăng nhập đầu tiên.</small></div>
            <a class="button primary wide" href="index.php?page=login&next=admin">Đăng nhập quản trị</a>
        <?php else: ?>
            <form method="post"><input type="hidden" name="csrf_token" value="<?= h($_SESSION['install_csrf']) ?>"><button class="button primary wide" type="submit">Tạo database và dữ liệu mẫu</button></form>
        <?php endif; ?>
        <p class="installer-help">MAMP cần bật Apache và MySQL. Cấu hình mặc định: <code>root/root</code>, cổng <code>8889</code>.</p>
    </section>
</main>
</body>
</html>

