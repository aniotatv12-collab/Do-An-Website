<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0a0d12">
    <meta name="description" content="CineNeon — trải nghiệm đặt vé rạp chiếu phim hiện đại.">
    <title><?= h($title) ?></title>
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400..700,0..1,0&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css?v=20261019-1">
</head>
<body class="<?= $isAdmin ? 'admin-body' : '' ?>">
<a class="skip-link" href="#main-content">Bỏ qua điều hướng</a>
<?php if (!$isAdmin): ?>
<header class="site-header" data-header>
    <div class="container header-inner">
        <a class="brand" href="index.php?page=home" aria-label="CineNeon - Trang chủ">
            <span class="brand-mark"><span class="material-symbols-rounded">play_arrow</span></span>
            <span>Cine<span>Neon</span></span>
        </a>
        <nav class="desktop-nav" aria-label="Điều hướng chính">
            <a class="<?= in_array($page, ['home', 'movie'], true) ? 'active' : '' ?>" href="index.php?page=home#movies">Phim</a>
            <a href="index.php?page=home#schedule">Lịch chiếu</a>
            <a href="index.php?page=home#cinemas">Rạp & giá vé</a>
            <a href="index.php?page=home#offers">Ưu đãi</a>
            <a class="<?= $page === 'contact' ? 'active' : '' ?>" href="index.php?page=contact">Liên hệ</a>
        </nav>
        <button class="header-search" type="button" data-search-open aria-label="Mở tìm kiếm">
            <span class="material-symbols-rounded">search</span>
            <span>Tìm phim...</span>
            <kbd>⌘ K</kbd>
        </button>
        <div class="cinema-picker-wrap">
            <button class="cinema-picker" type="button" data-cinema-toggle aria-expanded="false">
                <span class="material-symbols-rounded">location_on</span>
                <span data-selected-cinema>Landmark 81</span>
                <span class="material-symbols-rounded">expand_more</span>
            </button>
            <?php if (!empty($cinemas)): ?><div class="cinema-popover" data-cinema-popover><?php foreach ($cinemas as $cinema): ?><button type="button" data-cinema-name="<?= h($cinema['name']) ?>" data-cinema-address="<?= h($cinema['address']) ?>"><span class="material-symbols-rounded">location_on</span><span><strong><?= h($cinema['name']) ?></strong><small><?= h($cinema['address']) ?></small></span></button><?php endforeach; ?></div><?php endif; ?>
        </div>
        <?php if (!empty($_SESSION['user'])): ?>
            <div class="profile-menu">
                <button class="avatar" type="button" aria-label="Mở tài khoản" data-profile-toggle><?= h(mb_substr($_SESSION['user']['name'], 0, 1)) ?></button>
                <div class="profile-popover" data-profile-popover>
                    <strong><?= h($_SESSION['user']['name']) ?></strong>
                    <small><?= h($_SESSION['user']['email']) ?></small>
                    <a href="index.php?page=ticket"><span class="material-symbols-rounded">confirmation_number</span> Vé của tôi</a>
                    <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?><a href="index.php?page=admin"><span class="material-symbols-rounded">dashboard</span> Trang quản trị</a><?php endif; ?>
                    <a class="danger" href="index.php?logout=1"><span class="material-symbols-rounded">logout</span> Đăng xuất</a>
                </div>
            </div>
        <?php else: ?>
            <a class="login-link" href="index.php?page=login">Đăng nhập</a>
        <?php endif; ?>
        <button class="mobile-menu-button" type="button" data-menu-toggle aria-label="Mở menu"><span class="material-symbols-rounded">menu</span></button>
    </div>
    <nav class="mobile-nav" data-mobile-nav aria-label="Điều hướng di động">
        <a href="index.php?page=home#movies">Phim đang chiếu</a>
        <a href="index.php?page=home#schedule">Lịch chiếu</a>
        <a href="index.php?page=home#cinemas">Rạp & giá vé</a>
        <a href="index.php?page=contact">Liên hệ</a>
        <a href="index.php?page=login">Đăng nhập</a>
    </nav>
</header>
<?php endif; ?>
<?php if (!$databaseOnline): ?>
<div class="system-banner"><span class="material-symbols-rounded">database</span><span>Đang dùng dữ liệu giao diện mẫu. <a href="install.php">Cài đặt MySQL</a> để lưu và quản lý dữ liệu.</span></div>
<?php endif; ?>
<?php if ($flash): ?><div class="sr-only" data-server-flash data-type="<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>
<div class="search-dialog" data-search-dialog aria-hidden="true">
    <button class="search-backdrop" type="button" data-search-close aria-label="Đóng tìm kiếm"></button>
    <div class="search-panel" role="dialog" aria-modal="true" aria-label="Tìm kiếm phim">
        <span class="material-symbols-rounded">search</span>
        <input type="search" placeholder="Tên phim, diễn viên, đạo diễn..." data-search-input>
        <button type="button" data-search-close>ESC</button>
        <div class="search-results" data-search-results></div>
    </div>
</div>
<main id="main-content">
