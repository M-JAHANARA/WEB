<?php
declare(strict_types=1);

/*
 * تنظیمات پروژه نوبت‌دهی آرایشگاه
 * بعد از ساخت دیتابیس، مقادیر اتصال را متناسب با سیستم خود تغییر دهید.
 */
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'barber_booking');
define('DB_USER', 'root');
define('DB_PASS', '');

// منطقه زمانی کسب‌وکار؛ در صورت نیاز به Asia/Baku یا منطقه دیگری تغییر دهید.
define('APP_TIMEZONE', 'Asia/Tehran');

define('SHOP_NAME', 'آرایشگاه مردانه باربر');
define('SHOP_TAGLINE', 'وقتِ رسیدگی به خودت');
define('SHOP_PHONE', '09177507395'); // شماره تماس آرایشگاه، اختیاری

define('DEVELOPER_NAME', 'M.JAHANARA');
define('DEVELOPER_EMAIL', 'm.jahanara1.19.2010@gmail.com'); // این مقدار را با Gmail واقعی خود جایگزین کنید.

// برای ارسال پیامک از Kavenegar استفاده می‌شود. بدون API Key پیامک ارسال نمی‌شود.
define('KAVENEGAR_API_KEY', '');
define('KAVENEGAR_SENDER', ''); // در صورت نیاز، خط ارسال اختصاصی

// ساعات کاری روزانه (قابل تغییر)
define('OPENING_TIME', '09:00');
define('CLOSING_TIME', '21:00');
define('BOOKING_DAYS_AHEAD', 30);
define('SLOT_INTERVAL_MINUTES', 30);
