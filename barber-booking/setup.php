<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$message = '';
$done = false;
try {
    $adminCount = (int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    $done = $adminCount > 0;
} catch (Throwable $e) {
    http_response_code(500);
    $message = 'ابتدا فایل database/schema.sql را در phpMyAdmin اجرا کنید و سپس تنظیمات اتصال دیتابیس را در app/config.php بررسی کنید.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$done && $message === '') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $message = 'درخواست منقضی شده است. صفحه را تازه‌سازی کنید.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $passwordConfirm = (string)($_POST['password_confirm'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_.-]{4,80}$/', $username)) {
            $message = 'نام کاربری باید حداقل ۴ کاراکتر و شامل حروف انگلیسی، عدد یا . _ - باشد.';
        } elseif (strlen($password) < 10) {
            $message = 'گذرواژه باید دست‌کم ۱۰ کاراکتر داشته باشد.';
        } elseif ($password !== $passwordConfirm) {
            $message = 'تکرار گذرواژه با گذرواژه یکسان نیست.';
        } else {
            $pdo = null;
            $setupLock = false;
            try {
                $pdo = db();
                // قفل نام‌دار مانع ساخت هم‌زمان چند حساب مدیر در دو درخواست نصب می‌شود.
                $lock = $pdo->prepare('SELECT GET_LOCK(?, 5)');
                $lock->execute(['barber_admin_setup']);
                $setupLock = (int)$lock->fetchColumn() === 1;
                if (!$setupLock) {
                    $message = 'فرایند راه‌اندازی دیگری در حال اجراست؛ دوباره تلاش کنید.';
                } else {
                    $count = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
                    if ($count > 0) {
                        $done = true;
                    } else {
                        $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
                        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
                        $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
                        $release->execute(['barber_admin_setup']);
                        $setupLock = false;
                        header('Location: admin/login.php?setup=1'); exit;
                    }
                }
            } catch (Throwable $e) {
                error_log('Admin setup error: ' . $e->getMessage());
                $message = 'ساخت حساب مدیر انجام نشد؛ اطلاعات دیتابیس را بررسی کنید.';
            } finally {
                if ($setupLock && $pdo instanceof PDO) {
                    try { $release = $pdo->prepare('SELECT RELEASE_LOCK(?)'); $release->execute(['barber_admin_setup']); } catch (Throwable) {}
                }
            }
        }
    }
}
?>
<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>راه‌اندازی مدیریت | <?= e(SHOP_NAME) ?></title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="login-page"><main class="login-card setup-card"><div class="login-brand"><a class="brand" href="index.php"><span class="brand-mark"><span></span><span></span></span><span class="brand-copy"><strong><?= e(SHOP_NAME) ?></strong><small>راه‌اندازی اولیه</small></span></a></div>
<h1>ساخت حساب مدیر</h1><p>فقط یک بار برای ایجاد دسترسی امن پنل مدیریت استفاده می‌شود.</p>
<?php if ($message !== ''): ?><div class="admin-alert admin-alert-error"><?= e($message) ?></div><?php endif; ?>
<?php if ($done): ?><div class="notice notice-success"><span class="notice-symbol">✓</span><div><strong>حساب مدیر قبلاً ساخته شده است.</strong><p>برای ورود به پنل از صفحه ورود استفاده کنید.</p></div></div><a class="button button-primary" href="admin/login.php">رفتن به صفحه ورود</a>
<?php else: ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="field-group"><label for="username">نام کاربری مدیر</label><input id="username" name="username" autocomplete="username" minlength="4" maxlength="80" required></div><div class="field-group"><label for="password">گذرواژه (حداقل ۱۰ کاراکتر)</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required></div><div class="field-group"><label for="password_confirm">تکرار گذرواژه</label><input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="10" required></div><button class="button button-primary" type="submit">ایجاد حساب مدیر <span>←</span></button></form><?php endif; ?>
<p class="login-bottom">پس از راه‌اندازی، فایل نصب امکان ساخت مدیر دیگری را نمی‌دهد.</p></main></body></html>
