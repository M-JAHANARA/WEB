<?php

require_once "./db.php";

header("Content-Type: application/json; charset=UTF-8");


/*
|--------------------------------------------------------------------------
| فقط POST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "درخواست نامعتبر است."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| دریافت اطلاعات
|--------------------------------------------------------------------------
*/

$mobile = isset($_POST["mobile"])
    ? trim($_POST["mobile"])
    : "";

$rating = isset($_POST["rating"])
    ? (int) $_POST["rating"]
    : 0;

$comment = isset($_POST["comment"])
    ? trim($_POST["comment"])
    : "";


/*
|--------------------------------------------------------------------------
| بررسی شماره موبایل
|--------------------------------------------------------------------------
*/

if (!preg_match("/^09[0-9]{9}$/", $mobile)) {

    echo json_encode([
        "success" => false,
        "message" => "شماره موبایل وارد شده صحیح نیست."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| بررسی امتیاز
|--------------------------------------------------------------------------
*/

if ($rating < 1 || $rating > 5) {

    echo json_encode([
        "success" => false,
        "message" => "لطفاً امتیاز خود را انتخاب کنید."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| بررسی متن
|--------------------------------------------------------------------------
*/

if ($comment === "") {

    echo json_encode([
        "success" => false,
        "message" => "لطفاً متن نظر را وارد کنید."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


$commentLength = mb_strlen(
    $comment,
    "UTF-8"
);


if ($commentLength < 3) {

    echo json_encode([
        "success" => false,
        "message" => "نظر باید حداقل ۳ کاراکتر باشد."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


if ($commentLength > 1000) {

    echo json_encode([
        "success" => false,
        "message" => "نظر نمی‌تواند بیشتر از ۱۰۰۰ کاراکتر باشد."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| محدودیت اول:
| حداکثر ۳ نظر در ۲۴ ساعت برای هر شماره
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM comments
    WHERE mobile = ?
      AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
");


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "خطا در بررسی محدودیت ارسال."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


$stmt->bind_param(
    "s",
    $mobile
);


$stmt->execute();


$result =
    $stmt->get_result();


$row =
    $result->fetch_assoc();


$stmt->close();


$last24Hours =
    (int) ($row["total"] ?? 0);


if ($last24Hours >= 3) {

    echo json_encode([
        "success" => false,
        "message" =>
            "این شماره موبایل در ۲۴ ساعت گذشته به حداکثر تعداد مجاز نظرات رسیده است."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


/*
|--------------------------------------------------------------------------
| محدودیت دوم:
| حداقل ۵ دقیقه بین دو ارسال
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT created_at
    FROM comments
    WHERE mobile = ?
    ORDER BY created_at DESC
    LIMIT 1
");


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "خطا در بررسی زمان آخرین ارسال."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


$stmt->bind_param(
    "s",
    $mobile
);


$stmt->execute();


$result =
    $stmt->get_result();


$lastComment =
    $result->fetch_assoc();


$stmt->close();


if ($lastComment) {

    $lastTime =
        strtotime(
            $lastComment["created_at"]
        );

    $currentTime =
        time();

    $secondsPassed =
        $currentTime - $lastTime;


    $fiveMinutes =
        5 * 60;


    if ($secondsPassed < $fiveMinutes) {

        $remaining =
            $fiveMinutes - $secondsPassed;

        $remainingMinutes =
            (int) ceil(
                $remaining / 60
            );


        echo json_encode([
            "success" => false,
            "message" =>
                "لطفاً {$remainingMinutes} دقیقه دیگر برای ارسال نظر بعدی صبر کنید."
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| ثبت نظر
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    INSERT INTO comments
    (
        mobile,
        rating,
        comment
    )
    VALUES
    (?, ?, ?)
");


if (!$stmt) {

    echo json_encode([
        "success" => false,
        "message" => "خطا در آماده‌سازی ثبت نظر."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


$stmt->bind_param(
    "sis",
    $mobile,
    $rating,
    $comment
);


if (!$stmt->execute()) {

    echo json_encode([
        "success" => false,
        "message" => "ثبت نظر انجام نشد."
    ], JSON_UNESCAPED_UNICODE);

    $stmt->close();

    exit;
}


$commentId =
    $stmt->insert_id;


$stmt->close();


/*
|--------------------------------------------------------------------------
| شماره موبایل ماسک‌شده
|--------------------------------------------------------------------------
*/

$maskedMobile =
    substr($mobile, 0, 4)
    . "****"
    . substr($mobile, -3);


/*
|--------------------------------------------------------------------------
| تاریخ نمایش
|--------------------------------------------------------------------------
*/

$displayDate =
    date("Y/m/d - H:i");


/*
|--------------------------------------------------------------------------
| پاسخ نهایی
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,

    "message" =>
        "نظر شما با موفقیت ثبت شد.",

    "review" => [

        "id" =>
            $commentId,

        "mobile" =>
            $maskedMobile,

        "rating" =>
            $rating,

        "comment" =>
            $comment,

        "date" =>
            $displayDate
    ]

], JSON_UNESCAPED_UNICODE);