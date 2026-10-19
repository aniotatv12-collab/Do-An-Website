</main>
<?php if (!$isAdmin): ?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <a class="brand" href="index.php?page=home"><span class="brand-mark"><span class="material-symbols-rounded">play_arrow</span></span><span>Cine<span>Neon</span></span></a>
            <p>Đặt vé nhanh, chọn ghế trực quan và tận hưởng điện ảnh theo cách của bạn.</p>
        </div>
        <div><strong>Khám phá</strong><a href="#movies">Phim đang chiếu</a><a href="#schedule">Lịch chiếu</a><a href="#offers">Khuyến mãi</a></div>
        <div><strong>Hỗ trợ</strong><a href="#">Điều khoản</a><a href="#">Chính sách hoàn vé</a><a href="index.php?page=contact">Liên hệ</a></div>
        <div class="footer-contact"><strong>CineNeon Landmark 81</strong><span><span class="material-symbols-rounded">call</span> 1900 8888</span><span><span class="material-symbols-rounded">mail</span> hello@cineneon.vn</span></div>
    </div>
    <div class="container footer-bottom"><span>© 2026 CineNeon.</span><span>Thiết kế cho trải nghiệm điện ảnh hiện đại.</span></div>
</footer>
<nav class="bottom-nav" aria-label="Điều hướng nhanh">
    <a class="<?= $page === 'home' ? 'active' : '' ?>" href="index.php?page=home"><span class="material-symbols-rounded">home</span>Trang chủ</a>
    <a href="index.php?page=home#movies"><span class="material-symbols-rounded">movie</span>Phim</a>
    <a href="index.php?page=ticket"><span class="material-symbols-rounded">confirmation_number</span>Vé</a>
    <a class="<?= $page === 'contact' ? 'active' : '' ?>" href="index.php?page=contact"><span class="material-symbols-rounded">support_agent</span>Liên hệ</a>
</nav>
<?php endif; ?>
<div class="toast" data-toast role="status" aria-live="polite"></div>
<script>
window.CINENEON_MOVIES = <?= json_encode(array_map(static fn($item) => ['id' => $item['id'], 'title' => $item['title'], 'subtitle' => $item['subtitle'], 'poster' => $item['poster'], 'backdrop' => $item['backdrop'], 'age' => $item['age'], 'duration' => $item['duration'], 'rating' => $item['rating'], 'synopsis' => $item['synopsis']], $movies), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="assets/js/app.js?v=20261013-2"></script>
</body>
</html>
