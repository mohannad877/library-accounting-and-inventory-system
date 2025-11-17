<?php

$env = [];
$envPath = dirname(__DIR__) . '/.env';
if (is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $env[$key] = $value;
    }
}

$host = getenv('DB_HOST') ?: ($env['DB_HOST'] ?? 'localhost');
$user = getenv('DB_USER') ?: ($env['DB_USER'] ?? 'root');
$password = getenv('DB_PASSWORD') ?: ($env['DB_PASSWORD'] ?? '');
$dbname = getenv('DB_NAME') ?: ($env['DB_NAME'] ?? 'schema');
$port = getenv('DB_PORT') ?: ($env['DB_PORT'] ?? 3306);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $user, $password, $dbname, (int)$port);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('حدث خطأ في الاتصال بقاعدة البيانات، يرجى المحاولة لاحقاً.');
}
