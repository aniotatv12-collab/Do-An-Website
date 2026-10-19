<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/repositories.php';
require __DIR__ . '/includes/momo.php';

$allowedPages = ['home', 'movie', 'seats', 'checkout', 'ticket', 'login', 'register', 'contact', 'admin'];
$page = $_GET['page'] ?? 'home';
$page = in_array($page, $allowedPages, true) ? $page : 'home';
$databaseOnline = databaseIsReady();
$pdo = $databaseOnline ? database() : null;

if ($databaseOnline && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['momo'] ?? '') === 'ipn') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        momoHandleIpn($pdo, file_get_contents('php://input') ?: '{}');
        echo json_encode(['resultCode' => 0, 'message' => 'OK']);
    } catch (Throwable $exception) {
        http_response_code(400);
        echo json_encode(['resultCode' => 1, 'message' => $exception->getMessage()]);
    }
    exit;
}

if ($databaseOnline && ($_GET['momo'] ?? '') === 'return') {
    try {
        $orderId = momoHandleReturn($pdo, $_GET);
        $_SESSION['last_order_id'] = $orderId;
        redirectWithFlash('index.php?page=ticket&order=' . $orderId, 'Đang cập nhật kết quả thanh toán MoMo.');
    } catch (Throwable $exception) {
        redirectWithFlash('index.php?page=home', $exception->getMessage(), 'error');
    }
}

if ($databaseOnline) {
    $movies = dbFetchMovies($pdo, $page === 'admin');
}
if (!$movies) {
    http_response_code(503);
    exit('Chưa có dữ liệu phim. Vui lòng chạy install.php.');
}
$featured = $movies[0];
$cinemas = $databaseOnline ? dbFetchAdminCinemas($pdo) : [];

if ($page === 'admin' && $databaseOnline && ($_GET['export'] ?? '') === 'orders') {
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        header('Location: index.php?page=login&next=admin');
        exit;
    }
    $rows = dbFetchAdminOrders($pdo);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cineneon-orders-' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Ma don', 'Khach hang', 'Email', 'Phim', 'Ghe', 'Tong tien', 'Trang thai', 'Thoi gian']);
    foreach ($rows as $row) {
        fputcsv($output, [$row['order_code'], $row['customer_name'], $row['customer_email'], $row['movie_title'], $row['seat_codes'], $row['total_amount'], $row['status'], $row['created_at']]);
    }
    fclose($output);
    exit;
}

$_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

function redirectWithFlash(string $url, string $message, string $type = 'success'): never
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    header('Location: ' . $url);
    exit;
}

function requireValidCsrf(): void
{
    if (!hash_equals($_SESSION['csrf_token'] ?? '', (string) ($_POST['csrf_token'] ?? ''))) {
        throw new RuntimeException('Phiên làm việc đã hết hạn. Vui lòng thử lại.');
    }
}

function requireAdminUser(): void
{
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        throw new RuntimeException('Bạn không có quyền thực hiện thao tác này.');
    }
}

