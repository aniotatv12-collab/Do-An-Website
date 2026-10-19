# CineNeon — giao diện tối ưu cho MAMP

Đây là hệ thống đặt vé PHP + MySQL được tái cấu trúc từ bộ mẫu Stitch. Bản gốc được giữ nguyên; phiên bản này có luồng đặt vé responsive, đăng nhập/phân quyền và trang quản trị dữ liệu.

## Chạy bằng MAMP

1. Sao chép thư mục `cineneon_mamp` vào thư mục `htdocs` của MAMP.
2. Khởi động Apache và MySQL.
3. Mở `http://localhost:8888/cineneon_mamp/install.php` và nhấn **Tạo database và dữ liệu mẫu**.
4. Mở `http://localhost:8888/cineneon_mamp/`.

Không cần cài thư viện. Font và ảnh mẫu đang được tải từ internet; có thể thay bằng tệp trong `assets/images` khi triển khai thật.

## Các màn hình đã có

- Trang chủ, tìm kiếm và bộ lọc phim.
- Trang liên hệ có biểu mẫu lưu trực tiếp vào MySQL.
- Chi tiết phim và chọn suất chiếu.
- Sơ đồ ghế tương tác, tính tổng tiền và đếm thời gian.
- Thanh toán, vé điện tử.
- Đăng nhập, đăng ký bằng PHP Session và tài khoản MySQL.
- Dashboard quản trị; CRUD phim, thêm suất chiếu/rạp, phân quyền người dùng và cập nhật đơn vé.
- Lưu đơn hàng, ghế và giao dịch thanh toán vào MySQL.

## Chức năng đáp ứng yêu cầu đồ án

- **Thêm:** quản trị viên thêm phim, rạp và suất chiếu; khách gửi liên hệ và tạo đơn vé.
- **Sửa:** quản trị viên chỉnh sửa phim, quyền người dùng và trạng thái đơn vé.
- **Xóa:** phim được xóa mềm (chuyển sang trạng thái ẩn) để không làm mất lịch sử đơn hàng.
- **Cơ sở dữ liệu:** 14 bảng MySQL, gồm người dùng, phim, thể loại, rạp, phòng, ghế, suất chiếu, đơn vé, thanh toán và liên hệ.
- **Các trang chính:** trang chủ, chi tiết phim, chọn ghế, thanh toán, vé điện tử, liên hệ, đăng nhập, đăng ký và quản trị.

## Tài khoản quản trị mẫu

```text
Email: admin@cineneon.vn
Mật khẩu: Admin@123
```

Hãy đổi mật khẩu khi triển khai thật.

## Cấu hình MySQL

Mặc định phù hợp với MAMP trên macOS:

```text
DB_HOST=127.0.0.1
DB_PORT=8889
DB_NAME=cineneon
DB_USER=root
DB_PASSWORD=root
```

Có thể thay các giá trị này bằng biến môi trường. Kết nối PDO nằm tại `config/database.php`; schema nằm tại `database/schema.sql`.

Nếu thích cài từ Terminal thay vì trình duyệt:

```bash
/Applications/MAMP/bin/php/php8.3.30/bin/php scripts/install_database.php
```

Kiểm tra nhanh kết nối MySQL, chức năng thêm–sửa–xóa mềm phim và lưu liên hệ:

```bash
/Applications/MAMP/bin/php/php8.3.30/bin/php tests/smoke.php
```

Thanh toán thẻ, ZaloPay và VietQR hiện vẫn là nhà cung cấp mô phỏng. MoMo đã có luồng Sandbox riêng được mô tả bên dưới; khi tích hợp cổng khác, cần cập nhật `payments.status` bằng callback có chữ ký từ nhà cung cấp.

## Kết nối MoMo Sandbox

Project đã có sẵn luồng MoMo `captureWallet`: tạo đơn chờ thanh toán, ký request bằng HMAC-SHA256, chuyển sang MoMo, nhận IPN và xác nhận chữ ký callback. Để chạy được, cần đăng ký tài khoản đối tác MoMo và lấy `partnerCode`, `accessKey`, `secretKey` trong môi trường Sandbox.

MoMo không gọi được địa chỉ `localhost`, vì vậy `MOMO_REDIRECT_URL` và `MOMO_IPN_URL` phải là URL HTTPS public (có thể dùng một tunnel như ngrok khi phát triển):

```text
MOMO_PARTNER_CODE=MOMxxxx
MOMO_ACCESS_KEY=...
MOMO_SECRET_KEY=...
MOMO_ENDPOINT=https://test-payment.momo.vn/v2/gateway/api/create
MOMO_REDIRECT_URL=https://your-public-host.example/index.php?momo=return
MOMO_IPN_URL=https://your-public-host.example/index.php?momo=ipn
```

Các biến môi trường phải được khai báo cho PHP/Apache của MAMP, không commit `MOMO_SECRET_KEY` vào source. Sau khi cấu hình, chọn **Ví MoMo** ở trang thanh toán; chỉ IPN có chữ ký hợp lệ và đúng số tiền mới chuyển đơn sang `paid`. Production phải thay endpoint Sandbox bằng endpoint do MoMo cấp và dùng credential Production.

Với MAMP trên macOS, mở `/Applications/MAMP/conf/apache/httpd.conf`, thêm các dòng `SetEnv` ở cuối file rồi restart Apache:

```apache
SetEnv MOMO_PARTNER_CODE MOMxxxx
SetEnv MOMO_ACCESS_KEY your_access_key
SetEnv MOMO_SECRET_KEY your_secret_key
SetEnv MOMO_ENDPOINT https://test-payment.momo.vn/v2/gateway/api/create
SetEnv MOMO_REDIRECT_URL https://your-public-host.example/index.php?momo=return
SetEnv MOMO_IPN_URL https://your-public-host.example/index.php?momo=ipn
```

Không dùng `localhost` cho hai URL callback khi test MoMo. Nếu chạy local, dùng URL HTTPS do tunnel cung cấp và trỏ tunnel vào cổng Apache của MAMP (thường là `8888`).
