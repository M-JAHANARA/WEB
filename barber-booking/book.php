<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}
if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    header('Location: index.php?error=csrf#booking'); exit;
}

$name = trim((string)($_POST['customer_name'] ?? ''));
$phone = normalize_phone((string)($_POST['customer_phone'] ?? ''));
$serviceId = filter_var($_POST['service_id'] ?? null, FILTER_VALIDATE_INT);
$date = normalize_digits((string)($_POST['booking_date'] ?? ''));
$time = normalize_digits((string)($_POST['booking_time'] ?? ''));
$consent = (string)($_POST['consent'] ?? '') === '1';

if (utf8_length($name) < 2 || utf8_length($name) > 120 || !valid_phone($phone) || !$serviceId || !$consent || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time)) {
    header('Location: index.php?error=invalid#booking'); exit;
}

$zone = new DateTimeZone(APP_TIMEZONE);
$day = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $zone);
$parseErrors = DateTimeImmutable::getLastErrors();
$today = new DateTimeImmutable('today', $zone);
if (!$day || ($parseErrors && ($parseErrors['warning_count'] || $parseErrors['error_count'])) || $day->format('Y-m-d') !== $date || $day < $today || $day > $today->modify('+' . BOOKING_DAYS_AHEAD . ' days')) {
    header('Location: index.php?error=invalid#booking'); exit;
}

$start = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, $zone);
if (!$start || $start->format('Y-m-d H:i') !== $date . ' ' . $time || $start->format('i') % SLOT_INTERVAL_MINUTES !== 0) {
    header('Location: index.php?error=invalid#booking'); exit;
}
$opening = new DateTimeImmutable($date . ' ' . OPENING_TIME, $zone);
$closing = new DateTimeImmutable($date . ' ' . CLOSING_TIME, $zone);
if ($start < $opening || $start <= new DateTimeImmutable('now', $zone)) {
    header('Location: index.php?error=invalid#booking'); exit;
}

$pdo = null;
$lockName = 'barber_booking_' . str_replace('-', '', $date);
$gotLock = false;
try {
    $pdo = db();
    // تمام رزروهای یک روز را کوتاه‌مدت سریالی می‌کند تا دو درخواست هم‌زمان یک بازه را تصاحب نکنند.
    $lockStmt = $pdo->prepare('SELECT GET_LOCK(?, 8)');
    $lockStmt->execute([$lockName]);
    $gotLock = (int)$lockStmt->fetchColumn() === 1;
    if (!$gotLock) {
        header('Location: index.php?error=server#booking'); exit;
    }

    $pdo->beginTransaction();
    $serviceStmt = $pdo->prepare('SELECT id, name, duration_minutes, price FROM services WHERE id = ? AND is_active = 1 FOR UPDATE');
    $serviceStmt->execute([$serviceId]);
    $service = $serviceStmt->fetch();
    if (!$service) {
        $pdo->rollBack(); header('Location: index.php?error=invalid#booking'); exit;
    }
    $end = $start->modify('+' . (int)$service['duration_minutes'] . ' minutes');
    if ($end > $closing) {
        $pdo->rollBack(); header('Location: index.php?error=invalid#booking'); exit;
    }

    $conflictStmt = $pdo->prepare("SELECT id FROM appointments WHERE starts_at < ? AND ends_at > ? AND status IN ('pending','confirmed','completed') LIMIT 1 FOR UPDATE");
    $conflictStmt->execute([$end->format('Y-m-d H:i:s'), $start->format('Y-m-d H:i:s')]);
    if ($conflictStmt->fetch()) {
        $pdo->rollBack(); header('Location: index.php?error=slot_taken#booking'); exit;
    }

    $reference = strtoupper(bin2hex(random_bytes(4)));
    $insert = $pdo->prepare('INSERT INTO appointments (reference_code, customer_name, customer_phone, service_id, service_name, service_price, duration_minutes, starts_at, ends_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, \'pending\')');
    $insert->execute([$reference, $name, $phone, $service['id'], $service['name'], $service['price'], $service['duration_minutes'], $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
    $pdo->commit();
    header('Location: index.php?success=1&ref=' . rawurlencode($reference) . '#booking'); exit;
} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Booking error: ' . $e->getMessage());
    header('Location: index.php?error=server#booking'); exit;
} finally {
    if ($gotLock && $pdo instanceof PDO) {
        try { $release = $pdo->prepare('SELECT RELEASE_LOCK(?)'); $release->execute([$lockName]); } catch (Throwable) {}
    }
}
