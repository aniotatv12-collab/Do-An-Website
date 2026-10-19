<?php $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']; $basePrice = (int) ($showtime['base_price'] ?? 120000); ?>
<section class="booking-topbar">
    <div class="container"><a class="icon-button" href="index.php?page=movie&movie=<?= $movie['id'] ?>"><span class="material-symbols-rounded">arrow_back</span></a><span class="format-badge"><?= h($movie['format']) ?></span><strong><?= h($movie['title']) ?></strong><span class="booking-room"><?= h(($showtime['room_name'] ?? 'Phòng 04') . ' · ' . ($showtime['cinema_name'] ?? 'Landmark 81')) ?></span><div class="booking-timer"><span class="material-symbols-rounded">timer</span>Giữ ghế <strong data-countdown>05:00</strong></div></div>
</section>

<section class="booking-layout container">
    <div class="seat-panel">
        <header class="seat-header"><div><span class="kicker">Phòng chiếu kỹ thuật số</span><h1><?= h($showtime['room_name'] ?? 'Phòng 04') ?> — <?= h($movie['format']) ?></h1></div><div class="showtime-box"><span class="material-symbols-rounded">calendar_month</span><small>Suất chiếu</small><strong><?= h($selectedTime) ?> · <?= isset($showtime['start_time']) ? date('d/m', strtotime($showtime['start_time'])) : 'Hôm nay' ?></strong></div></header>
        <div class="screen"><span>Màn hình chính</span></div>
        <div class="seat-scroll" tabindex="0" aria-label="Sơ đồ ghế có thể cuộn ngang">
            <div class="seat-map" data-seat-map>
                <?php foreach ($rows as $rowIndex => $row): ?>
                    <div class="seat-row">
                        <span class="row-label"><?= $row ?></span>
                        <?php for ($number = 1; $number <= 16; $number++):
                            $seatId = $row . $number;
                            $isSold = in_array($seatId, $soldSeats, true);
                            $isVip = $rowIndex >= 4;
                            $gap = in_array($number, [5, 13], true);
                        ?>
                            <?php if ($gap): ?><i class="aisle"></i><?php endif; ?>
                            <button class="seat <?= $isVip ? 'vip' : 'standard' ?> <?= $isSold ? 'sold' : '' ?>" type="button" data-seat="<?= $seatId ?>" data-price="<?= $basePrice + ($isVip ? 10000 : 0) ?>" <?= $isSold ? 'disabled' : '' ?> aria-label="Ghế <?= $seatId ?><?= $isSold ? ' đã bán' : '' ?>"><?= str_pad((string) $number, 2, '0', STR_PAD_LEFT) ?></button>
                        <?php endfor; ?>
                        <span class="row-label"><?= $row ?></span>
                    </div>
                <?php endforeach; ?>
                <div class="seat-row sweet-row"><span class="row-label">J</span><i class="sweet-spacer"></i><?php foreach (['J1–2', 'J3–4', 'J5–6', 'J7–8', 'J9–10', 'J11–12'] as $pair): $pairSold = in_array($pair, $soldSeats, true); ?><button class="seat sweetbox <?= $pairSold ? 'sold' : '' ?>" type="button" data-seat="<?= h($pair) ?>" data-price="<?= $basePrice + 180000 ?>" <?= $pairSold ? 'disabled' : '' ?>><span class="material-symbols-rounded">favorite</span><?= h($pair) ?></button><?php endforeach; ?><span class="row-label">J</span></div>
            </div>
        </div>
        <div class="seat-legend"><span><i class="seat-demo standard"></i>Tiêu chuẩn · <?= number_format($basePrice / 1000) ?>K</span><span><i class="seat-demo vip"></i>VIP · <?= number_format(($basePrice + 10000) / 1000) ?>K</span><span><i class="seat-demo sweetbox"></i>Sweetbox đôi · <?= number_format(($basePrice + 180000) / 1000) ?>K</span><span><i class="seat-demo selected"></i>Đang chọn</span><span><i class="seat-demo sold"></i>Đã bán</span></div>
        <div class="seat-notes"><span><span class="material-symbols-rounded">graphic_eq</span>Dolby Atmos 64 channels</span><span><span class="material-symbols-rounded">videocam</span>Dual 4K Laser</span><span><span class="material-symbols-rounded">door_open</span>Lối thoát hai bên</span></div>
    </div>

    <aside class="booking-summary">
        <div class="summary-movie"><img src="<?= h($movie['poster']) ?>" alt=""><div><span class="age-badge age-16"><?= h($movie['age']) ?></span><h2><?= h($movie['title']) ?></h2><p><?= h($movie['format']) ?> · <?= h($movie['language']) ?></p></div></div>
        <dl class="summary-details"><div><dt><span class="material-symbols-rounded">schedule</span>Suất chiếu</dt><dd><?= h($selectedTime) ?> · <?= isset($showtime['start_time']) ? date('d/m', strtotime($showtime['start_time'])) : 'Hôm nay' ?></dd></div><div><dt><span class="material-symbols-rounded">location_on</span>Cụm rạp</dt><dd><?= h($showtime['cinema_name'] ?? 'Landmark 81') ?></dd></div><div><dt><span class="material-symbols-rounded">meeting_room</span>Phòng</dt><dd><?= h($showtime['room_name'] ?? 'Rạp 04') ?></dd></div></dl>
        <div class="selected-seat-block"><div><span>Ghế đang chọn</span><button type="button" data-clear-seats>Xóa chọn</button></div><div class="selected-chips" data-selected-chips><span class="muted">Chưa chọn ghế</span></div></div>
        <div class="price-lines"><span><label>Tiền vé</label><strong data-seat-subtotal>0đ</strong></span><span><label>Phí đặt vé</label><strong>0đ</strong></span></div>
        <div class="total-box"><span>Tổng thanh toán<small>Đã bao gồm VAT</small></span><strong data-seat-total>0<small>VNĐ</small></strong></div>
        <a class="button primary wide disabled" href="index.php?page=checkout&movie=<?= $movie['id'] ?>&time=<?= urlencode($selectedTime) ?>&showtime=<?= $showtimeId ?>" data-continue-booking>Tiếp tục <span class="material-symbols-rounded">arrow_forward</span></a>
        <p class="secure-note"><span class="material-symbols-rounded">shield_lock</span>Ghế chỉ được giữ sau khi bạn tiếp tục.</p>
    </aside>
</section>
