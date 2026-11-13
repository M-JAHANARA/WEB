<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_admin();



$pdo = db();

$today = (new DateTimeImmutable('today', new DateTimeZone(APP_TIMEZONE)))->format('Y-m-d');

$statsStmt = $pdo->prepare("SELECT COUNT(*) AS today_total, SUM(status='pending') AS pending_total, SUM(status='confirmed') AS confirmed_total FROM appointments WHERE DATE(starts_at) = ? AND status <> 'cancelled'");

$statsStmt->execute([$today]);

$stats = $statsStmt->fetch() ?: ['today_total'=>0,'pending_total'=>0,'confirmed_total'=>0];

$allActive = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE status IN ('pending','confirmed')")->fetchColumn();



$q = trim((string)($_GET['q'] ?? ''));

$dateFilter = normalize_digits((string)($_GET['date'] ?? ''));

$statusFilter = (string)($_GET['status'] ?? '');

$params = [];

$where = [];

if ($q !== '') {

    $where[] = '(a.customer_name LIKE ? OR a.customer_phone LIKE ? OR a.reference_code LIKE ? OR a.service_name LIKE ?)';

    $like = '%' . $q . '%'; array_push($params, $like, $like, $like, $like);

}

if ($dateFilter !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFilter)) { $where[] = 'DATE(a.starts_at) = ?'; $params[] = $dateFilter; }

if (in_array($statusFilter, ['pending','confirmed','cancelled','completed'], true)) { $where[] = 'a.status = ?'; $params[] = $statusFilter; }

$sql = 'SELECT a.* FROM appointments a' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY a.starts_at DESC, a.id DESC LIMIT 300';

$stmt = $pdo->prepare($sql); $stmt->execute($params); $appointments = $stmt->fetchAll();

$returnQuery = http_build_query(array_filter(['q'=>$q, 'date'=>$dateFilter, 'status'=>$statusFilter], fn($v) => $v !== ''));

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

?>

<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><meta name="theme-color" content="#171b19"><title>مدیریت نوبت‌ها | <?= e(SHOP_NAME) ?></title><link rel="stylesheet" href="../assets/css/style.css"><style>
.admin-sidebar .sidebar-bottom{display:flex;flex-direction:row;align-items:center;justify-content:flex-start;gap:10px;direction:rtl}
.admin-sidebar .sidebar-bottom form{margin:0;padding:0;display:flex;align-items:center}
.admin-sidebar .sidebar-bottom .logout-link{display:inline-flex;align-items:center;justify-content:center;width:auto;height:auto;min-width:0;min-height:0;margin:0;padding:0!important;background:transparent!important;border:0!important;border-radius:0!important;box-shadow:none!important;outline-offset:3px;color:inherit;appearance:none;-webkit-appearance:none}
.admin-sidebar .sidebar-bottom .logout-link:hover,.admin-sidebar .sidebar-bottom .logout-link:focus{background:transparent!important;box-shadow:none!important}
.admin-sidebar .sidebar-bottom .logout-icon{display:block;flex-shrink:0}
</style><script defer src="../assets/js/admin.js"></script></head>

<body class="admin-body"><div class="admin-shell">

<aside class="admin-sidebar"><div class="admin-brand"><a class="brand" href="../index.php"><span class="brand-mark"><span></span><span></span></span><span class="brand-copy"><strong><?= e(SHOP_NAME) ?></strong><small>پنل مدیریت</small></span></a></div><nav class="admin-nav">
<a class="<?= $statusFilter === '' ? 'active' : '' ?>" href="index.php">نوبت‌ها</a>
<a class="<?= $statusFilter === 'pending' ? 'active' : '' ?>" href="index.php?status=pending">در انتظار تأیید</a>
<a class="<?= $statusFilter === 'confirmed' ? 'active' : '' ?>" href="index.php?status=confirmed">نوبت‌های تأییدشده</a>
</nav>
<div class="sidebar-bottom">
  <form action="logout.php" method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <button class="logout-link" type="submit" aria-label="خروج">
      <svg class="logout-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <path d="M16 17l5-5-5-5"/>
        <path d="M21 12H9"/>
      </svg>
    </button>
  </form>
  <span class="admin-user-copy">سلام <?= e($_SESSION['admin_username'] ?? 'مدیر') ?></span>
