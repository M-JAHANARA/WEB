<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

final class SmsService
{
    /** @return array{sent:bool,message:string} */
    public static function sendAppointmentStatus(array $appointment, string $status): array
    {
        if (!in_array($status, ['confirmed', 'cancelled'], true)) {
            return ['sent' => false, 'message' => 'برای این وضعیت پیامکی تعریف نشده است.'];
        }

        $service = (string)($appointment['service_name'] ?? 'خدمت آرایشگاه');
        $date = fa_datetime((string)$appointment['starts_at'], false);
        $time = (new DateTimeImmutable((string)$appointment['starts_at'], new DateTimeZone(APP_TIMEZONE)))->format('H:i');
        $shop = SHOP_NAME;

        if ($status === 'confirmed') {
            $message = "{$shop}\nنوبت شما تأیید شد.\nخدمت: {$service}\nتاریخ: {$date}\nساعت: {$time}\nمنتظر دیدار شما هستیم.";
        } else {
            $message = "{$shop}\nنوبت شما لغو شد.\nخدمت: {$service}\nتاریخ: {$date}\nساعت: {$time}\nبرای رزرو مجدد از سامانه نوبت‌دهی اقدام کنید.";
        }

        if (KAVENEGAR_API_KEY === '') {
            self::log('SMS is not configured; a status notification was not sent. Status=' . $status . '.');
            return ['sent' => false, 'message' => 'وضعیت ذخیره شد؛ ارسال پیامک فعال نیست. کلید API را در app/config.php تنظیم کنید.'];
        }

        if (!function_exists('curl_init')) {
            self::log('PHP cURL extension is not enabled.');
            return ['sent' => false, 'message' => 'وضعیت ذخیره شد؛ افزونه cURL در PHP فعال نیست.'];
        }

        $url = 'https://api.kavenegar.com/v1/' . rawurlencode(KAVENEGAR_API_KEY) . '/sms/send.json';
        $payload = [
            'receptor' => (string)$appointment['customer_phone'],
            'message' => $message,
        ];
        if (KAVENEGAR_SENDER !== '') $payload['sender'] = KAVENEGAR_SENDER;

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded; charset=UTF-8'],
        ]);
        $body = curl_exec($curl);
        $curlError = curl_error($curl);
        $httpCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($body === false || $httpCode < 200 || $httpCode >= 300) {
            self::log('SMS HTTP error: ' . $httpCode . ' ' . $curlError . ' ' . (is_string($body) ? $body : ''));
            return ['sent' => false, 'message' => 'وضعیت ذخیره شد؛ ارسال پیامک ناموفق بود. اتصال و اعتبار API را بررسی کنید.'];
        }

        $decoded = json_decode((string)$body, true);
        $apiStatus = (int)($decoded['return']['status'] ?? 0);
        if ($apiStatus !== 200) {
            self::log('SMS provider rejected request: ' . (string)$body);
            return ['sent' => false, 'message' => 'وضعیت ذخیره شد؛ سرویس پیامکی درخواست را نپذیرفت.'];
        }

        self::log('SMS accepted by provider. Recipient=' . $appointment['customer_phone'] . '; status=' . $status);
        return ['sent' => true, 'message' => 'پیامک به سرویس پیامکی تحویل داده شد.'];
    }

    private static function log(string $line): void
    {
        $dir = __DIR__ . '/../storage/logs';
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        @file_put_contents($dir . '/sms.log', '[' . date('c') . '] ' . $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
