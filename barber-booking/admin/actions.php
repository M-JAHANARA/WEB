<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/SmsService.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? null)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'درخواست معتبر نیست یا منقضی شده است.'];
    header('Location: index.php'); exit;
}
$id = filter_var($_POST['appointment_id'] ?? null, FILTER_VALIDATE_INT);
$newStatus = (string)($_POST['new_status'] ?? '');
$returnQuery = (string)($_POST['return_query'] ?? '');
$allowedStatuses = ['confirmed', 'cancelled', 'completed'];
if (!$id || !in_array($newStatus, $allowedStatuses, true)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'عملیات درخواستی معتبر نیست.'];
    header('Location: index.php'); exit;
}

try {
    $pdo = db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT a.*, a.service_name AS joined_service_name FROM appointments a WHERE a.id = ? FOR UPDATE');
    $stmt->execute([$id]);
    $appointment = $stmt->fetch();
    if (!$appointment) {
        $pdo->rollBack();
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'نوبت موردنظر پیدا نشد.'];
        header('Location: index.php'); exit;
    }
    $current = (string)$appointment['status'];
    $transitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['cancelled', 'completed'],
        'cancelled' => [],
        'completed' => [],
    ];
    if (!in_array($newStatus, $transitions[$current] ?? [], true)) {
        $pdo->rollBack();
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'این تغییر وضعیت مجاز نیست.'];
        header('Location: index.php'); exit;
    }
    // قبل از آزاد کردن یک نوبت، مطمئن می‌شویم وضعیت آن هنوز توسط مدیر دیگری تغییر نکرده باشد.
    $update = $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?');
    $update->execute([$newStatus, $id]);
    $pdo->commit();

    if (in_array($newStatus, ['confirmed', 'cancelled'], true)) {
        $appointment['service_name'] = $appointment['joined_service_name'];
        $sms = SmsService::sendAppointmentStatus($appointment, $newStatus);
        if ($sms['sent']) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'وضعیت نوبت به «' . status_label($newStatus) . '» تغییر کرد. ' . $sms['message']];
        } else {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'وضعیت نوبت به «' . status_label($newStatus) . '» تغییر کرد؛ اما ' . $sms['message']];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'وضعیت نوبت به «انجام شده» تغییر کرد.'];
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Appointment action error: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'ذخیره تغییرات انجام نشد. گزارش خطای سرور را بررسی کنید.'];
}
$redirect = 'index.php';
if ($returnQuery !== '' && preg_match('/^[a-zA-Z0-9%&_+=-]{1,1000}$/', $returnQuery)) $redirect .= '?' . $returnQuery;
header('Location: ' . $redirect); exit;
