<section class="movie-detail-hero" style="--detail-image: url('<?= h($movie['backdrop']) ?>')">
    <div class="detail-overlay"></div>
    <div class="container detail-grid">
        <div class="detail-poster"><img src="<?= h($movie['poster']) ?>" alt="Poster <?= h($movie['title']) ?>"><button type="button" data-toast-message="Trailer sẽ được mở trong phiên bản hoàn chỉnh."><span class="material-symbols-rounded fill">play_circle</span> Xem trailer</button></div>
        <div class="detail-content">
            <div class="eyebrow"><span></span> Siêu phẩm độc quyền</div>
            <h1><?= h($movie['title']) ?></h1>
            <p class="subtitle"><?= h($movie['subtitle']) ?> · 2026</p>
            <div class="detail-tags"><span><?= h($movie['format']) ?></span><span>Dolby Atmos</span><span><?= $movie['duration'] ?> phút</span><span><?= h($movie['language']) ?></span></div>
            <div class="critic-card"><div class="critic-score"><strong>9.4</strong><span>/10</span></div><div><span class="kicker">CineNeon Critics</span><strong>Xuất sắc đột phá</strong><small>Được 18.420 khán giả bình chọn</small></div><div class="audience-score"><span class="material-symbols-rounded fill">star</span><strong><?= $movie['rating'] ?></strong><small>95% khuyên xem</small></div></div>
            <div class="synopsis"><h2><span class="material-symbols-rounded">movie</span>Nội dung phim</h2><p><?= h(($movie['synopsis'] ?? '') ?: 'Thông tin nội dung phim đang được cập nhật.') ?></p></div>
            <div class="cast-row"><span><i>PK</i><small>Đạo diễn</small><strong>Paul Kael</strong></span><span><i>AV</i><small>Vai chính</small><strong>Aelita Vance</strong></span><span><i>MD</i><small>Phi hành gia</small><strong>Marcus Drake</strong></span></div>
            <div class="detail-actions"><a class="button primary wide" href="#showtimes"><span class="material-symbols-rounded">confirmation_number</span>Đặt vé ngay</a><button class="button secondary" data-toast-message="Đã thêm phim vào danh sách yêu thích."><span class="material-symbols-rounded">favorite</span><span class="desktop-only">Yêu thích</span></button></div>
        </div>
    </div>
</section>

<section class="section section-raised" id="showtimes">
    <div class="container">
        <div class="section-heading"><div><span class="kicker">Lịch chiếu trực quan</span><h2>Chọn ngày & rạp</h2><p>Giá hiển thị đã bao gồm thuế.</p></div><span class="availability"><i></i> Còn nhiều ghế</span></div>
        <div class="date-tabs" data-tabs>
            <button class="active"><small>Hôm nay</small><strong>12/10</strong><span>Thứ Hai</span></button>
            <button><small>Ngày mai</small><strong>13/10</strong><span>Thứ Ba</span></button>
            <button><small>Thứ Tư</small><strong>14/10</strong><span>Thứ Tư</span></button>
            <button><small>Thứ Năm</small><strong>15/10</strong><span>Thứ Năm</span></button>
            <button><small>Thứ Sáu</small><strong>16/10</strong><span>Thứ Sáu</span></button>
        </div>
        <?php foreach (['CineNeon Landmark 81' => 'Tầng B1, Vinhomes Landmark 81, Bình Thạnh', 'CineNeon Thủ Thiêm Cyber' => 'Thiso Mall Sala, TP. Thủ Đức'] as $cinema => $address): ?>
            <article class="cinema-card">
                <header><span class="cinema-icon"><span class="material-symbols-rounded">local_activity</span></span><div><h3><?= h($cinema) ?></h3><p><?= h($address) ?></p></div><span class="distance">Cách bạn 2,4 km</span></header>
                <div class="room-grid">
                    <div><h4><span class="green-dot"></span>Phòng IMAX Laser 3D <small>Phụ đề · 3D</small></h4><div class="time-list large"><?php foreach ($movie['times'] as $time): ?><a href="index.php?page=seats&movie=<?= $movie['id'] ?>&time=<?= urlencode($time) ?><?= isset($movie['showtime_ids'][$time]) ? '&showtime=' . (int) $movie['showtime_ids'][$time] : '' ?>"><?= h($time) ?></a><?php endforeach; ?></div></div>
                    <div><h4><span class="gold-dot"></span>Phòng Dolby Atmos 2D <small>Phụ đề Việt</small></h4><div class="time-list large"><a href="index.php?page=seats&movie=<?= $movie['id'] ?>&time=17:15">17:15</a><a href="index.php?page=seats&movie=<?= $movie['id'] ?>&time=20:15">20:15</a><a href="index.php?page=seats&movie=<?= $movie['id'] ?>&time=22:45">22:45</a></div></div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="section container reviews-section">
    <div class="section-heading"><div><span class="kicker">Cộng đồng khán giả</span><h2>Đánh giá & bình luận</h2></div></div>
    <div class="review-summary"><div class="rating-big"><strong>4.8</strong><span>★★★★★</span><small>Từ 18.420 đánh giá</small></div><div class="rating-bars"><span>5 sao <i><b style="width:82%"></b></i>82%</span><span>4 sao <i><b style="width:46%"></b></i>12%</span><span>3 sao <i><b style="width:24%"></b></i>4%</span></div><div class="write-review"><h3>Bạn đã xem phim chưa?</h3><p>Chia sẻ cảm nhận với cộng đồng CineNeon.</p><div class="stars">★★★★★</div><button class="button primary" data-toast-message="Cảm ơn bạn! Tính năng đánh giá sẽ mở sau khi kết nối tài khoản.">Viết đánh giá</button></div></div>
</section>
