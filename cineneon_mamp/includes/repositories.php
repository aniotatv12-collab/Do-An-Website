<?php
declare(strict_types=1);

function dbFetchMovies(PDO $pdo, bool $includeHidden = false): array
{
    $sql = "SELECT m.*,
                   COALESCE(GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', '), 'Khác') AS genres
            FROM movies m
            LEFT JOIN movie_genres mg ON mg.movie_id = m.id
            LEFT JOIN genres g ON g.id = mg.genre_id";
    if (!$includeHidden) {
        $sql .= " WHERE m.status <> 'hidden'";
    }
    $sql .= ' GROUP BY m.id ORDER BY m.hot_score DESC, m.release_date DESC';
    $rows = $pdo->query($sql)->fetchAll();
    return array_map(static fn(array $row): array => dbMapMovie($pdo, $row), $rows);
}

function dbFetchMovie(PDO $pdo, int $id): ?array
{
    $statement = $pdo->prepare(
        "SELECT m.*,
                COALESCE(GROUP_CONCAT(DISTINCT g.name ORDER BY g.name SEPARATOR ', '), 'Khác') AS genres
         FROM movies m
         LEFT JOIN movie_genres mg ON mg.movie_id = m.id
         LEFT JOIN genres g ON g.id = mg.genre_id
         WHERE m.id = :id
         GROUP BY m.id
         LIMIT 1"
    );
    $statement->execute(['id' => $id]);
    $row = $statement->fetch();
    return $row ? dbMapMovie($pdo, $row) : null;
}

function dbMapMovie(PDO $pdo, array $row): array
{
    $showtimeStatement = $pdo->prepare(
        "SELECT id, DATE_FORMAT(start_time, '%H:%i') AS show_time, format
         FROM showtimes
         WHERE movie_id = :movie_id
           AND status IN ('scheduled', 'selling')
           AND start_time >= CURDATE()
         ORDER BY start_time
         LIMIT 8"
    );
    $showtimeStatement->execute(['movie_id' => $row['id']]);
    $showtimes = $showtimeStatement->fetchAll();
    $times = [];
    $showtimeIds = [];
    foreach ($showtimes as $showtime) {
        if (!in_array($showtime['show_time'], $times, true)) {
            $times[] = $showtime['show_time'];
            $showtimeIds[$showtime['show_time']] = (int) $showtime['id'];
        }
    }

    return [
        'id' => (int) $row['id'],
        'title' => $row['title'],
        'subtitle' => $row['original_title'] ?: $row['title'],
        'genre' => $row['genres'],
        'duration' => (int) $row['duration'],
        'rating' => (float) $row['rating'],
        'age' => $row['age_rating'],
        'format' => $showtimes[0]['format'] ?? '2D Digital',
        'language' => $row['language'],
        'hot' => (int) $row['hot_score'],
        'poster' => $row['poster_url'] ?: 'assets/favicon.svg',
        'backdrop' => $row['backdrop_url'] ?: $row['poster_url'],
        'times' => $times ?: ['18:30', '20:30', '22:30'],
        'showtime_ids' => $showtimeIds,
        'status' => $row['status'],
        'release_date' => $row['release_date'],
        'synopsis' => $row['synopsis'] ?? '',
    ];
}

