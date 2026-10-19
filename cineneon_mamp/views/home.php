<?php if (isset($_GET['welcome'])): ?>
<div class="sr-only" data-welcome>Đăng nhập thành công</div>
<?php endif; ?>

<section class="hero-wrap">
    <div class="container hero-grid">
        <article class="hero-card" data-hero-card style="--hero-image: url('<?= h($featured['backdrop']) ?>')">
            <div class="hero-content">
                <div class="eyebrow"><span></span> Phim nổi bật tuần này</div>
                <div class="meta-row">
                    <span class="age-badge age-16" data-hero-age><?= h($featured['age']) ?></span>
                    <span><span class="material-symbols-rounded">schedule</span><span data-hero-duration><?= $featured['duration'] ?> phút</span></span>
                    <span class="rating"><span class="material-symbols-rounded fill">star</span><span data-hero-rating><?= $featured['rating'] ?></span></span>
                </div>
                <h1 data-hero-title><?= h($featured['title']) ?></h1>
                <p data-hero-description><?= h(($featured['synopsis'] ?? '') ?: 'Khám phá những suất chiếu nổi bật và đặt ghế yêu thích của bạn ngay hôm nay.') ?></p>
                <div class="hero-actions">
                    <a class="button primary" data-hero-link href="index.php?page=movie&movie=<?= $featured['id'] ?>"><span class="material-symbols-rounded">confirmation_number</span>Đặt vé ngay</a>
                    <button class="button secondary" type="button" data-toast-message="Trailer sẽ được mở trong phiên bản kết nối dữ liệu."><span class="material-symbols-rounded">play_circle</span>Xem trailer</button>
                </div>
                <div class="hero-dots" aria-label="Chọn banner"><?php foreach ($movies as $index => $item): ?><button type="button" class="<?= $index === 0 ? 'active' : '' ?>" data-hero-dot aria-label="Banner <?= $index + 1 ?>"></button><?php endforeach; ?></div>
            </div>
        </article>

        <aside class="ranking-card">
            <div class="section-heading compact"><div><span class="kicker gold">Dẫn đầu phòng vé</span><h2>Top phim tuần</h2></div><a href="#movies">Xem tất cả</a></div>
            <div class="ranking-list">
                <?php foreach ($movies as $index => $item): ?>
                    <a href="index.php?page=movie&movie=<?= $item['id'] ?>" class="ranking-item">
                        <span class="rank <?= $index < 3 ? 'top' : '' ?>"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <img src="<?= h($item['poster']) ?>" alt="Poster <?= h($item['title']) ?>">
                        <span class="ranking-info"><strong><?= h($item['title']) ?></strong><small><?= $item['hot'] ?>% lấp đầy</small></span>
                        <span class="material-symbols-rounded">chevron_right</span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="live-note"><i></i> Cập nhật mỗi 15 phút</div>
        </aside>
    </div>
</section>

<section class="filter-shell" aria-label="Bộ lọc phim">
    <div class="container filter-bar">
        <div class="segmented" data-filter-group="format">
            <button class="active" type="button" data-value="all">Tất cả</button>
            <button type="button" data-value="2d">2D</button>
            <button type="button" data-value="3d">3D</button>
            <button type="button" data-value="imax">IMAX</button>
        </div>
        <div class="filter-divider"></div>
        <div class="filter-chips" data-filter-group="seat">
            <button class="active" type="button">Mọi loại ghế</button>
            <button type="button">Ghế VIP</button>
            <button type="button">Sweetbox</button>
        </div>
        <label class="select-control"><span class="material-symbols-rounded">category</span><select data-genre-filter><option value="all">Mọi thể loại</option><option value="Hành động">Hành động</option><option value="Kinh dị">Kinh dị</option><option value="Hoạt hình">Hoạt hình</option></select></label>
        <label class="select-control"><span class="material-symbols-rounded">sort</span><select data-sort-filter><option value="hot">Độ hot</option><option value="rating">Điểm đánh giá</option><option value="duration">Thời lượng</option></select></label>
    </div>
</section>

