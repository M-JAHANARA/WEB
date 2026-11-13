<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
if (!empty($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
$error = '';
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = [];
$_SESSION['login_attempts'] = array_values(array_filter($_SESSION['login_attempts'], fn($t) => (int)$t > time() - 900));
$locked = count($_SESSION['login_attempts']) >= 6;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) $error = 'درخواست معتبر نیست. صفحه را تازه‌سازی کنید.';
    elseif ($locked) $error = 'تلاش‌های ناموفق بیش از حد بوده است. ۱۵ دقیقه دیگر دوباره تلاش کنید.';
    else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        try {
            $stmt = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($password, $admin['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int)$admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['login_attempts'] = [];
                header('Location: index.php'); exit;
            }
            $_SESSION['login_attempts'][] = time();
            $locked = count($_SESSION['login_attempts']) >= 6;
            $error = 'نام کاربری یا گذرواژه صحیح نیست.';
        } catch (Throwable $e) {
            error_log('Login error: ' . $e->getMessage());
            $error = 'اتصال به پایگاه داده برقرار نیست.';
        }
    }
}
?>
<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>ورود مدیر | <?= e(SHOP_NAME) ?></title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="login-page"><main class="login-card"><div class="login-brand"><a class="brand" href="../index.php"><span class="brand-mark"><span></span><span></span></span></a></div><h1><span class="brand-copy"><strong><?= e(SHOP_NAME) ?></strong></span></h1><p>پنل مدیریت</p>
<?php if (isset($_GET['setup'])): ?><div class="admin-alert admin-alert-success">حساب مدیر با موفقیت ساخته شد.</div><?php endif; ?>
<?php if ($error !== ''): ?><div class="admin-alert admin-alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" autocomplete="on"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="field-group"><label for="username">نام کاربری</label><input id="username" name="username" type="text" autocomplete="username" maxlength="80" required autofocus></div><div class="field-group"><label for="password">گذرواژه</label><input id="password" name="password" type="password" autocomplete="current-password" required></div><button class="button button-primary" type="submit" <?= $locked ? 'disabled' : '' ?>>ورود به پنل </button></form><p class="login-bottom"><span>Create By M.JAHANARA</span></p></main></body></html>
