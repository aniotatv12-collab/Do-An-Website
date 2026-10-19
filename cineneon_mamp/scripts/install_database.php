<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/config/database.php';
require dirname(__DIR__) . '/includes/data.php';
require dirname(__DIR__) . '/includes/repositories.php';
require dirname(__DIR__) . '/includes/seed.php';

try {
    $config = databaseConfig();
    $databaseName = preg_replace('/[^a-zA-Z0-9_]/', '', $config['name']);
    databaseServer()->exec("CREATE DATABASE IF NOT EXISTS `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
    foreach (preg_split('/;\s*(?:\r?\n|$)/', (string) $schema) as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            database()->exec($statement);
        }
    }
    dbSeed(database(), $movies);

    $tableCount = (int) database()->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()"
    )->fetchColumn();
    $movieCount = (int) database()->query('SELECT COUNT(*) FROM movies')->fetchColumn();
    $showtimeCount = (int) database()->query('SELECT COUNT(*) FROM showtimes')->fetchColumn();

    fwrite(STDOUT, "CineNeon database installed successfully.\n");
    fwrite(STDOUT, "Tables: {$tableCount}; Movies: {$movieCount}; Showtimes: {$showtimeCount}.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, 'Installation failed: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