if (isset($_GET['logout'])) {
    unset($_SESSION['user']);
    redirectWithFlash('index.php?page=home', 'Bạn đã đăng xuất.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['_action'] ?? '');
    try {
        requireValidCsrf();
        if (!$databaseOnline) {
            throw new RuntimeException('MySQL chưa được cài đặt. Hãy mở install.php trước.');
        }

        switch ($action) {
            case 'login':
                $user = dbAuthenticate($pdo, (string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''));
                if (!$user) {
                    throw new InvalidArgumentException('Email hoặc mật khẩu không chính xác.');
                }
                $_SESSION['user'] = $user;
                session_regenerate_id(true);
                $next = ($_POST['next'] ?? '') === 'admin' && $user['role'] === 'admin' ? 'admin' : 'home';
                redirectWithFlash('index.php?page=' . $next, 'Đăng nhập thành công. Chào mừng ' . $user['name'] . '!');

            case 'register':
                $_SESSION['user'] = dbRegisterUser($pdo, $_POST);
                session_regenerate_id(true);
                redirectWithFlash('index.php?page=home', 'Tạo tài khoản thành công. Bạn nhận được 100 điểm chào mừng!');

            case 'contact':
                dbCreateContactMessage($pdo, $_POST, isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null);
                redirectWithFlash('index.php?page=contact', 'CineNeon đã nhận được lời nhắn. Bộ phận hỗ trợ sẽ phản hồi sớm nhất.');

            case 'save_movie':
                requireAdminUser();
                dbSaveMovie($pdo, $_POST);
                redirectWithFlash('index.php?page=admin&view=movies', 'Đã lưu thông tin phim vào MySQL.');

            case 'archive_movie':
                requireAdminUser();
                dbArchiveMovie($pdo, (int) ($_POST['movie_id'] ?? 0));
                redirectWithFlash('index.php?page=admin&view=movies', 'Đã xóa mềm phim. Dữ liệu lịch sử vẫn được giữ trong MySQL.');

            case 'save_showtime':
                requireAdminUser();
                dbSaveShowtime($pdo, $_POST);
                redirectWithFlash('index.php?page=admin&view=showtimes', 'Đã tạo suất chiếu mới.');

            case 'save_cinema':
                requireAdminUser();
                dbSaveCinema($pdo, $_POST);
                redirectWithFlash('index.php?page=admin&view=cinemas', 'Đã thêm cụm rạp mới vào MySQL.');

            case 'update_order':
                requireAdminUser();
                dbUpdateOrderStatus($pdo, (int) ($_POST['order_id'] ?? 0), (string) ($_POST['status'] ?? 'pending'));
                redirectWithFlash('index.php?page=admin&view=orders', 'Đã cập nhật trạng thái đơn vé.');

            case 'update_user':
                requireAdminUser();
                dbUpdateUserAccess(
                    $pdo,
                    (int) ($_POST['user_id'] ?? 0),
                    (string) ($_POST['role'] ?? 'member'),
                    (string) ($_POST['status'] ?? 'active'),
                    (int) $_SESSION['user']['id']
                );
                redirectWithFlash('index.php?page=admin&view=users', 'Đã cập nhật quyền người dùng.');

            case 'checkout':
                $orderId = dbCreateOrder($pdo, $_POST, isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null);
                $_SESSION['last_order_id'] = $orderId;
                if ((string) ($_POST['payment'] ?? '') === 'momo') {
                    header('Location: ' . momoCreatePayment($pdo, $orderId));
                    exit;
                }
                header('Location: index.php?page=ticket&order=' . $orderId);
                exit;

            default:
                throw new InvalidArgumentException('Yêu cầu không hợp lệ.');
        }
    } catch (Throwable $exception) {
        switch ($action) {
            case 'login':
                $target = 'index.php?page=login&next=' . urlencode((string) ($_POST['next'] ?? ''));
                break;
            case 'register':
                $target = 'index.php?page=register';
                break;
            case 'contact':
                $target = 'index.php?page=contact';
                break;
            case 'save_movie':
            case 'archive_movie':
                $target = 'index.php?page=admin&view=movies';
                break;
            case 'save_showtime':
                $target = 'index.php?page=admin&view=showtimes';
                break;
            case 'save_cinema':
                $target = 'index.php?page=admin&view=cinemas';
                break;
            case 'update_order':
                $target = 'index.php?page=admin&view=orders';
                break;
            case 'update_user':
                $target = 'index.php?page=admin&view=users';
                break;
            case 'checkout':
                $target = 'index.php?page=checkout&movie=' . (int) ($_POST['movie_id'] ?? 1) . '&time=' . urlencode((string) ($_POST['show_time'] ?? '19:45'));
                break;
            default:
                $target = 'index.php?page=home';
        }
        redirectWithFlash($target, $exception->getMessage(), 'error');
    }
}

if ($page === 'admin' && $databaseOnline && ($_SESSION['user']['role'] ?? '') !== 'admin') {
    redirectWithFlash('index.php?page=login&next=admin', 'Vui lòng đăng nhập bằng tài khoản quản trị.', 'error');
}

$movie = movieById($movies, (int) ($_GET['movie'] ?? 1));
$selectedTime = (string) ($_GET['time'] ?? ($movie['times'][0] ?? '19:45'));
$showtimeId = isset($_GET['showtime']) ? (int) $_GET['showtime'] : (int) ($movie['showtime_ids'][$selectedTime] ?? 0);
$showtime = $databaseOnline ? dbFindShowtime($pdo, $movie['id'], $selectedTime, $showtimeId ?: null) : null;
$showtimeId = (int) ($showtime['id'] ?? $showtimeId);
$soldSeats = $databaseOnline && $showtimeId ? dbSoldSeatCodes($pdo, $showtimeId) : ['B5', 'B6', 'D3', 'D4', 'G5', 'G6', 'G7', 'H14'];
if ($page === 'seats' && isset($_GET['seat_status'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['sold' => $soldSeats], JSON_UNESCAPED_UNICODE);
    exit;
}
$rooms = $databaseOnline ? dbFetchRooms($pdo) : [];
$adminOrders = $databaseOnline && $page === 'admin' ? dbFetchAdminOrders($pdo) : [];
$adminUsers = $databaseOnline && $page === 'admin' ? dbFetchAdminUsers($pdo) : [];
$adminCinemas = $databaseOnline && $page === 'admin' ? dbFetchAdminCinemas($pdo) : [];
$adminStats = $databaseOnline && $page === 'admin' ? dbFetchAdminStats($pdo) : [];
$adminRevenue = $databaseOnline && $page === 'admin' ? dbFetchRevenueByDay($pdo) : [];
$order = null;
if ($page === 'ticket' && $databaseOnline && isset($_GET['order'])) {
    $orderId = (int) $_GET['order'];
    $viewerId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
    $canViewAnyOrder = ($_SESSION['user']['role'] ?? '') === 'admin'
        || (int) ($_SESSION['last_order_id'] ?? 0) === $orderId;
    $order = dbFetchOrder($pdo, $orderId, $viewerId, $canViewAnyOrder);
    if (!$order) {
        http_response_code(404);
        exit('Vé không tồn tại hoặc bạn không có quyền xem vé này.');
    }
}

$titles = [
    'home' => 'CineNeon — Đặt vé xem phim',
    'movie' => $movie['title'] . ' — CineNeon',
    'seats' => 'Chọn ghế — CineNeon',
    'checkout' => 'Thanh toán — CineNeon',
    'ticket' => 'Vé điện tử — CineNeon',
    'login' => 'Đăng nhập — CineNeon',
    'register' => 'Đăng ký — CineNeon',
    'contact' => 'Liên hệ — CineNeon',
    'admin' => 'Quản trị — CineNeon',
];
$title = $titles[$page];
$isAdmin = $page === 'admin';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/views/' . $page . '.php';
require __DIR__ . '/includes/footer.php';
