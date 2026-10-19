<?php
$ticketTitle = $order['title'] ?? $movie['title'];
$ticketSubtitle = $order['original_title'] ?? $movie['subtitle'];
$ticketBackdrop = $order['backdrop_url'] ?? $movie['backdrop'];
$ticketFormat = $order['format'] ?? $movie['format'];
$ticketAge = $order['age_rating'] ?? $movie['age'];
$ticketDate = isset($order['start_time']) ? date('d/m/Y', strtotime($order['start_time'])) : '13/10/2026';
$ticketTime = isset($order['start_time']) ? date('H:i', strtotime($order['start_time'])) : '19:45';
$ticketCinema = $order['cinema_name'] ?? 'CineNeon Landmark 81';
$ticketRoom = $order['room_name'] ?? 'Phòng 04 — IMAX';
$ticketSeats = $order['seat_codes'] ?? 'F08, F09';
$ticketCode = $order['order_code'] ?? 'CN-261013-4821';
$ticketPaid = !$order || ($order['status'] ?? '') === 'paid';
$ticketStatusLabel = $ticketPaid ? 'Thanh toán thành công' : 'Đang chờ MoMo xác nhận';
$ticketHeading = $ticketPaid ? 'Hẹn gặp bạn tại rạp!' : 'Đang xác nhận thanh toán';
$ticketDescription = $ticketPaid
    ? 'Vé đã được gửi tới email của bạn. Hãy xuất trình mã QR tại cửa phòng chiếu.'
    : 'MoMo đang xử lý giao dịch. Vui lòng tải lại trang sau vài giây để xem trạng thái cuối cùng.';
?>
<section class="ticket-page container">
    <div class="success-mark"><span class="material-symbols-rounded">check</span></div>
    <span class="kicker"><?= h($ticketStatusLabel) ?></span>
    <h1><?= h($ticketHeading) ?></h1>
    <p><?= h($ticketDescription) ?></p>
    <article class="e-ticket">
        <div class="ticket-visual" style="--ticket-image:url('<?= h($ticketBackdrop) ?>')"><span class="format-badge"><?= h($ticketFormat) ?></span><div><span class="age-badge age-16"><?= h($ticketAge) ?></span><h2><?= h($ticketTitle) ?></h2><p><?= h($ticketSubtitle) ?></p></div></div>
        <div class="ticket-data"><div class="ticket-grid"><span><small>Ngày</small><strong><?= h($ticketDate) ?></strong></span><span><small>Giờ</small><strong><?= h($ticketTime) ?></strong></span><span><small>Rạp</small><strong><?= h($ticketCinema) ?></strong></span><span><small>Phòng</small><strong><?= h($ticketRoom) ?></strong></span><span class="accent"><small>Ghế</small><strong data-ticket-seats data-server-ticket="<?= $order ? '1' : '0' ?>"><?= h(str_replace(',', ' ·', $ticketSeats)) ?></strong></span><span><small>Mã vé</small><strong><?= h($ticketCode) ?></strong></span></div><div class="ticket-cut"><i></i><i></i></div><div class="qr-code" aria-label="Mã QR vé"><div class="qr-pattern"></div><strong><?= h($ticketCode) ?></strong><small>Đưa mã này cho nhân viên soát vé</small></div></div>
    </article>
    <div class="ticket-actions"><button class="button secondary" type="button" onclick="window.print()"><span class="material-symbols-rounded">download</span>Lưu vé</button><a class="button primary" href="index.php?page=home"><span class="material-symbols-rounded">home</span>Về trang chủ</a></div>
</section>
