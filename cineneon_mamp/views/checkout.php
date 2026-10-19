<section class="checkout-section container">
    <a class="back-link" href="index.php?page=seats&movie=<?= $movie['id'] ?>&time=<?= urlencode($selectedTime) ?>&showtime=<?= $showtimeId ?>"><span class="material-symbols-rounded">arrow_back</span>Quay lại chọn ghế</a>
    <div class="checkout-heading"><div><span class="kicker">Bước cuối cùng</span><h1>Thanh toán an toàn</h1><p>Kiểm tra thông tin trước khi hoàn tất đơn vé.</p></div><div class="checkout-progress"><span class="done">1<small>Chọn phim</small></span><i></i><span class="done">2<small>Chọn ghế</small></span><i></i><span class="active">3<small>Thanh toán</small></span></div></div>
    <div class="checkout-grid">
        <form class="checkout-form" method="post" data-checkout-form data-database-online="<?= $databaseOnline ? '1' : '0' ?>">
            <input type="hidden" name="_action" value="checkout">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
            <input type="hidden" name="movie_id" value="<?= $movie['id'] ?>">
            <input type="hidden" name="showtime_id" value="<?= $showtimeId ?>">
            <input type="hidden" name="show_time" value="<?= h($selectedTime) ?>">
            <input type="hidden" name="seats_json" value="[]" data-seats-json>
            <section class="form-card"><div class="form-card-title"><span class="number-badge">1</span><div><h2>Thông tin nhận vé</h2><p>Vé điện tử sẽ được gửi tới email này.</p></div></div><div class="form-grid"><label>Họ và tên<input required name="name" autocomplete="name" placeholder="Nguyễn Minh Anh"></label><label>Số điện thoại<input required name="phone" autocomplete="tel" inputmode="tel" placeholder="090 123 4567"></label><label class="full">Email<input required name="email" type="email" autocomplete="email" placeholder="minhanh@example.com"></label></div></section>
            <section class="form-card"><div class="form-card-title"><span class="number-badge">2</span><div><h2>Phương thức thanh toán</h2><p>Chọn phương thức bạn muốn sử dụng.</p></div></div><div class="payment-options">
                <label class="payment-option active"><input type="radio" name="payment" value="card" checked><span class="payment-icon green"><span class="material-symbols-rounded">credit_card</span></span><span><strong>Thẻ ngân hàng</strong><small>Visa, Mastercard, NAPAS</small></span><i></i></label>
                <label class="payment-option"><input type="radio" name="payment" value="momo"><span class="payment-icon pink">M</span><span><strong>Ví MoMo</strong><small>Quét QR trong ứng dụng</small></span><i></i></label>
                <label class="payment-option"><input type="radio" name="payment" value="zalopay"><span class="payment-icon blue">Z</span><span><strong>ZaloPay</strong><small>Thanh toán nhanh</small></span><i></i></label>
                <label class="payment-option"><input type="radio" name="payment" value="qr"><span class="payment-icon gold"><span class="material-symbols-rounded">qr_code_2</span></span><span><strong>VietQR</strong><small>Ứng dụng ngân hàng</small></span><i></i></label>
            </div><div class="card-fields" data-card-fields><label>Số thẻ<input inputmode="numeric" placeholder="1234 5678 9012 3456"></label><div class="form-grid"><label>Ngày hết hạn<input placeholder="MM/YY"></label><label>CVV<input inputmode="numeric" placeholder="•••"></label></div></div></section>
            <label class="terms-check"><input type="checkbox" required><span>Tôi đồng ý với <a href="#">điều khoản đặt vé</a> và chính sách bảo mật của CineNeon.</span></label>
            <button class="button primary checkout-submit" type="submit"><span class="material-symbols-rounded">lock</span>Thanh toán <span data-checkout-total>0đ</span></button>
        </form>
        <aside class="order-card">
            <h2>Đơn hàng của bạn</h2>
            <div class="order-movie"><img src="<?= h($movie['poster']) ?>" alt=""><div><span class="age-badge age-16"><?= h($movie['age']) ?></span><strong><?= h($movie['title']) ?></strong><small><?= h($movie['format']) ?> · <?= h($movie['language']) ?></small></div></div>
            <div class="order-info"><span><small>Rạp</small><strong><?= h($showtime['cinema_name'] ?? 'Chưa chọn') ?></strong></span><span><small>Suất chiếu</small><strong><?= h($selectedTime) ?> · <?= isset($showtime['start_time']) ? date('d/m', strtotime($showtime['start_time'])) : date('d/m') ?></strong></span><span><small>Phòng</small><strong><?= h($showtime['room_name'] ?? 'Chưa chọn') ?></strong></span><span><small>Ghế</small><strong data-checkout-seats>Chưa chọn</strong></span></div>
            <div class="promo-row"><input placeholder="Mã khuyến mãi"><button type="button" data-toast-message="Mã ưu đãi sẽ được kiểm tra khi kết nối cơ sở dữ liệu.">Áp dụng</button></div>
            <div class="order-prices"><span><label>Tiền vé</label><strong data-order-subtotal>0đ</strong></span><span><label>Phí dịch vụ</label><strong>0đ</strong></span><span class="order-total"><label>Tổng cộng</label><strong data-order-total>0đ</strong></span></div>
            <p><span class="material-symbols-rounded">verified_user</span>Giao dịch được mã hóa và bảo vệ.</p>
        </aside>
    </div>
</section>
