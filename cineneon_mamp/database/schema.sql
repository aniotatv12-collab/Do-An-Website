SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(20) NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('member', 'staff', 'admin') NOT NULL DEFAULT 'member',
    status ENUM('active', 'locked') NOT NULL DEFAULT 'active',
    points INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS genres (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    slug VARCHAR(90) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS movies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(220) NOT NULL,
    original_title VARCHAR(220) NULL,
    slug VARCHAR(240) NOT NULL UNIQUE,
    synopsis TEXT NULL,
    duration SMALLINT UNSIGNED NOT NULL,
    age_rating ENUM('P', 'K', 'T13', 'T16', 'T18') NOT NULL DEFAULT 'P',
    release_date DATE NULL,
    country VARCHAR(100) NULL,
    language VARCHAR(80) NOT NULL DEFAULT 'Phụ đề',
    poster_url TEXT NULL,
    backdrop_url TEXT NULL,
    trailer_url TEXT NULL,
    rating DECIMAL(3,1) NOT NULL DEFAULT 0,
    hot_score TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('now_showing', 'upcoming', 'hidden') NOT NULL DEFAULT 'upcoming',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_movies_status (status),
    INDEX idx_movies_release_date (release_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS movie_genres (
    movie_id BIGINT UNSIGNED NOT NULL,
    genre_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (movie_id, genre_id),
    CONSTRAINT fk_movie_genres_movie FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    CONSTRAINT fk_movie_genres_genre FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cinemas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    status ENUM('active', 'maintenance', 'hidden') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cinema_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    room_type ENUM('2D', '3D', 'IMAX', '4DX', 'VIP') NOT NULL DEFAULT '2D',
    rows_count TINYINT UNSIGNED NOT NULL DEFAULT 8,
    seats_per_row TINYINT UNSIGNED NOT NULL DEFAULT 16,
    status ENUM('active', 'maintenance') NOT NULL DEFAULT 'active',
    UNIQUE KEY uq_room_name (cinema_id, name),
    CONSTRAINT fk_rooms_cinema FOREIGN KEY (cinema_id) REFERENCES cinemas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seats (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id INT UNSIGNED NOT NULL,
    seat_code VARCHAR(12) NOT NULL,
    seat_row VARCHAR(3) NOT NULL,
    seat_number TINYINT UNSIGNED NOT NULL,
    seat_type ENUM('standard', 'vip', 'sweetbox') NOT NULL DEFAULT 'standard',
    price_modifier INT NOT NULL DEFAULT 0,
    status ENUM('active', 'maintenance', 'hidden') NOT NULL DEFAULT 'active',
    UNIQUE KEY uq_room_seat (room_id, seat_code),
    CONSTRAINT fk_seats_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS showtimes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    movie_id BIGINT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    format VARCHAR(50) NOT NULL DEFAULT '2D Digital',
    language VARCHAR(50) NOT NULL DEFAULT 'Phụ đề',
    base_price INT UNSIGNED NOT NULL DEFAULT 120000,
    status ENUM('scheduled', 'selling', 'closed', 'cancelled') NOT NULL DEFAULT 'selling',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_showtimes_movie_start (movie_id, start_time),
    INDEX idx_showtimes_room_start (room_id, start_time),
    CONSTRAINT fk_showtimes_movie FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE RESTRICT,
    CONSTRAINT fk_showtimes_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS promotions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(160) NOT NULL,
    discount_type ENUM('percent', 'fixed') NOT NULL,
    discount_value INT UNSIGNED NOT NULL,
    minimum_amount INT UNSIGNED NOT NULL DEFAULT 0,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    usage_limit INT UNSIGNED NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(30) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NULL,
    showtime_id BIGINT UNSIGNED NOT NULL,
    promotion_id INT UNSIGNED NULL,
    customer_name VARCHAR(120) NOT NULL,
    customer_email VARCHAR(190) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    subtotal INT UNSIGNED NOT NULL,
    discount_amount INT UNSIGNED NOT NULL DEFAULT 0,
    total_amount INT UNSIGNED NOT NULL,
    payment_method VARCHAR(40) NULL,
    status ENUM('pending', 'paid', 'cancelled', 'expired', 'refunded') NOT NULL DEFAULT 'pending',
    expires_at DATETIME NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_status_created (status, created_at),
    INDEX idx_orders_user (user_id),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_showtime FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE RESTRICT,
    CONSTRAINT fk_orders_promotion FOREIGN KEY (promotion_id) REFERENCES promotions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    seat_id BIGINT UNSIGNED NOT NULL,
    seat_code VARCHAR(12) NOT NULL,
    seat_type VARCHAR(20) NOT NULL,
    unit_price INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_order_seat (order_id, seat_id),
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_seat FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seat_holds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    showtime_id BIGINT UNSIGNED NOT NULL,
    seat_id BIGINT UNSIGNED NOT NULL,
    session_token VARCHAR(128) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_showtime_seat_hold (showtime_id, seat_id),
    INDEX idx_holds_expiry (expires_at),
    CONSTRAINT fk_holds_showtime FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE,
    CONSTRAINT fk_holds_seat FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    provider VARCHAR(40) NOT NULL,
    transaction_code VARCHAR(120) NULL,
    amount INT UNSIGNED NOT NULL,
    status ENUM('pending', 'success', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    response_payload JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_payments_order (order_id),
    CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(20) NULL,
    subject VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'processing', 'resolved') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_status_created (status, created_at),
    CONSTRAINT fk_contact_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