</div></aside>

<main class="admin-main"><header class="admin-topbar">
  <div class="admin-title-group">
    <h1><?= $statusFilter === 'pending' ? 'در انتظار تأیید' : ($statusFilter === 'confirmed' ? 'نوبت‌های تأییدشده' : 'نوبت‌ها') ?></h1>
  </div>
</header>

<?php if (is_array($flash)): ?><div class="admin-alert <?= e('admin-alert-' . ($flash['type'] ?? 'success')) ?>" role="status"><?= e($flash['message'] ?? '') ?></div><?php endif; ?>

<section class="stats-grid"><article class="stat-card"><div class="stat-head"><span>نوبت‌های امروز</span></div><strong><?= number_format((int)($stats['today_total'] ?? 0)) ?></strong><small>نوبت فعال در تاریخ امروز</small></article><article class="stat-card"><div class="stat-head"><span>در انتظار تأیید</span></div><strong><?= number_format((int)($stats['pending_total'] ?? 0)) ?></strong><small>نیازمند بررسی مدیریت</small></article><article class="stat-card"><div class="stat-head"><span>تأیید شده امروز</span></div><strong><?= number_format((int)($stats['confirmed_total'] ?? 0)) ?></strong><small>رزرو قطعی امروز</small></article><article class="stat-card"><div class="stat-head"><span>نوبت‌های فعال</span></div><strong><?= number_format($allActive) ?></strong><small>در انتظار تأیید یا تأیید شده</small></article></section>

<section class="filter-panel"><form class="filter-form" method="get"><div class="filter-field"><label for="q">جست‌وجو</label><input id="q" name="q" value="<?= e($q) ?>" placeholder="نام، موبایل، کد پیگیری یا خدمت"></div><div class="filter-field"><label for="date">تاریخ مراجعه</label><input id="date" name="date" type="date" value="<?= e($dateFilter) ?>"></div><div class="filter-field"><label for="status">وضعیت نوبت</label><select id="status" name="status"><option value="">همه وضعیت‌ها</option><?php foreach (['pending','confirmed','cancelled','completed'] as $status): ?><option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= e(status_label($status)) ?></option><?php endforeach; ?></select></div><div class="filter-actions"><button class="admin-button admin-button-primary" type="submit">جست‌وجو</button><a class="admin-button" href="index.php">پاک‌کردن</a></div></form></section>

<section class="table-panel"><div class="table-panel-head"><div><h2>فهرست نوبت‌ها</h2><p>جزئیات را باز کن یا وضعیت هر نوبت را تغییر بده.</p></div><span class="result-count"><?= number_format(count($appointments)) ?> مورد<?= count($appointments) === 300 ? ' (نمایش حداکثر ۳۰۰ مورد)' : '' ?></span></div>

<?php if (!$appointments): ?><div class="admin-empty"><div class="empty-icon">◌</div><strong>نوبتی مطابق جست‌وجوی شما پیدا نشد.</strong><p>فیلترها را تغییر بده یا بعداً دوباره بررسی کن.</p></div><?php else: ?><div class="table-wrap"><table><thead><tr><th>مشتری</th><th>خدمت</th><th>تاریخ و ساعت</th><th>وضعیت</th><th>جزئیات / عملیات</th></tr></thead><tbody>

