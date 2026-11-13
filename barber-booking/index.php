<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

try {
    $services = db()->query('SELECT id, name, description, duration_minutes, price FROM services WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll();
} catch (Throwable $e) {
    http_response_code(500);
    $services = [];
    $dbError = true;
}
$successCode = isset($_GET['success'], $_GET['ref']) && $_GET['success'] === '1' ? preg_replace('/[^A-Z0-9]/', '', strtoupper((string)$_GET['ref'])) : '';
$bookingError = isset($_GET['error']) ? (string)$_GET['error'] : '';
$errors = [
    'slot_taken' => 'این ساعت به‌تازگی رزرو شده است. لطفاً زمان دیگری انتخاب کنید.',
    'invalid' => 'اطلاعات واردشده معتبر نیست. لطفاً فرم را بررسی کنید.',
    'server' => 'ثبت نوبت انجام نشد. لطفاً دوباره تلاش کنید.',
    'csrf' => 'درخواست شما منقضی شده است. صفحه را تازه‌سازی کنید.',
];
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#171b19">
  <meta name="description" content="رزرو آنلاین نوبت آرایشگاه؛ انتخاب خدمت، تاریخ و ساعت در چند مرحله ساده.">
  <title>رزرو نوبت | <?= e(SHOP_NAME) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/app.js" defer></script>
</head>
<body class="booking-page">
  <header class="site-header">
    <a class="brand" href="index.php" aria-label="صفحه اصلی">
      <span class="brand-mark" aria-hidden="true"><span></span><span></span></span>
      <span class="brand-copy"><strong><?= e(SHOP_NAME) ?></strong><small><?= e(SHOP_TAGLINE) ?></small></span>
    </a>
  </header>

  <main>
    <section class="hero section-shell">
      <div class="hero-copy">
        <span class="eyebrow"><span class="eyebrow-dot"></span> تجربه‌ای برای خودت</span>
        <h1>وقتشه کمی<br><em>به خودت برسی.</em></h1>
        <p>خدمت دلخواهت را انتخاب کن، زمان مناسب را پیدا کن و باقی مسیر را به ما بسپار.</p>
        <a class="button button-primary hero-cta" href="#booking">شروع رزرو</a>
      </div>
      <div class="hero-art" aria-label="فضایی گرم و مینیمال با حال‌وهوای آرایشگاه">
        <div class="art-glow"></div><div class="art-frame"><div class="art-circle"></div><div class="art-arch"></div><div class="art-chair"><i></i><b></b><span></span></div><div class="art-plant"><i></i><i></i><i></i><b></b></div></div>
        <div class="art-caption"><span>01 / YOUR TIME</span><span>CALM · CARE · STYLE</span></div>
      </div>
    </section>

    <section class="booking-section section-shell" id="booking">
      <div class="section-heading">
        <div><span class="eyebrow"></span><h2>ثبت درخواست نوبت </h2></div>
      </div>

      <?php if ($successCode !== ''): ?>
        <div class="notice notice-success" role="status">
          <span class="notice-symbol">✓</span><div><strong>درخواست نوبت ثبت شد.</strong><p>کد پیگیری شما <b><?= e($successCode) ?></b> است. نوبت پس از بررسی آرایشگاه تأیید می‌شود و پیامک نتیجه برایتان ارسال خواهد شد.</p></div>
        </div>
      <?php elseif ($bookingError !== ''): ?>
        <div class="notice notice-error" role="alert"><span class="notice-symbol">!</span><div><strong>ثبت نوبت کامل نشد</strong><p><?= e($errors[$bookingError] ?? 'لطفاً اطلاعات را بررسی و دوباره تلاش کنید.') ?></p></div></div>
      <?php endif; ?>

      <?php if (!empty($dbError)): ?>
        <div class="notice notice-error" role="alert"><span class="notice-symbol">!</span><div><strong>اتصال پایگاه داده برقرار نیست.</strong><p>ابتدا راهنمای README را دنبال کنید و فایل database/schema.sql را در MySQL اجرا کنید.</p></div></div>
      <?php else: ?>
      <form id="booking-form" class="booking-layout" action="book.php" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="booking-main">
          <section class="form-card" aria-labelledby="service-title">
            <div class="card-heading"><span class="step-number">۱</span><div><h3 id="service-title">خدمت موردنظرت</h3></div></div>
            <div class="service-grid">
              <?php foreach ($services as $index => $service): ?>
                <label class="service-option">
                  <input type="radio" name="service_id" value="<?= (int)$service['id'] ?>" data-name="<?= e($service['name']) ?>" data-duration="<?= (int)$service['duration_minutes'] ?>" data-price="<?= (int)$service['price'] ?>" <?= $index === 0 ? 'checked' : '' ?> required>
                  <span class="service-card"><span class="service-icon" aria-hidden="true"></span><span class="service-name"><?= e($service['name']) ?></span><span class="service-desc"><?= e($service['description']) ?></span><span class="service-foot"><b><?= toPersianDigits(number_format((int)$service['price'])) ?> <small>تومان</small></b><small><?= toPersianDigits((int)$service['duration_minutes']) ?> دقیقه</small></span><span class="service-check" aria-hidden="true">✓</span></span>
                </label>
              <?php endforeach; ?>
              <?php if (!$services): ?><p class="empty-state">فعلاً خدمتی برای رزرو تعریف نشده است.</p><?php endif; ?>
            </div>
          </section>

          <section class="form-card" aria-labelledby="datetime-title">
            <div class="card-heading"><span class="step-number">۲</span><div><h3 id="datetime-title">روز و ساعت مراجعه</h3></div></div>
            <div class="date-field"><label for="booking-date">تاریخ مراجعه</label><input id="booking-date" name="booking_date" type="date" data-min-date="<?= e((new DateTimeImmutable('today', new DateTimeZone(APP_TIMEZONE)))->format('Y-m-d')) ?>" data-max-date="<?= e((new DateTimeImmutable('today', new DateTimeZone(APP_TIMEZONE)))->modify('+' . BOOKING_DAYS_AHEAD . ' days')->format('Y-m-d')) ?>" required></div>
            <div class="time-area"><div class="time-area-heading"><label>ساعت‌های آزاد</label><span id="availability-count" class="muted-text"></span></div><div id="time-slots" class="time-slots" aria-live="polite"><div class="slot-placeholder">برای دیدن زمان‌های آزاد، یک تاریخ انتخاب کن.</div></div><input type="hidden" name="booking_time" id="booking-time" required></div>
          </section>

          <section class="form-card" aria-labelledby="customer-title">
            <div class="card-heading"><span class="step-number">۳</span><div><h3 id="customer-title">اطلاعات تماس</h3></div></div>
            <div class="customer-fields"><div class="field-group"><label for="customer-name">نام و نام خانوادگی</label><input id="customer-name" name="customer_name" type="text" minlength="2" maxlength="120" placeholder="علی رضایی" autocomplete="name" required></div><div class="field-group"><label for="customer-phone">شماره موبایل</label><input id="customer-phone" name="customer_phone" type="tel" inputmode="numeric" maxlength="11" placeholder="09xxxxxxxxx" autocomplete="tel" required></div></div>
            <label class="consent-line"><input type="checkbox" name="consent" value="1" required><span>اطلاعات را بررسی کرده‌ام و برای پیگیری نوبت، دریافت پیامک مربوط به وضعیت رزرو را می‌پذیرم.</span></label>
          </section>
        </div>

        <aside class="booking-summary">
          <h3>تکمیل فرآیند ثبت درخواست</h3><p class="summary-intro">قبل از ثبت، جزئیات نوبتت را مرور کن.</p>
          <div class="summary-line"><span>خدمت</span><strong id="summary-service"><?= e($services[0]['name'] ?? 'انتخاب نشده') ?></strong></div>
          <div class="summary-line"><span>مدت زمان</span><strong id="summary-duration"><?= isset($services[0]) ? (int)$services[0]['duration_minutes'] . ' دقیقه' : '—' ?></strong></div>
          <div class="summary-line"><span>تاریخ</span><strong id="summary-date">انتخاب نشده</strong></div>
          <div class="summary-line"><span>ساعت</span><strong id="summary-time">انتخاب نشده</strong></div>
          <div class="summary-total"><span>مبلغ خدمت</span><strong><span id="summary-price"><?= isset($services[0]) ? number_format((int)$services[0]['price']) : '—' ?></span> <small>تومان</small></strong></div>
          <button class="button button-primary submit-booking" type="submit" <?= !$services ? 'disabled' : '' ?>>ثبت درخواست نوبت</button>
          <p class="summary-note"><span>ⓘ</span> نوبت ابتدا در انتظار تأیید می‌ماند. پس از تأیید ، پیامک برای شما ارسال می‌شود.</p>
        </aside>
      </form>
      <?php endif; ?>
    </section>

    <section class="closing-note section-shell"><span class="closing-rule" style="transform: scaleX(-1);"></span><p>گاهی یک تغییر کوچک، حال‌وهوای تازه‌ای می‌سازد.</p><span class="closing-rule"></span></section>
  </main>
  <footer class="site-footer"><span>تمامی حقوق متعلق به  <span style="color:var(--sage)"><?= e(SHOP_NAME)?></span> است.</span><span> Create By <?= e(DEVELOPER_NAME) ?> - <a href="mailto:<?= e(DEVELOPER_EMAIL) ?>" style="color:var(--sage)"><?= e(DEVELOPER_EMAIL) ?></a></span></footer>
</body>
</html>
