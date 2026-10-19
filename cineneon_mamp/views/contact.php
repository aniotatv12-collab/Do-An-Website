<?php
$contactName = (string) ($_SESSION['user']['name'] ?? '');
$contactEmail = (string) ($_SESSION['user']['email'] ?? '');
?>
<section class="contact-hero">
    <div class="container contact-hero-inner">
        <div>
            <span class="kicker">CineNeon luôn lắng nghe</span>
            <h1>Chúng tôi có thể<br>hỗ trợ gì cho bạn?</h1>
            <p>Gửi câu hỏi về vé, suất chiếu, hoàn tiền hoặc trải nghiệm tại rạp. Mọi lời nhắn được lưu vào hệ thống MySQL để đội ngũ hỗ trợ theo dõi.</p>
        </div>
        <div class="contact-hero-badge" aria-hidden="true">
            <span class="material-symbols-rounded">support_agent</span>
            <strong>Phản hồi nhanh</strong>
            <small>Trong giờ làm việc</small>
        </div>
    </div>
</section>

<section class="section container contact-layout">
    <div class="contact-details">
        <div class="section-heading compact"><div><span class="kicker">Kênh hỗ trợ</span><h2>Liên hệ CineNeon</h2><p>Chọn kênh thuận tiện nhất hoặc gửi biểu mẫu bên cạnh.</p></div></div>
        <div class="contact-card-grid">
            <article><span class="material-symbols-rounded">call</span><div><small>Hotline</small><strong>1900 8888</strong><p>08:00–22:30 mỗi ngày</p></div></article>
            <article><span class="material-symbols-rounded">mail</span><div><small>Email</small><strong>hello@cineneon.vn</strong><p>Phản hồi trong vòng 24 giờ</p></div></article>
            <article><span class="material-symbols-rounded">location_on</span><div><small>Rạp chính</small><strong>CineNeon Landmark 81</strong><p>720A Điện Biên Phủ, Bình Thạnh, TP.HCM</p></div></article>
        </div>
        <div class="contact-note">
            <span class="material-symbols-rounded">verified_user</span>
            <div><strong>Bảo vệ thông tin khách hàng</strong><p>CineNeon chỉ sử dụng thông tin bạn cung cấp để xử lý yêu cầu hỗ trợ.</p></div>
        </div>
    </div>

    <form class="contact-form-card" method="post">
        <input type="hidden" name="_action" value="contact">
        <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
        <header><span class="kicker">Gửi lời nhắn</span><h2>Thông tin liên hệ</h2><p>Các trường có dấu * là bắt buộc.</p></header>
        <div class="form-grid">
            <label>Họ và tên *<input required name="name" maxlength="120" value="<?= h($contactName) ?>" placeholder="Nguyễn Văn An"></label>
            <label>Email *<input required name="email" type="email" maxlength="190" value="<?= h($contactEmail) ?>" placeholder="ban@email.com"></label>
            <label class="full">Số điện thoại<input name="phone" type="tel" maxlength="20" placeholder="09xx xxx xxx"></label>
            <label class="full">Tiêu đề *<input required name="subject" minlength="3" maxlength="160" placeholder="Ví dụ: Cần hỗ trợ đổi suất chiếu"></label>
            <label class="full">Nội dung *<textarea required name="message" minlength="10" maxlength="3000" rows="7" placeholder="Mô tả chi tiết vấn đề bạn đang gặp..."></textarea></label>
        </div>
        <button class="button primary contact-submit" type="submit"><span class="material-symbols-rounded">send</span>Gửi yêu cầu hỗ trợ</button>
    </form>
</section>

<section class="section contact-faq">
    <div class="container">
        <div class="section-heading"><div><span class="kicker">Câu hỏi thường gặp</span><h2>Giải đáp nhanh</h2></div></div>
        <div class="faq-grid">
            <article><span>01</span><h3>Tôi có thể đổi ghế sau khi thanh toán không?</h3><p>Vui lòng liên hệ hotline trước giờ chiếu tối thiểu 60 phút để nhân viên kiểm tra tình trạng ghế.</p></article>
            <article><span>02</span><h3>Khi nào tôi nhận được vé điện tử?</h3><p>Vé hiển thị ngay sau khi giao dịch thành công và có thể mở lại từ tài khoản của bạn.</p></article>
            <article><span>03</span><h3>Thanh toán thất bại nhưng đã trừ tiền?</h3><p>Hãy gửi mã giao dịch và email đặt vé. CineNeon sẽ kiểm tra, đối soát với đơn vị thanh toán.</p></article>
        </div>
    </div>
</section>
