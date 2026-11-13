<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';

date_default_timezone_set(APP_TIMEZONE);

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function toPersianDigits(string|int $number): string {
    return strtr((string)$number, [
        '0' => '۰', '1' => '۱', '2' => '۲',
        '3' => '۳', '4' => '۴', '5' => '۵',
        '6' => '۶', '7' => '۷', '8' => '۸',
        '9' => '۹'
    ]);
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function utf8_length(string $value): int
{
    if (function_exists('mb_strlen')) return mb_strlen($value, 'UTF-8');
    preg_match_all('/./us', $value, $matches);
    return count($matches[0] ?? []);
}

function utf8_substr(string $value, int $offset, int $length = 1): string
{
    if (function_exists('mb_substr')) return mb_substr($value, $offset, $length, 'UTF-8');
    preg_match_all('/./us', $value, $matches);
    return implode('', array_slice($matches[0] ?? [], $offset, $length));
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['_csrf'];
}

function verify_csrf(?string $token): bool
{
    return is_string($token) && isset($_SESSION['_csrf']) && hash_equals((string)$_SESSION['_csrf'], $token);
}

function require_admin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function normalize_digits(string $value): string
{
    return strtr($value, [
        '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
    ]);
}

function normalize_phone(string $phone): string
{
    $phone = normalize_digits(trim($phone));
    return preg_replace('/[\s\-()]+/', '', $phone) ?? '';
}

function valid_phone(string $phone): bool
{
    // پیش‌فرض متناسب با شماره موبایل ایران؛ برای بازار دیگر، این الگو را در همین تابع تغییر دهید.
    return (bool) preg_match('/^09\d{9}$/', $phone);
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fa_datetime(string $value, bool $withTime = true): string
{
    try {
        $date = new DateTimeImmutable($value, new DateTimeZone(APP_TIMEZONE));
        if (class_exists(IntlDateFormatter::class)) {
            $pattern = $withTime ? 'yyyy/MM/dd - HH:mm' : 'yyyy/MM/dd';
            $formatter = new IntlDateFormatter('fa_IR@calendar=persian', IntlDateFormatter::NONE, IntlDateFormatter::NONE, APP_TIMEZONE, IntlDateFormatter::TRADITIONAL, $pattern);
            $formatted = $formatter->format($date);
            if ($formatted !== false) return (string)$formatted;
        }
        return $date->format($withTime ? 'Y/m/d - H:i' : 'Y/m/d');
    } catch (Throwable) {
        return $value;
    }
}

function status_label(string $status): string
{
    return match ($status) {
        'pending' => 'در انتظار تأیید',
        'confirmed' => 'تأیید شده',
        'cancelled' => 'لغو شده',
        'completed' => 'انجام شده',
        default => 'نامشخص',
    };
}

function status_class(string $status): string
{
    return match ($status) {
        'pending' => 'status-pending',
        'confirmed' => 'status-confirmed',
        'cancelled' => 'status-cancelled',
        'completed' => 'status-completed',
        default => 'status-pending',
    };
}
