<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/config/database.php';
require dirname(__DIR__) . '/includes/data.php';
require dirname(__DIR__) . '/includes/repositories.php';

function ensure(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$pdo = database();
ensure(databaseIsReady(), 'Database chưa sẵn sàng.');

$tableCount = (int) $pdo->query(
    'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()'
)->fetchColumn();
ensure($tableCount >= 14, 'Thiếu bảng dữ liệu cần thiết.');

$pdo->beginTransaction();
try {
    $movieId = dbSaveMovie($pdo, [
        'title' => 'Phim kiểm thử CRUD ' . bin2hex(random_bytes(3)),
        'original_title' => 'CRUD Smoke Test',
        'synopsis' => 'Bản ghi tạm dùng để kiểm tra thêm sửa xóa mềm.',
        'duration' => 99,
        'age_rating' => 'P',
        'release_date' => date('Y-m-d'),
        'language' => 'Phụ đề',
        'rating' => 7.5,
        'hot_score' => 50,
        'status' => 'upcoming',
        'genres' => 'Kiểm thử',
    ]);
    ensure($movieId > 0, 'Không thể thêm phim.');

    dbSaveMovie($pdo, [
        'movie_id' => $movieId,
        'title' => 'Phim kiểm thử đã sửa',
        'original_title' => 'CRUD Smoke Test',
        'synopsis' => 'Nội dung đã cập nhật.',
        'duration' => 105,
        'age_rating' => 'T13',
        'release_date' => date('Y-m-d'),
        'language' => 'Phụ đề',
        'rating' => 8.0,
        'hot_score' => 60,
        'status' => 'now_showing',
        'genres' => 'Kiểm thử',
    ]);
    ensure(dbFetchMovie($pdo, $movieId)['duration'] === 105, 'Không thể sửa phim.');

    dbArchiveMovie($pdo, $movieId);
    $archivedStatus = $pdo->query('SELECT status FROM movies WHERE id = ' . $movieId)->fetchColumn();
    ensure($archivedStatus === 'hidden', 'Không thể xóa mềm phim.');

    $messageId = dbCreateContactMessage($pdo, [
        'name' => 'Khách kiểm thử',
        'email' => 'smoke-test@cineneon.local',
        'phone' => '0900000001',
        'subject' => 'Kiểm tra biểu mẫu liên hệ',
        'message' => 'Nội dung tạm được hoàn tác sau khi kiểm tra thành công.',
    ]);
    ensure($messageId > 0, 'Không thể lưu liên hệ.');

    $pdo->rollBack();
    fwrite(STDOUT, "PASS: MySQL 14 bảng, CRUD phim và lưu liên hệ hoạt động.\n");
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
