<?php
declare(strict_types=1);

function dbSeed(PDO $pdo, array $sampleMovies): void
{
    if ((int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $statement = $pdo->prepare(
            "INSERT INTO users (full_name, email, phone, password_hash, role, points)
             VALUES (:name, :email, :phone, :password_hash, 'admin', 1000)"
        );
        $statement->execute([
            'name' => 'CineNeon Admin',
            'email' => 'admin@cineneon.vn',
            'phone' => '0900000000',
            'password_hash' => password_hash('Admin@123', PASSWORD_DEFAULT),
        ]);
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM cinemas')->fetchColumn() === 0) {
        $cinemaStatement = $pdo->prepare('INSERT INTO cinemas (name, address, city) VALUES (:name, :address, :city)');
        $cinemaStatement->execute([
            'name' => 'CineNeon Landmark 81',
            'address' => 'Tầng B1, Vinhomes Landmark 81, Bình Thạnh',
            'city' => 'TP. Hồ Chí Minh',
        ]);
        $cinemaId = (int) $pdo->lastInsertId();
        $types = ['IMAX', '3D', '2D', 'IMAX', 'VIP', '2D', '3D', '2D'];
        $roomStatement = $pdo->prepare(
            'INSERT INTO rooms (cinema_id, name, room_type, rows_count, seats_per_row)
             VALUES (:cinema_id, :name, :room_type, 9, 16)'
        );
        for ($index = 1; $index <= 8; $index++) {
            $roomStatement->execute([
                'cinema_id' => $cinemaId,
                'name' => 'Phòng ' . str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'room_type' => $types[$index - 1],
            ]);
            dbSeedSeats($pdo, (int) $pdo->lastInsertId());
        }

        $cinemaStatement->execute([
            'name' => 'CineNeon Thủ Thiêm Cyber',
            'address' => 'Thiso Mall Sala, TP. Thủ Đức',
            'city' => 'TP. Hồ Chí Minh',
        ]);
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM movies')->fetchColumn() === 0) {
        foreach ($sampleMovies as $sample) {
            dbSaveMovie($pdo, [
                'title' => $sample['title'],
                'original_title' => $sample['subtitle'],
                'synopsis' => 'Một hành trình điện ảnh giàu cảm xúc với hình ảnh mãn nhãn, âm thanh sống động và câu chuyện cuốn hút.',
                'duration' => $sample['duration'],
                'age_rating' => $sample['age'],
                'release_date' => date('Y-m-d'),
                'language' => $sample['language'],
                'poster_url' => $sample['poster'],
                'backdrop_url' => $sample['backdrop'],
                'rating' => $sample['rating'],
                'hot_score' => $sample['hot'],
                'status' => 'now_showing',
                'genres' => $sample['genre'],
            ]);
        }
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM showtimes')->fetchColumn() === 0) {
        $databaseMovies = $pdo->query('SELECT id, duration, language FROM movies ORDER BY id')->fetchAll();
        $rooms = array_map('intval', array_column($pdo->query('SELECT id FROM rooms ORDER BY id')->fetchAll(), 'id'));
        $showtimeStatement = $pdo->prepare(
            "INSERT INTO showtimes (movie_id, room_id, start_time, end_time, format, language, base_price, status)
             VALUES (:movie_id, :room_id, :start_time, :end_time, :format, :language, :base_price, 'selling')"
        );
        foreach ($databaseMovies as $movieIndex => $databaseMovie) {
            $source = $sampleMovies[$movieIndex % count($sampleMovies)];
            foreach (range(0, 4) as $dayOffset) {
                foreach ($source['times'] as $timeIndex => $time) {
                    $start = new DateTimeImmutable('today +' . $dayOffset . ' days ' . $time);
                    $end = $start->modify('+' . (int) $databaseMovie['duration'] . ' minutes');
                    $roomId = $rooms[($movieIndex * 4 + $timeIndex) % count($rooms)];
                    $showtimeStatement->execute([
                        'movie_id' => $databaseMovie['id'],
                        'room_id' => $roomId,
                        'start_time' => $start->format('Y-m-d H:i:s'),
                        'end_time' => $end->format('Y-m-d H:i:s'),
                        'format' => $source['format'],
                        'language' => $databaseMovie['language'],
                        'base_price' => strpos($source['format'], 'IMAX') !== false ? 140000 : 120000,
                    ]);
                }
            }
        }
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM promotions')->fetchColumn() === 0) {
        $pdo->exec(
            "INSERT INTO promotions (code, name, discount_type, discount_value, minimum_amount, starts_at, ends_at, usage_limit)
             VALUES ('CINENEON10', 'Ưu đãi thành viên mới', 'percent', 10, 200000, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), 1000)"
        );
    }
}

function dbSeedSeats(PDO $pdo, int $roomId): void
{
    $statement = $pdo->prepare(
        'INSERT INTO seats (room_id, seat_code, seat_row, seat_number, seat_type, price_modifier)
         VALUES (:room_id, :seat_code, :seat_row, :seat_number, :seat_type, :price_modifier)'
    );
    foreach (range('A', 'H') as $row) {
        foreach (range(1, 16) as $number) {
            $isVip = in_array($row, ['E', 'F', 'G', 'H'], true);
            $statement->execute([
                'room_id' => $roomId,
                'seat_code' => $row . $number,
                'seat_row' => $row,
                'seat_number' => $number,
                'seat_type' => $isVip ? 'vip' : 'standard',
                'price_modifier' => $isVip ? 10000 : 0,
            ]);
        }
    }
    foreach ([1, 3, 5, 7, 9, 11] as $number) {
        $statement->execute([
            'room_id' => $roomId,
            'seat_code' => 'J' . $number . '–' . ($number + 1),
            'seat_row' => 'J',
            'seat_number' => $number,
            'seat_type' => 'sweetbox',
            'price_modifier' => 180000,
        ]);
    }
}
