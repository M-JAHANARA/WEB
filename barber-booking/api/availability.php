<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$serviceId = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT);
$date = normalize_digits((string)($_GET['date'] ?? ''));
if (!$serviceId || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    json_response(['ok' => false, 'message' => 'تاریخ یا خدمت معتبر نیست.', 'slots' => []], 422);
}

try {
    $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone(APP_TIMEZONE));
    $errors = DateTimeImmutable::getLastErrors();
    if (!$day || ($errors && ($errors['warning_count'] || $errors['error_count'])) || $day->format('Y-m-d') !== $date) {
        json_response(['ok' => false, 'message' => 'تاریخ معتبر نیست.', 'slots' => []], 422);
    }
    $today = new DateTimeImmutable('today', new DateTimeZone(APP_TIMEZONE));
    $last = $today->modify('+' . BOOKING_DAYS_AHEAD . ' days');
    if ($day < $today || $day > $last) {
        json_response(['ok' => false, 'message' => 'این تاریخ خارج از بازه مجاز رزرو است.', 'slots' => []], 422);
    }

    $pdo = db();
    $serviceStmt = $pdo->prepare('SELECT duration_minutes FROM services WHERE id = ? AND is_active = 1 LIMIT 1');
    $serviceStmt->execute([$serviceId]);
    $service = $serviceStmt->fetch();
    if (!$service) json_response(['ok' => false, 'message' => 'خدمت انتخاب‌شده در دسترس نیست.', 'slots' => []], 404);

    $startOfDay = $date . ' 00:00:00';
    $endOfDay = $date . ' 23:59:59';
    $busyStmt = $pdo->prepare("SELECT starts_at, ends_at FROM appointments WHERE starts_at <= ? AND ends_at > ? AND status IN ('pending','confirmed','completed')");
    $busyStmt->execute([$endOfDay, $startOfDay]);
    $busy = $busyStmt->fetchAll();

    $opening = new DateTimeImmutable($date . ' ' . OPENING_TIME, new DateTimeZone(APP_TIMEZONE));
    $closing = new DateTimeImmutable($date . ' ' . CLOSING_TIME, new DateTimeZone(APP_TIMEZONE));
    $duration = (int)$service['duration_minutes'];
    $now = new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE));
    $slots = [];
    for ($cursor = $opening; $cursor->modify('+' . $duration . ' minutes') <= $closing; $cursor = $cursor->modify('+' . SLOT_INTERVAL_MINUTES . ' minutes')) {
        $slotEnd = $cursor->modify('+' . $duration . ' minutes');
        if ($cursor <= $now) continue;
        $conflict = false;
        foreach ($busy as $item) {
            $busyStart = new DateTimeImmutable($item['starts_at'], new DateTimeZone(APP_TIMEZONE));
            $busyEnd = new DateTimeImmutable($item['ends_at'], new DateTimeZone(APP_TIMEZONE));
            if ($cursor < $busyEnd && $slotEnd > $busyStart) { $conflict = true; break; }
        }
        if (!$conflict) $slots[] = ['value' => $cursor->format('H:i'), 'label' => $cursor->format('H:i')];
    }
    json_response(['ok' => true, 'slots' => $slots, 'message' => $slots ? 'زمان‌های آزاد برای انتخاب آماده‌اند.' : 'برای این تاریخ زمان آزادی پیدا نشد.']);
} catch (Throwable $e) {
    error_log('Availability error: ' . $e->getMessage());
    json_response(['ok' => false, 'message' => 'بررسی زمان‌های آزاد با خطا مواجه شد.', 'slots' => []], 500);
}