<section class="section container" id="movies">
    <div class="section-heading">
        <div><span class="kicker">Đang mở bán</span><h2>Phim đang chiếu</h2><p>Tìm suất chiếu phù hợp chỉ trong vài giây.</p></div>
        <div class="age-filter" data-filter-group="age"><span>Độ tuổi</span><button class="active" data-value="all">ALL</button><button data-value="P">P</button><button data-value="K">K</button><button data-value="T16">T16</button><button data-value="T18">T18</button></div>
    </div>
    <div class="movie-grid" data-movie-grid>
        <?php foreach ($movies as $item): ?>
            <article class="movie-card" data-format="<?= strtolower(h($item['format'])) ?>" data-genre="<?= h($item['genre']) ?>" data-age="<?= h($item['age']) ?>" data-hot="<?= $item['hot'] ?>" data-rating="<?= $item['rating'] ?>" data-duration="<?= $item['duration'] ?>">
                <a class="poster-wrap" href="index.php?page=movie&movie=<?= $item['id'] ?>">
                    <img src="<?= h($item['poster']) ?>" alt="Poster <?= h($item['title']) ?>" loading="lazy">
                    <span class="format-badge"><?= h($item['format']) ?></span>
                    <span class="age-badge <?= $item['age'] === 'T18' ? 'age-18' : 'age-16' ?>"><?= h($item['age']) ?></span>
                    <span class="poster-play"><span class="material-symbols-rounded">play_arrow</span></span>
                </a>
                <div class="movie-card-body">
                    <div class="movie-title-row"><div><h3><?= h($item['title']) ?></h3><p><?= h($item['subtitle']) ?></p></div><span class="rating"><span class="material-symbols-rounded fill">star</span><?= $item['rating'] ?></span></div>
                    <div class="movie-meta"><span><?= h(explode(',', $item['genre'])[0]) ?></span><i></i><span><?= $item['duration'] ?>'</span><i></i><span><?= h($item['language']) ?></span></div>
                    <span class="showtime-label">Suất chiếu hôm nay</span>
                    <div class="time-list">
                        <?php foreach ($item['times'] as $timeIndex => $time): ?>
                            <a class="<?= $timeIndex === 1 ? 'featured' : '' ?>" href="index.php?page=seats&movie=<?= $item['id'] ?>&time=<?= urlencode($time) ?><?= isset($item['showtime_ids'][$time]) ? '&showtime=' . (int) $item['showtime_ids'][$time] : '' ?>"><?= h($time) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="empty-state" data-empty-state hidden><span class="material-symbols-rounded">movie_off</span><h3>Chưa có phim phù hợp</h3><p>Hãy thử thay đổi bộ lọc để xem thêm phim.</p></div>
</section>

<section class="section container" id="schedule">
    <div class="section-heading"><div><span class="kicker">Đặt vé nhanh</span><h2>Chọn lịch theo rạp</h2></div></div>
    <div class="quick-booking" data-quick-booking>
        <div class="quick-step"><span>1</span><label>Rạp chiếu<select name="quick_cinema"><?php foreach ($cinemas as $cinema): ?><option value="<?= (int) $cinema['id'] ?>"><?= h($cinema['name']) ?></option><?php endforeach; ?></select></label></div>
        <div class="quick-step"><span>2</span><label>Ngày xem<select><option>Hôm nay, 12/10</option><option>Ngày mai, 13/10</option></select></label></div>
        <div class="quick-step"><span>3</span><label>Phim<select name="quick_movie"><?php foreach ($movies as $item): ?><option value="<?= (int) $item['id'] ?>"><?= h($item['title']) ?></option><?php endforeach; ?></select></label></div>
        <a class="button primary" data-quick-booking-submit href="index.php?page=movie&movie=<?= $featured['id'] ?>#showtimes">Xem suất chiếu <span class="material-symbols-rounded">arrow_forward</span></a>
    </div>
</section>

<section class="benefits" id="cinemas">
    <div class="container benefit-grid">
        <div><span class="material-symbols-rounded">video_camera_front</span><strong>IMAX Laser</strong><small>Màn chiếu 4K thế hệ mới</small></div>
        <div><span class="material-symbols-rounded">graphic_eq</span><strong>Dolby Atmos</strong><small>Âm thanh 128 kênh</small></div>
        <div><span class="material-symbols-rounded">event_seat</span><strong>Ghế VIP & Sweetbox</strong><small>Thoải mái trong mọi suất chiếu</small></div>
        <div><span class="material-symbols-rounded">fastfood</span><strong>Combo tại ghế</strong><small>Đặt trước, nhận nhanh</small></div>
    </div>
</section>

<section class="section container" id="offers">
    <div class="offer-banner"><div><span class="kicker">Ưu đãi thành viên</span><h2>Thứ Hai rực rỡ — vé chỉ từ 79K</h2><p>Áp dụng cho thành viên CinePass tại tất cả cụm rạp CineNeon.</p></div><a class="button secondary" href="index.php?page=register">Tham gia CinePass</a></div>
</section>
