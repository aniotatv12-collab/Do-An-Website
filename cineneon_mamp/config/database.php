<?php
declare(strict_types=1);

function databaseConfig(): array
{
    return [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '8889',
        'name' => getenv('DB_NAME') ?: 'cineneon',
        'user' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : 'root',
    ];
}

function databaseServer(): PDO
{
    $config = databaseConfig();
    return new PDO(
        sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['host'], $config['port']),
        $config['user'],
        $config['password'],
        databaseOptions()
    );
}

function database(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $config = databaseConfig();
    $connection = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['name']),
        $config['user'],
        $config['password'],
        databaseOptions()
    );
    return $connection;
}

function databaseOptions(): array
{
    return [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
}

function databaseIsReady(): bool
{
    try {
        database()->query('SELECT 1 FROM movies LIMIT 1');
        return true;
    } catch (Throwable $exception) {
        return false;
    }
}

function momoConfig(): array
{
    return [
        'partner_code' => getenv('MOMO_PARTNER_CODE') ?: '',
        'access_key' => getenv('MOMO_ACCESS_KEY') ?: '',
        'secret_key' => getenv('MOMO_SECRET_KEY') ?: '',
        'endpoint' => getenv('MOMO_ENDPOINT') ?: 'https://test-payment.momo.vn/v2/gateway/api/create',
        'redirect_url' => getenv('MOMO_REDIRECT_URL') ?: '',
        'ipn_url' => getenv('MOMO_IPN_URL') ?: '',
    ];
}