function dbFindShowtime(PDO $pdo, int $movieId, string $time, ?int $showtimeId = null): ?array
{
    if ($showtimeId) {
        $statement = $pdo->prepare(
            'SELECT s.*, r.name AS room_name, r.room_type, r.cinema_id, c.name AS cinema_name, c.address
             FROM showtimes s
             JOIN rooms r ON r.id = s.room_id
             JOIN cinemas c ON c.id = r.cinema_id
                         WHERE s.id = :id
                             AND s.movie_id = :movie_id
                             AND TIME_FORMAT(s.start_time, \'%H:%i\') = :show_time
                             AND s.start_time >= CURDATE()
                             AND s.status IN (\'scheduled\', \'selling\')
                         LIMIT 1'
        );
                $statement->execute(['id' => $showtimeId, 'movie_id' => $movieId, 'show_time' => $time]);
    } else {
        $statement = $pdo->prepare(
            "SELECT s.*, r.name AS room_name, r.room_type, r.cinema_id, c.name AS cinema_name, c.address
             FROM showtimes s
             JOIN rooms r ON r.id = s.room_id
             JOIN cinemas c ON c.id = r.cinema_id
             WHERE s.movie_id = :movie_id
               AND TIME_FORMAT(s.start_time, '%H:%i') = :show_time
               AND s.start_time >= CURDATE()
               AND s.status IN ('scheduled', 'selling')
             ORDER BY s.start_time LIMIT 1"
        );
        $statement->execute(['movie_id' => $movieId, 'show_time' => $time]);
    }
    $showtime = $statement->fetch();
    return $showtime ?: null;
}

function dbSoldSeatCodes(PDO $pdo, int $showtimeId): array
{
    $statement = $pdo->prepare(
        "SELECT DISTINCT oi.seat_code
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.showtime_id = :showtime_id
           AND (o.status = 'paid' OR (o.status = 'pending' AND o.expires_at > NOW()))"
    );
    $statement->execute(['showtime_id' => $showtimeId]);
    return array_column($statement->fetchAll(), 'seat_code');
}

function dbAuthenticate(PDO $pdo, string $email, string $password): ?array
{
    $statement = $pdo->prepare('SELECT * FROM users WHERE email = :email AND status = \'active\' LIMIT 1');
    $statement->execute(['email' => mb_strtolower(trim($email))]);
    $user = $statement->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }
    return dbPublicUser($user);
}

function dbRegisterUser(PDO $pdo, array $data): array
{
    $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
    $name = trim((string) ($data['name'] ?? ''));
    $phone = preg_replace('/\s+/', '', (string) ($data['phone'] ?? ''));
    $password = (string) ($data['password'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        throw new InvalidArgumentException('Vui lòng nhập đầy đủ thông tin và mật khẩu ít nhất 8 ký tự.');
    }

    $statement = $pdo->prepare(
        'INSERT INTO users (full_name, email, phone, password_hash, role, points)
         VALUES (:full_name, :email, :phone, :password_hash, \'member\', 100)'
    );
    try {
        $statement->execute([
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone ?: null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    } catch (PDOException $exception) {
        if ((int) $exception->errorInfo[1] === 1062) {
            throw new InvalidArgumentException('Email hoặc số điện thoại đã được sử dụng.');
        }
        throw $exception;
    }

    return ['id' => (int) $pdo->lastInsertId(), 'name' => $name, 'email' => $email, 'role' => 'member'];
}

function dbPublicUser(array $user): array
{
    return [
        'id' => (int) $user['id'],
        'name' => $user['full_name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
}

function dbCreateContactMessage(PDO $pdo, array $data, ?int $userId = null): int
{
    $name = trim((string) ($data['name'] ?? ''));
    $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
    $phone = preg_replace('/\s+/', '', (string) ($data['phone'] ?? ''));
    $subject = trim((string) ($data['subject'] ?? ''));
    $message = trim((string) ($data['message'] ?? ''));

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Vui lòng nhập họ tên và email hợp lệ.');
    }
    if (mb_strlen($subject) < 3 || mb_strlen($subject) > 160) {
        throw new InvalidArgumentException('Tiêu đề cần có từ 3 đến 160 ký tự.');
    }
    if (mb_strlen($message) < 10 || mb_strlen($message) > 3000) {
        throw new InvalidArgumentException('Nội dung liên hệ cần có từ 10 đến 3000 ký tự.');
    }
    if ($phone !== '' && strlen($phone) < 9) {
        throw new InvalidArgumentException('Số điện thoại chưa hợp lệ.');
    }

    $statement = $pdo->prepare(
        'INSERT INTO contact_messages (user_id, full_name, email, phone, subject, message)
         VALUES (:user_id, :full_name, :email, :phone, :subject, :message)'
    );
    $statement->execute([
        'user_id' => $userId,
        'full_name' => $name,
        'email' => $email,
        'phone' => $phone ?: null,
        'subject' => $subject,
        'message' => $message,
    ]);
    return (int) $pdo->lastInsertId();
}

function dbSaveMovie(PDO $pdo, array $data): int
{
    $id = (int) ($data['movie_id'] ?? 0);
    $title = trim((string) ($data['title'] ?? ''));
    if ($title === '') {
        throw new InvalidArgumentException('Tên phim không được để trống.');
    }
    $slug = dbUniqueSlug($pdo, $title, $id);
    $values = [
        'title' => $title,
        'original_title' => trim((string) ($data['original_title'] ?? '')) ?: null,
        'slug' => $slug,
        'synopsis' => trim((string) ($data['synopsis'] ?? '')),
        'duration' => max(1, (int) ($data['duration'] ?? 120)),
        'age_rating' => in_array($data['age_rating'] ?? '', ['P', 'K', 'T13', 'T16', 'T18'], true) ? $data['age_rating'] : 'P',
        'release_date' => $data['release_date'] ?: null,
        'language' => trim((string) ($data['language'] ?? 'Phụ đề')),
        'poster_url' => trim((string) ($data['poster_url'] ?? '')) ?: null,
        'backdrop_url' => trim((string) ($data['backdrop_url'] ?? '')) ?: null,
        'rating' => min(10, max(0, (float) ($data['rating'] ?? 0))),
        'hot_score' => min(100, max(0, (int) ($data['hot_score'] ?? 0))),
        'status' => in_array($data['status'] ?? '', ['now_showing', 'upcoming', 'hidden'], true) ? $data['status'] : 'upcoming',
    ];

    if ($id > 0) {
        $values['id'] = $id;
        $statement = $pdo->prepare(
            'UPDATE movies SET title=:title, original_title=:original_title, slug=:slug, synopsis=:synopsis,
             duration=:duration, age_rating=:age_rating, release_date=:release_date, language=:language,
             poster_url=:poster_url, backdrop_url=:backdrop_url, rating=:rating, hot_score=:hot_score, status=:status
             WHERE id=:id'
        );
        $statement->execute($values);
    } else {
        $statement = $pdo->prepare(
            'INSERT INTO movies (title, original_title, slug, synopsis, duration, age_rating, release_date,
             language, poster_url, backdrop_url, rating, hot_score, status)
             VALUES (:title, :original_title, :slug, :synopsis, :duration, :age_rating, :release_date,
             :language, :poster_url, :backdrop_url, :rating, :hot_score, :status)'
        );
        $statement->execute($values);
        $id = (int) $pdo->lastInsertId();
    }
    dbSyncGenres($pdo, $id, (string) ($data['genres'] ?? 'Khác'));
    return $id;
}

function dbArchiveMovie(PDO $pdo, int $id): void
{
    $statement = $pdo->prepare("UPDATE movies SET status = 'hidden' WHERE id = :id");
    $statement->execute(['id' => $id]);
}

function dbSyncGenres(PDO $pdo, int $movieId, string $genreList): void
{
    $genres = array_values(array_filter(array_map('trim', explode(',', $genreList))));
    $pdo->prepare('DELETE FROM movie_genres WHERE movie_id = :movie_id')->execute(['movie_id' => $movieId]);
    foreach ($genres ?: ['Khác'] as $name) {
        $slug = dbSlug($name);
        $statement = $pdo->prepare('INSERT INTO genres (name, slug) VALUES (:name, :slug) ON DUPLICATE KEY UPDATE name = VALUES(name)');
        $statement->execute(['name' => $name, 'slug' => $slug]);
        $genreIdStatement = $pdo->prepare('SELECT id FROM genres WHERE slug = :slug');
        $genreIdStatement->execute(['slug' => $slug]);
        $genreId = (int) $genreIdStatement->fetchColumn();
        $pdo->prepare('INSERT IGNORE INTO movie_genres (movie_id, genre_id) VALUES (:movie_id, :genre_id)')
            ->execute(['movie_id' => $movieId, 'genre_id' => $genreId]);
    }
}

function dbSaveShowtime(PDO $pdo, array $data): int
{
    $movieId = (int) ($data['movie_id'] ?? 0);
    $roomId = (int) ($data['room_id'] ?? 0);
    $start = new DateTimeImmutable(($data['show_date'] ?? '') . ' ' . ($data['show_time'] ?? ''));
    $durationStatement = $pdo->prepare('SELECT duration FROM movies WHERE id = :id');
    $durationStatement->execute(['id' => $movieId]);
    $duration = (int) $durationStatement->fetchColumn();
    if (!$movieId || !$roomId || !$duration) {
        throw new InvalidArgumentException('Phim hoặc phòng chiếu không hợp lệ.');
    }
    $end = $start->modify('+' . $duration . ' minutes');
    $conflictStatement = $pdo->prepare(
        "SELECT COUNT(*) FROM showtimes
         WHERE room_id = :room_id AND status <> 'cancelled'
           AND start_time < :end_time AND end_time > :start_time"
    );
    $conflictStatement->execute([
        'room_id' => $roomId,
        'start_time' => $start->format('Y-m-d H:i:s'),
        'end_time' => $end->format('Y-m-d H:i:s'),
    ]);
    if ((int) $conflictStatement->fetchColumn() > 0) {
        throw new InvalidArgumentException('Phòng chiếu đã có suất trong khung giờ này.');
    }
    $statement = $pdo->prepare(
        'INSERT INTO showtimes (movie_id, room_id, start_time, end_time, format, language, base_price, status)
         VALUES (:movie_id, :room_id, :start_time, :end_time, :format, :language, :base_price, \'selling\')'
    );
    $statement->execute([
        'movie_id' => $movieId,
        'room_id' => $roomId,
        'start_time' => $start->format('Y-m-d H:i:s'),
        'end_time' => $end->format('Y-m-d H:i:s'),
        'format' => trim((string) ($data['format'] ?? '2D Digital')),
        'language' => trim((string) ($data['language'] ?? 'Phụ đề')),
        'base_price' => max(1000, (int) ($data['base_price'] ?? 120000)),
    ]);
    return (int) $pdo->lastInsertId();
}

function dbFetchRooms(PDO $pdo): array
{
    return $pdo->query(
        "SELECT r.id, r.name, r.room_type, c.name AS cinema_name
         FROM rooms r JOIN cinemas c ON c.id = r.cinema_id
         WHERE r.status = 'active' ORDER BY c.name, r.name"
    )->fetchAll();
}

function dbFetchAdminOrders(PDO $pdo): array
{
    return $pdo->query(
        "SELECT o.id, o.order_code, o.customer_name, o.customer_email, o.customer_phone,
                o.total_amount, o.payment_method, o.status, o.created_at,
                m.title AS movie_title, s.start_time,
                GROUP_CONCAT(oi.seat_code ORDER BY oi.seat_code SEPARATOR ', ') AS seat_codes
         FROM orders o
         JOIN showtimes s ON s.id = o.showtime_id
         JOIN movies m ON m.id = s.movie_id
         JOIN order_items oi ON oi.order_id = o.id
         GROUP BY o.id
         ORDER BY o.created_at DESC
         LIMIT 100"
    )->fetchAll();
}

function dbFetchAdminStats(PDO $pdo): array
{
    $stats = $pdo->query(
        "SELECT
            COALESCE(SUM(CASE WHEN orders.status = 'paid' AND DATE(orders.paid_at) = CURDATE() THEN orders.total_amount ELSE 0 END), 0) AS revenue_today,
            COUNT(CASE WHEN orders.status = 'paid' AND DATE(orders.paid_at) = CURDATE() THEN 1 END) AS paid_orders_today,
            COUNT(CASE WHEN orders.status = 'paid' THEN 1 END) AS paid_orders_total,
            COUNT(DISTINCT showtimes.id) AS showtimes_total,
            COUNT(DISTINCT CASE WHEN showtimes.status IN ('scheduled', 'selling') AND showtimes.start_time >= NOW() THEN showtimes.id END) AS showtimes_open
         FROM showtimes
         LEFT JOIN orders ON orders.showtime_id = showtimes.id"
    )->fetch();
    return [
        'revenue_today' => (int) ($stats['revenue_today'] ?? 0),
        'paid_orders_today' => (int) ($stats['paid_orders_today'] ?? 0),
        'paid_orders_total' => (int) ($stats['paid_orders_total'] ?? 0),
        'showtimes_total' => (int) ($stats['showtimes_total'] ?? 0),
        'showtimes_open' => (int) ($stats['showtimes_open'] ?? 0),
        'movie_count' => (int) $pdo->query("SELECT COUNT(*) FROM movies WHERE status <> 'hidden'")->fetchColumn(),
        'user_count' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn(),
    ];
}

function dbFetchRevenueByDay(PDO $pdo, int $days = 7): array
{
    $days = max(1, min(30, $days));
    $statement = $pdo->query(
        "SELECT DATE(paid_at) AS day, COALESCE(SUM(total_amount), 0) AS revenue
         FROM orders WHERE status = 'paid'
         AND paid_at >= DATE_SUB(CURDATE(), INTERVAL " . ($days - 1) . " DAY)
         GROUP BY DATE(paid_at) ORDER BY day"
    );
    $values = [];
    foreach ($statement->fetchAll() as $row) {
        $values[$row['day']] = (int) $row['revenue'];
    }
    $series = [];
    for ($offset = $days - 1; $offset >= 0; $offset--) {
        $date = (new DateTimeImmutable('today'))->modify('-' . $offset . ' days');
        $series[] = ['label' => $date->format('d/m'), 'value' => $values[$date->format('Y-m-d')] ?? 0];
    }
    return $series;
}

function dbFetchAdminUsers(PDO $pdo): array
{
    return $pdo->query(
        "SELECT id, full_name, email, phone, role, status, points, created_at
         FROM users ORDER BY created_at DESC LIMIT 100"
    )->fetchAll();
}

function dbFetchAdminCinemas(PDO $pdo): array
{
    return $pdo->query(
        "SELECT c.id, c.name, c.address, c.city, c.status,
                COUNT(DISTINCT r.id) AS room_count,
                COUNT(DISTINCT s.id) AS seat_count
         FROM cinemas c
         LEFT JOIN rooms r ON r.cinema_id = c.id
         LEFT JOIN seats s ON s.room_id = r.id
         GROUP BY c.id ORDER BY c.name"
    )->fetchAll();
}

function dbUpdateOrderStatus(PDO $pdo, int $orderId, string $status): void
{
    $allowed = ['pending', 'paid', 'cancelled', 'expired', 'refunded'];
    if (!in_array($status, $allowed, true)) {
        throw new InvalidArgumentException('Trạng thái đơn vé không hợp lệ.');
    }
    $statement = $pdo->prepare('UPDATE orders SET status = :status WHERE id = :id');
    $statement->execute(['status' => $status, 'id' => $orderId]);
}

function dbUpdateUserAccess(PDO $pdo, int $userId, string $role, string $status, int $currentUserId): void
{
    if (!in_array($role, ['member', 'staff', 'admin'], true) || !in_array($status, ['active', 'locked'], true)) {
        throw new InvalidArgumentException('Phân quyền người dùng không hợp lệ.');
    }
    if ($userId === $currentUserId && ($role !== 'admin' || $status !== 'active')) {
        throw new InvalidArgumentException('Bạn không thể tự khóa hoặc hạ quyền tài khoản đang đăng nhập.');
    }
    $statement = $pdo->prepare('UPDATE users SET role = :role, status = :status WHERE id = :id');
    $statement->execute(['role' => $role, 'status' => $status, 'id' => $userId]);
}

function dbSaveCinema(PDO $pdo, array $data): int
{
    $name = trim((string) ($data['name'] ?? ''));
    $address = trim((string) ($data['address'] ?? ''));
    $city = trim((string) ($data['city'] ?? ''));
    if ($name === '' || $address === '' || $city === '') {
        throw new InvalidArgumentException('Vui lòng nhập đầy đủ tên, địa chỉ và thành phố.');
    }
    $statement = $pdo->prepare('INSERT INTO cinemas (name, address, city, status) VALUES (:name, :address, :city, \'active\')');
    $statement->execute(['name' => $name, 'address' => $address, 'city' => $city]);
    return (int) $pdo->lastInsertId();
}

function dbCreateOrder(PDO $pdo, array $data, ?int $userId): int
{
    $movieId = (int) ($data['movie_id'] ?? 0);
    $showtimeId = (int) ($data['showtime_id'] ?? 0);
    $customerName = trim((string) ($data['name'] ?? ''));
    $customerEmail = trim((string) ($data['email'] ?? ''));
    $customerPhone = preg_replace('/\s+/', '', (string) ($data['phone'] ?? ''));
    $seatCodes = json_decode((string) ($data['seats_json'] ?? '[]'), true);
    $seatCodes = array_values(array_unique(array_filter(array_map('strval', is_array($seatCodes) ? $seatCodes : []))));
    $paymentMethod = trim((string) ($data['payment'] ?? ''));
    if (!$movieId || !$showtimeId || !$seatCodes || count($seatCodes) > 8) {
        throw new InvalidArgumentException('Suất chiếu hoặc danh sách ghế không hợp lệ.');
    }
    if (!in_array($paymentMethod, ['card', 'momo', 'zalopay', 'qr'], true)) {
        throw new InvalidArgumentException('Phương thức thanh toán không hợp lệ.');
    }
    if ($customerName === '' || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL) || strlen($customerPhone) < 9) {
        throw new InvalidArgumentException('Thông tin người nhận vé chưa hợp lệ.');
    }

    $pdo->beginTransaction();
    try {
                $showtimeStatement = $pdo->prepare(
                        'SELECT * FROM showtimes
                         WHERE id = :id AND movie_id = :movie_id
                             AND status = \'selling\' AND start_time >= NOW()
                         FOR UPDATE'
                );
                $showtimeStatement->execute(['id' => $showtimeId, 'movie_id' => $movieId]);
        $showtime = $showtimeStatement->fetch();
        if (!$showtime) {
            throw new RuntimeException('Suất chiếu không còn mở bán.');
        }

        $placeholders = implode(',', array_fill(0, count($seatCodes), '?'));
        $seatStatement = $pdo->prepare("SELECT * FROM seats WHERE room_id = ? AND seat_code IN ($placeholders) AND status = 'active' FOR UPDATE");
        $seatStatement->execute(array_merge([(int) $showtime['room_id']], $seatCodes));
        $seats = $seatStatement->fetchAll();
        if (count($seats) !== count($seatCodes)) {
            throw new RuntimeException('Có ghế không tồn tại trong phòng chiếu.');
        }

        $availabilityStatement = $pdo->prepare(
            "SELECT oi.seat_code FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE o.showtime_id = ? AND oi.seat_code IN ($placeholders)
               AND (o.status = 'paid' OR (o.status = 'pending' AND o.expires_at > NOW()))"
        );
        $availabilityStatement->execute(array_merge([$showtimeId], $seatCodes));
        if ($availabilityStatement->fetchColumn()) {
            throw new RuntimeException('Một hoặc nhiều ghế vừa được người khác đặt.');
        }

        $subtotal = 0;
        foreach ($seats as &$seat) {
            $seat['unit_price'] = (int) $showtime['base_price'] + (int) $seat['price_modifier'];
            $subtotal += $seat['unit_price'];
        }
        unset($seat);

        $orderCode = 'CN-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
           $isMomo = $paymentMethod === 'momo';
           $orderStatement = $pdo->prepare(
            "INSERT INTO orders (order_code, user_id, showtime_id, customer_name, customer_email, customer_phone,
             subtotal, total_amount, payment_method, status, expires_at, paid_at)
             VALUES (:order_code, :user_id, :showtime_id, :customer_name, :customer_email, :customer_phone,
               :subtotal, :total_amount, :payment_method, :status, DATE_ADD(NOW(), INTERVAL 5 MINUTE), :paid_at)"
        );
        $orderStatement->execute([
            'order_code' => $orderCode,
            'user_id' => $userId,
            'showtime_id' => $showtimeId,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'subtotal' => $subtotal,
            'total_amount' => $subtotal,
            'payment_method' => $paymentMethod,
            'status' => $isMomo ? 'pending' : 'paid',
            'paid_at' => $isMomo ? null : date('Y-m-d H:i:s'),
        ]);
        $orderId = (int) $pdo->lastInsertId();

        $itemStatement = $pdo->prepare(
            'INSERT INTO order_items (order_id, seat_id, seat_code, seat_type, unit_price)
             VALUES (:order_id, :seat_id, :seat_code, :seat_type, :unit_price)'
        );
        foreach ($seats as $seat) {
            $itemStatement->execute([
                'order_id' => $orderId,
                'seat_id' => $seat['id'],
                'seat_code' => $seat['seat_code'],
                'seat_type' => $seat['seat_type'],
                'unit_price' => $seat['unit_price'],
            ]);
        }
        $pdo->prepare(
            "INSERT INTO payments (order_id, provider, transaction_code, amount, status)
             VALUES (:order_id, :provider, :transaction_code, :amount, :status)"
        )->execute([
            'order_id' => $orderId,
            'provider' => $paymentMethod,
            'transaction_code' => $isMomo ? null : 'DEMO-' . strtoupper(bin2hex(random_bytes(4))),
            'amount' => $subtotal,
            'status' => $isMomo ? 'pending' : 'success',
        ]);
        $pdo->commit();
        return $orderId;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function dbOrderIdByCode(PDO $pdo, string $orderCode): ?int
{
    $statement = $pdo->prepare('SELECT id FROM orders WHERE order_code = :order_code LIMIT 1');
    $statement->execute(['order_code' => $orderCode]);
    $id = $statement->fetchColumn();
    return $id === false ? null : (int) $id;
}

function dbFetchOrder(PDO $pdo, int $orderId, ?int $userId = null, bool $allowAnyOrder = false): ?array
{
    $conditions = ['o.id = :id'];
    $parameters = ['id' => $orderId];
    if (!$allowAnyOrder) {
        if ($userId === null) {
            return null;
        }
        $conditions[] = 'o.user_id = :user_id';
        $parameters['user_id'] = $userId;
    }
    $statement = $pdo->prepare(
        "SELECT o.*, m.title, m.original_title, m.poster_url, m.backdrop_url, m.age_rating,
                s.start_time, s.format, s.language, r.name AS room_name, c.name AS cinema_name,
                GROUP_CONCAT(oi.seat_code ORDER BY oi.seat_code SEPARATOR ', ') AS seat_codes
         FROM orders o
         JOIN showtimes s ON s.id = o.showtime_id
         JOIN movies m ON m.id = s.movie_id
         JOIN rooms r ON r.id = s.room_id
         JOIN cinemas c ON c.id = r.cinema_id
         JOIN order_items oi ON oi.order_id = o.id
            WHERE " . implode(' AND ', $conditions) . "
         GROUP BY o.id LIMIT 1"
    );
        $statement->execute($parameters);
    $order = $statement->fetch();
    return $order ?: null;
}

function dbSlug(string $value): string
{
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii), '-'));
    return $slug ?: 'item';
}

function dbUniqueSlug(PDO $pdo, string $title, int $excludeId = 0): string
{
    $base = dbSlug($title);
    $slug = $base;
    $counter = 2;
    do {
        $statement = $pdo->prepare('SELECT COUNT(*) FROM movies WHERE slug = :slug AND id <> :id');
        $statement->execute(['slug' => $slug, 'id' => $excludeId]);
        $exists = (int) $statement->fetchColumn() > 0;
        if ($exists) {
            $slug = $base . '-' . $counter++;
        }
    } while ($exists);
    return $slug;
}