<?php foreach ($appointments as $a): $detailId = 'detail-' . (int)$a['id']; ?><tr><td><div class="customer-cell"><span class="customer-avatar"><?= e(utf8_substr($a['customer_name'], 0, 1)) ?></span><span class="customer-data"><b><?= e($a['customer_name']) ?></b><small><?= e($a['customer_phone']) ?></small></span></div></td><td><span class="service-cell"><b><?= e($a['service_name']) ?></b><small><?= number_format((int)$a['service_price']) ?> تومان · <?= (int)$a['duration_minutes'] ?> دقیقه</small></span></td><td class="datetime-cell"><span><?= e(fa_datetime($a['starts_at'], false)) ?></span><small><?= e((new DateTimeImmutable($a['starts_at'], new DateTimeZone(APP_TIMEZONE)))->format('H:i')) ?> تا <?= e((new DateTimeImmutable($a['ends_at'], new DateTimeZone(APP_TIMEZONE)))->format('H:i')) ?></small></td><td><span class="status-pill <?= e(status_class($a['status'])) ?>"><?= e(status_label($a['status'])) ?></span></td><td><div class="row-actions"><button class="detail-toggle" type="button" data-detail-toggle="<?= e($detailId) ?>">جزئیات ↴</button>

<?php if ($a['status'] === 'pending'): ?><form action="actions.php" method="post" data-confirm="آیا نوبت را تأیید می‌کنی؟ پس از تأیید، پیامک برای مشتری ارسال می‌شود."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="new_status" value="confirmed"><input type="hidden" name="return_query" value="<?= e($returnQuery) ?>"><button class="admin-button admin-button-primary" type="submit">تأیید</button></form><form action="actions.php" method="post" data-confirm="نوبت لغو شود؟ برای مشتری پیامک لغو ارسال خواهد شد."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="new_status" value="cancelled"><input type="hidden" name="return_query" value="<?= e($returnQuery) ?>"><button class="admin-button admin-button-danger" type="submit">لغو</button></form>

<?php elseif ($a['status'] === 'confirmed'): ?><form action="actions.php" method="post" data-confirm="آیا خدمت انجام شده است؟"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="new_status" value="completed"><input type="hidden" name="return_query" value="<?= e($returnQuery) ?>"><button class="admin-button" type="submit">اتمام خدمت</button></form><form action="actions.php" method="post" data-confirm="نوبت لغو شود؟ برای مشتری پیامک لغو ارسال خواهد شد."><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>"><input type="hidden" name="new_status" value="cancelled"><input type="hidden" name="return_query" value="<?= e($returnQuery) ?>"><button class="admin-button admin-button-danger" type="submit">لغو</button></form><?php endif; ?></div></td></tr>

<tr id="<?= e($detailId) ?>" class="detail-row"><td colspan="5"><div class="detail-content"><div class="detail-item"><small>کد پیگیری</small><strong><?= e($a['reference_code']) ?></strong></div><div class="detail-item"><small>شماره موبایل</small><strong><?= e($a['customer_phone']) ?></strong></div><div class="detail-item"><small>زمان ثبت درخواست</small><strong><?= e(fa_datetime($a['created_at'])) ?></strong></div><div class="detail-item"><small>مبلغ خدمت</small><strong><?= number_format((int)$a['service_price']) ?> تومان</strong></div><div class="detail-item"><small>وضعیت فعلی</small><strong><?= e(status_label($a['status'])) ?></strong></div><div class="detail-item"><small>مدت رزرو</small><strong><?= (int)$a['duration_minutes'] ?> دقیقه</strong></div></div></td></tr>

<?php endforeach; ?></tbody></table></div><?php endif; ?></section>

<footer class="admin-footer"><span>تمامی حقوق متعلق به  <span style="color:var(--sage)"><?= e(SHOP_NAME)?></span> است.</span><span> Create By <?= e(DEVELOPER_NAME) ?> - <a href="mailto:<?= e(DEVELOPER_EMAIL) ?>" style="color:var(--sage)"><?= e(DEVELOPER_EMAIL) ?></a></span></footer>

</main></div></body></html>
