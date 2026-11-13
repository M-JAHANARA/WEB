<?php

require_once "./db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| تنظیمات
|--------------------------------------------------------------------------
*/

date_default_timezone_set("Asia/Baku");

$PROFILE_AUTH_TIME = 15 * 60; // 15 دقیقه
$MAX_AUTH_ATTEMPTS = 3;
$LOCK_TIME = 24 * 60 * 60; // 24 ساعت

/*
|--------------------------------------------------------------------------
| بررسی لاگین
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION["user_id"];

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (empty($_SESSION["profile_csrf"])) {
    $_SESSION["profile_csrf"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["profile_csrf"];

/*
|--------------------------------------------------------------------------
| پیام‌ها
|--------------------------------------------------------------------------
*/

$message = "";
$messageType = "";

/*
|--------------------------------------------------------------------------
| دریافت اطلاعات کاربر
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT 
        id,
        firstname,
        lastname,
        mobile,
        password,
        profile_image,
        profile_auth_attempts,
        profile_auth_locked_until
     FROM users
     WHERE id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$user) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| متغیرهای کاربر
|--------------------------------------------------------------------------
*/

$firstname = $user["firstname"] ?? "";
$lastname = $user["lastname"] ?? "";
$phone = $user["phone"] ?? "";
$email = $user["email"] ?? "";
$address = $user["address"] ?? "";
$profileImage = $user["profile_image"] ?? "";

/*
|--------------------------------------------------------------------------
| بررسی قفل بودن احراز هویت
|--------------------------------------------------------------------------
*/

$isLocked = false;
$lockRemaining = 0;

if (!empty($user["profile_auth_locked_until"])) {

    $lockedUntil = strtotime($user["profile_auth_locked_until"]);
    $now = time();

    if ($lockedUntil > $now) {
        $isLocked = true;
        $lockRemaining = $lockedUntil - $now;
    } else {

        // قفل تمام شده
        $resetStmt = mysqli_prepare(
            $conn,
            "UPDATE users
             SET profile_auth_attempts = 0,
                 profile_auth_locked_until = NULL
             WHERE id = ?"
        );

        mysqli_stmt_bind_param($resetStmt, "i", $userId);
        mysqli_stmt_execute($resetStmt);
        mysqli_stmt_close($resetStmt);

        $user["profile_auth_attempts"] = 0;
        $user["profile_auth_locked_until"] = null;
    }
}

/*
|--------------------------------------------------------------------------
| بررسی تأیید 15 دقیقه‌ای
|--------------------------------------------------------------------------
*/

$profileVerified = false;

if (
    isset($_SESSION["profile_verified_at"]) &&
    isset($_SESSION["profile_verified_user"]) &&
    (int) $_SESSION["profile_verified_user"] === $userId
) {

    if (
        time() - (int) $_SESSION["profile_verified_at"]
        <= $PROFILE_AUTH_TIME
    ) {
        $profileVerified = true;
    } else {

        unset($_SESSION["profile_verified_at"]);
        unset($_SESSION["profile_verified_user"]);
    }
}

/*
|--------------------------------------------------------------------------
| POST
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    |--------------------------------------------------------------------------
    | بررسی CSRF
    |--------------------------------------------------------------------------
    */

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($csrfToken, $_POST["csrf_token"])
    ) {

        $message = "درخواست نامعتبر است. صفحه را دوباره باز کنید.";
        $messageType = "error";

    } else {

        $action = $_POST["action"] ?? "";

        /*
        |--------------------------------------------------------------------------
        | مرحله اول: تأیید رمز فعلی
        |--------------------------------------------------------------------------
        */

        if ($action === "verify_profile") {

            if ($isLocked) {

                $hours = floor($lockRemaining / 3600);
                $minutes = floor(($lockRemaining % 3600) / 60);

                $message =
                    "دسترسی موقتاً قفل است. حدود "
                    . $hours
                    . " ساعت و "
                    . $minutes
                    . " دقیقه دیگر دوباره تلاش کنید.";

                $messageType = "error";

            } else {

                $currentPassword = $_POST["current_password"] ?? "";

                if ($currentPassword === "") {

                    $message = "رمز عبور فعلی را وارد کنید.";
                    $messageType = "error";

                } elseif (
                    !password_verify(
                        $currentPassword,
                        $user["password"]
                    )
                ) {

                    $attempts = (int) $user["profile_auth_attempts"];
                    $attempts++;

                    if ($attempts >= $MAX_AUTH_ATTEMPTS) {

                        $lockedUntil = date(
                            "Y-m-d H:i:s",
                            time() + $LOCK_TIME
                        );

                        $lockStmt = mysqli_prepare(
                            $conn,
                            "UPDATE users
                             SET profile_auth_attempts = ?,
                                 profile_auth_locked_until = ?
                             WHERE id = ?"
                        );

                        mysqli_stmt_bind_param(
                            $lockStmt,
                            "isi",
                            $attempts,
                            $lockedUntil,
                            $userId
                        );

                        mysqli_stmt_execute($lockStmt);
                        mysqli_stmt_close($lockStmt);

                        $isLocked = true;

                        $message =
                            "به دلیل ۳ بار وارد کردن رمز اشتباه، "
                            . "ویرایش حساب به مدت ۲۴ ساعت قفل شد.";

                    } else {

                        $updateAttempts = mysqli_prepare(
                            $conn,
                            "UPDATE users
                             SET profile_auth_attempts = ?
                             WHERE id = ?"
                        );

                        mysqli_stmt_bind_param(
                            $updateAttempts,
                            "ii",
                            $attempts,
                            $userId
                        );

                        mysqli_stmt_execute($updateAttempts);
                        mysqli_stmt_close($updateAttempts);

                        $remaining =
                            $MAX_AUTH_ATTEMPTS - $attempts;

                        $message =
                            "رمز عبور فعلی صحیح نیست. "
                            . "تعداد تلاش باقی‌مانده: "
                            . $remaining;
                    }

                    $messageType = "error";

                } else {

                    /*
                    | رمز صحیح
                    */

                    $resetAuth = mysqli_prepare(
                        $conn,
                        "UPDATE users
                         SET profile_auth_attempts = 0,
                             profile_auth_locked_until = NULL
                         WHERE id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $resetAuth,
                        "i",
                        $userId
                    );

                    mysqli_stmt_execute($resetAuth);
                    mysqli_stmt_close($resetAuth);

                    $_SESSION["profile_verified_at"] = time();
                    $_SESSION["profile_verified_user"] = $userId;

                    header("Location: profile.php");
                    exit;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | خروج از حالت تأیید
        |--------------------------------------------------------------------------
        */

        elseif ($action === "cancel_profile_auth") {

            unset($_SESSION["profile_verified_at"]);
            unset($_SESSION["profile_verified_user"]);

            header("Location: index.php");
            exit;
        }

        /*
        |--------------------------------------------------------------------------
        | ذخیره اطلاعات
        |--------------------------------------------------------------------------
        */

        elseif ($action === "update_profile") {

            if (!$profileVerified) {

                $message =
                    "زمان تأیید حساب به پایان رسیده است. "
                    . "دوباره رمز عبور خود را تأیید کنید.";

                $messageType = "error";

            } else {

                /*
                |--------------------------------------------------------------------------
                | دریافت اطلاعات
                |--------------------------------------------------------------------------
                */

                $newFirstname = trim($_POST["firstname"] ?? "");
                $newLastname = trim($_POST["lastname"] ?? "");
                $newEmail = trim($_POST["email"] ?? "");
                $newAddress = trim($_POST["address"] ?? "");

                $currentPassword =
                    $_POST["current_password_change"] ?? "";

                $newPassword =
                    $_POST["new_password"] ?? "";

                $confirmPassword =
                    $_POST["confirm_password"] ?? "";

                /*
                |--------------------------------------------------------------------------
                | اعتبارسنجی نام و نام خانوادگی
                |--------------------------------------------------------------------------
                */

                $persianPattern =
                    '/^[\x{0600}-\x{06FF}\x{FB8A}\x{067E}\x{0686}\x{0698}\x{06AF}\s]+$/u';

                if ($newFirstname === "") {

                    $message = "نام را وارد کنید.";
                    $messageType = "error";

                } elseif (
                    !preg_match(
                        $persianPattern,
                        $newFirstname
                    )
                ) {

                    $message =
                        "نام باید فقط شامل حروف فارسی باشد.";

                    $messageType = "error";

                } elseif ($newLastname === "") {

                    $message = "نام خانوادگی را وارد کنید.";
                    $messageType = "error";

                } elseif (
                    !preg_match(
                        $persianPattern,
                        $newLastname
                    )
                ) {

                    $message =
                        "نام خانوادگی باید فقط شامل حروف فارسی باشد.";

                    $messageType = "error";

                } elseif (
                    $newEmail !== "" &&
                    !filter_var($newEmail, FILTER_VALIDATE_EMAIL)
                ) {

                    $message =
                        "فرمت ایمیل وارد شده صحیح نیست.";

                    $messageType = "error";

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | بررسی تکراری نبودن ایمیل
                    |--------------------------------------------------------------------------
                    */

                    $emailExists = false;

                    if ($newEmail !== "") {

                        $emailCheck = mysqli_prepare(
                            $conn,
                            "SELECT id
                             FROM users
                             WHERE email = ?
                             AND id != ?
                             LIMIT 1"
                        );

                        mysqli_stmt_bind_param(
                            $emailCheck,
                            "si",
                            $newEmail,
                            $userId
                        );

                        mysqli_stmt_execute($emailCheck);

                        $emailResult =
                            mysqli_stmt_get_result($emailCheck);

                        $emailExists =
                            mysqli_num_rows($emailResult) > 0;

                        mysqli_stmt_close($emailCheck);
                    }

                    if ($emailExists) {

                        $message =
                            "این ایمیل قبلاً توسط کاربر دیگری ثبت شده است.";

                        $messageType = "error";

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | رمز عبور
                        |--------------------------------------------------------------------------
                        */

                        $passwordQuery = "";
                        $passwordHash = null;

                        if (
                            $newPassword !== "" ||
                            $confirmPassword !== "" ||
                            $currentPassword !== ""
                        ) {

                            if ($currentPassword === "") {

                                $message =
                                    "برای تغییر رمز عبور، رمز فعلی را وارد کنید.";

                                $messageType = "error";

                            } elseif (
                                !password_verify(
                                    $currentPassword,
                                    $user["password"]
                                )
                            ) {

                                $message =
                                    "رمز عبور فعلی برای تغییر رمز صحیح نیست.";

                                $messageType = "error";

                            } elseif (strlen($newPassword) < 8) {

                                $message =
                                    "رمز عبور جدید باید حداقل ۸ کاراکتر باشد.";

                                $messageType = "error";

                            } elseif (
                                $newPassword !== $confirmPassword
                            ) {

                                $message =
                                    "رمز عبور جدید و تکرار آن یکسان نیست.";

                                $messageType = "error";

                            } else {

                                $passwordHash =
                                    password_hash(
                                        $newPassword,
                                        PASSWORD_DEFAULT
                                    );
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | اگر تا اینجا خطایی نبود
                        |--------------------------------------------------------------------------
                        */

                        if ($messageType !== "error") {

                            /*
                            |--------------------------------------------------------------------------
                            | مدیریت تصویر
                            |--------------------------------------------------------------------------
                            */

                            $newProfileImage = $profileImage;
                            $uploadedFilePath = null;
                            $oldProfileFilePath = null;

                            if (
                                isset($_FILES["profile_image"]) &&
                                $_FILES["profile_image"]["error"] !== UPLOAD_ERR_NO_FILE
                            ) {

                                $file = $_FILES["profile_image"];

                                if (
                                    $file["error"] !== UPLOAD_ERR_OK
                                ) {

                                    $message =
                                        "در آپلود تصویر مشکلی به وجود آمد.";

                                    $messageType = "error";

                                } elseif (
                                    $file["size"] > 2 * 1024 * 1024
                                ) {

                                    $message =
                                        "حجم تصویر نباید بیشتر از ۲ مگابایت باشد.";

                                    $messageType = "error";

                                } elseif (
                                    !is_uploaded_file(
                                        $file["tmp_name"]
                                    )
                                ) {

                                    $message =
                                        "فایل ارسال شده معتبر نیست.";

                                    $messageType = "error";

                                } else {

                                    /*
                                    |--------------------------------------------------------------------------
                                    | بررسی MIME واقعی
                                    |--------------------------------------------------------------------------
                                    */

                                    $finfo =
                                        new finfo(FILEINFO_MIME_TYPE);

                                    $mime =
                                        $finfo->file(
                                            $file["tmp_name"]
                                        );

                                    $allowedTypes = [
                                        "image/jpeg" => "jpg",
                                        "image/png" => "png",
                                        "image/webp" => "webp"
                                    ];

                                    if (
                                        !isset(
                                            $allowedTypes[$mime]
                                        )
                                    ) {

                                        $message =
                                            "فرمت تصویر باید JPG، PNG یا WEBP باشد.";

                                        $messageType = "error";

                                    } else {

                                        /*
                                        |--------------------------------------------------------------------------
                                        | بررسی واقعی بودن تصویر
                                        |--------------------------------------------------------------------------
                                        */

                                        $imageInfo =
                                            @getimagesize(
                                                $file["tmp_name"]
                                            );

                                        if ($imageInfo === false) {

                                            $message =
                                                "فایل انتخاب شده یک تصویر معتبر نیست.";

                                            $messageType = "error";

                                        } else {

                                            $extension =
                                                $allowedTypes[$mime];

                                            $uploadDirectory =
                                                __DIR__
                                                . "/assets/image/profiles/";

                                            if (
                                                !is_dir(
                                                    $uploadDirectory
                                                )
                                            ) {

                                                if (
                                                    !mkdir(
                                                        $uploadDirectory,
                                                        0755,
                                                        true
                                                    )
                                                ) {

                                                    $message =
                                                        "پوشه ذخیره تصویر قابل ایجاد نیست.";

                                                    $messageType = "error";
                                                }
                                            }

                                            if (
                                                $messageType !== "error"
                                            ) {

                                                $uniqueName =
                                                    "profile_"
                                                    . $userId
                                                    . "_"
                                                    . bin2hex(
                                                        random_bytes(8)
                                                    )
                                                    . "."
                                                    . $extension;

                                                $destination =
                                                    $uploadDirectory
                                                    . $uniqueName;

                                                if (
                                                    !move_uploaded_file(
                                                        $file["tmp_name"],
                                                        $destination
                                                    )
                                                ) {

                                                    $message =
                                                        "ذخیره تصویر انجام نشد.";

                                                    $messageType = "error";

                                                } else {

                                                    $newProfileImage =
                                                        "assets/image/profiles/"
                                                        . $uniqueName;

                                                    $uploadedFilePath =
                                                        $destination;

                                                    if (
                                                        !empty(
                                                            $profileImage
                                                        )
                                                    ) {

                                                        $oldProfileFilePath =
                                                            __DIR__
                                                            . "/"
                                                            . ltrim(
                                                                $profileImage,
                                                                "/"
                                                            );
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | آپدیت دیتابیس
                            |--------------------------------------------------------------------------
                            */

                            if ($messageType !== "error") {

                                if ($passwordHash !== null) {

                                    $updateStmt = mysqli_prepare(
                                        $conn,
                                        "UPDATE users
                                         SET firstname = ?,
                                             lastname = ?,
                                             email = ?,
                                             address = ?,
                                             profile_image = ?,
                                             password = ?
                                         WHERE id = ?"
                                    );

                                    mysqli_stmt_bind_param(
                                        $updateStmt,
                                        "ssssssi",
                                        $newFirstname,
                                        $newLastname,
                                        $newEmail,
                                        $newAddress,
                                        $newProfileImage,
                                        $passwordHash,
                                        $userId
                                    );

                                } else {

                                    $updateStmt = mysqli_prepare(
                                        $conn,
                                        "UPDATE users
                                         SET firstname = ?,
                                             lastname = ?,
                                             profile_image = ?
                                         WHERE id = ?"
                                    );

                                    mysqli_stmt_bind_param(
                                        $updateStmt,
                                        "sssi",
                                        $newFirstname,
                                        $newLastname,
                                        $newProfileImage,
                                        $userId
                                    );
                                }

                                $updated =
                                    mysqli_stmt_execute(
                                        $updateStmt
                                    );

                                mysqli_stmt_close($updateStmt);

                                if (!$updated) {

                                    /*
                                    | اگر DB آپدیت نشد،
                                    | تصویر تازه را حذف کن
                                    */

                                    if (
                                        $uploadedFilePath &&
                                        file_exists(
                                            $uploadedFilePath
                                        )
                                    ) {

                                        @unlink(
                                            $uploadedFilePath
                                        );
                                    }

                                    $message =
                                        "ذخیره اطلاعات انجام نشد.";

                                    $messageType = "error";

                                } else {

                                    /*
                                    |--------------------------------------------------------------------------
                                    | حذف تصویر قبلی
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        $oldProfileFilePath &&
                                        file_exists(
                                            $oldProfileFilePath
                                        ) &&
                                        $oldProfileFilePath !==
                                        $uploadedFilePath
                                    ) {

                                        @unlink(
                                            $oldProfileFilePath
                                        );
                                    }

                                    /*
                                    |--------------------------------------------------------------------------
                                    | آپدیت Session
                                    |--------------------------------------------------------------------------
                                    */

                                    $_SESSION["firstname"] =
                                        $newFirstname;

                                    $_SESSION["lastname"] =
                                        $newLastname;

                                    $_SESSION["phone"] =
                                        $phone;

                                    $_SESSION["profile_image"] =
                                        $newProfileImage;

                                    /*
                                    |--------------------------------------------------------------------------
                                    | حفظ تأیید
                                    |--------------------------------------------------------------------------
                                    */

                                    $_SESSION["profile_verified_at"] =
                                        time();

                                    $_SESSION["profile_verified_user"] =
                                        $userId;

                                    /*
                                    |--------------------------------------------------------------------------
                                    | انتقال به خانه
                                    |--------------------------------------------------------------------------
                                    */

                                    header(
                                        "Location: index.php?profile_updated=1"
                                    );

                                    exit;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| اگر پیام موفقیت از index برگشته
|--------------------------------------------------------------------------
*/

$profileUpdated =
    isset($_GET["profile_updated"]) &&
    $_GET["profile_updated"] === "1";

/*
|--------------------------------------------------------------------------
| نام کامل
|--------------------------------------------------------------------------
*/

$fullName =
    trim($firstname . " " . $lastname);

if ($fullName === "") {
    $fullName = "کاربر";
}

/*
|--------------------------------------------------------------------------
| آدرس تصویر
|--------------------------------------------------------------------------
*/

$profileImageUrl = "";

if (!empty($profileImage)) {

    $profileImageUrl =
        "./" . ltrim($profileImage, "./");
}

/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

require_once "./include/header.php";

?>

<main class="profile-page">

    <div class="profile-container">

        <!-- =========================================================
             PAGE HEADER
        ========================================================== -->

        <div class="profile-page-heading">

            <div>
                <span class="profile-eyebrow">
                    حساب کاربری
                </span>

                <h1>
                    پروفایل من
                </h1>

                <p>
                    اطلاعات حساب و مشخصات شخصی خود را مدیریت کنید.
                </p>
            </div>

            <a
                href="index.php"
                class="profile-back-button"
            >
                <span>بازگشت به فروشگاه</span>

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path d="M5 12h14"></path>
                    <path d="m13 6 6 6-6 6"></path>
                </svg>
            </a>

        </div>


        <?php if ($profileUpdated): ?>

            <div class="profile-alert profile-alert-success">

                <span class="profile-alert-icon">
                    ✓
                </span>

                <div>
                    <strong>
                        حساب شما با موفقیت به‌روزرسانی شد.
                    </strong>

                    <span>
                        تغییرات جدید روی حساب شما ذخیره شده است.
                    </span>
                </div>

            </div>

        <?php endif; ?>


        <?php if ($message !== ""): ?>

            <div class="profile-alert <?= $messageType === "error"
                ? "profile-alert-error"
                : "profile-alert-success" ?>">

                <span class="profile-alert-icon">
                    <?= $messageType === "error" ? "!" : "✓" ?>
                </span>

                <div>
                    <strong>
                        <?= htmlspecialchars(
                            $message,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>
                    </strong>
                </div>

            </div>

        <?php endif; ?>


        <!-- =========================================================
             ACCOUNT LAYOUT
        ========================================================== -->

        <div class="profile-layout">

            <!-- =====================================================
                 SIDEBAR
            ====================================================== -->

            <aside class="profile-sidebar">

                <div class="profile-user-card">

                    <div class="profile-avatar-large">

                        <?php if ($profileImageUrl !== ""): ?>

                            <img
                                src="<?= htmlspecialchars(
                                    $profileImageUrl,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                                alt="تصویر پروفایل"
                                id="profileAvatarPreview"
                            >

                        <?php else: ?>

                            <div
                                class="profile-avatar-placeholder"
                                id="profileAvatarPlaceholder"
                            >
                                <?= htmlspecialchars(
                                    mb_substr(
                                        $firstname ?: "ک",
                                        0,
                                        1,
                                        "UTF-8"
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </div>

                            <img
                                src=""
                                alt=""
                                id="profileAvatarPreview"
                                class="profile-avatar-preview-hidden"
                            >

                        <?php endif; ?>

                    </div>

                    <div class="profile-user-info">

                        <h2>
                            <?= htmlspecialchars(
                                $fullName,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </h2>

                        <span>
                            <?= htmlspecialchars(
                                $phone,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>
                        </span>

                    </div>

                    <div class="profile-account-status">

                        <span class="status-dot"></span>

                        حساب فعال

                    </div>

                </div>


                <div class="profile-sidebar-menu">

                    <a
                        href="#personal-information"
                        class="profile-menu-item active"
                        data-profile-target="personal-information"
                    >

                        <span class="profile-menu-icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <circle
                                    cx="12"
                                    cy="8"
                                    r="3"
                                ></circle>

                                <path
                                    d="M5 20c.8-3.2 3.1-5 7-5s6.2 1.8 7 5"
                                ></path>
                            </svg>
                        </span>

                        اطلاعات شخصی

                    </a>


                    <a
                        href="#contact-information"
                        class="profile-menu-item"
                        data-profile-target="contact-information"
                    >

                        <span class="profile-menu-icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <rect
                                    x="3"
                                    y="5"
                                    width="18"
                                    height="14"
                                    rx="2"
                                ></rect>

                                <path
                                    d="m4 7 8 6 8-6"
                                ></path>
                            </svg>
                        </span>

                        اطلاعات تماس

                    </a>


                    <a
                        href="#security-information"
                        class="profile-menu-item"
                        data-profile-target="security-information"
                    >

                        <span class="profile-menu-icon">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <rect
                                    x="5"
                                    y="10"
                                    width="14"
                                    height="10"
                                    rx="2"
                                ></rect>

                                <path
                                    d="M8 10V7a4 4 0 0 1 8 0v3"
                                ></path>

                                <circle
                                    cx="12"
                                    cy="15"
                                    r="1"
                                ></circle>
                            </svg>
                        </span>

                        امنیت حساب

                    </a>

                </div>


                <div class="profile-sidebar-note">

                    <div class="profile-note-icon">
                        ✓
                    </div>

                    <div>
                        <strong>
                            شماره موبایل ثابت است
                        </strong>

                        <span>
                            برای امنیت حساب، شماره موبایل قابل تغییر نیست.
                        </span>
                    </div>

                </div>

            </aside>


            <!-- =====================================================
                 MAIN FORM
            ====================================================== -->

            <section class="profile-main">

                <?php if (!$profileVerified): ?>

                    <!-- =============================================
                         AUTH SCREEN
                    ============================================== -->

                    <div class="profile-auth-card">

                        <div class="profile-auth-visual">

                            <div class="profile-auth-orbit orbit-one"></div>
                            <div class="profile-auth-orbit orbit-two"></div>

                            <div class="profile-auth-lock">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                >
                                    <rect
                                        x="5"
                                        y="10"
                                        width="14"
                                        height="10"
                                        rx="2"
                                    ></rect>

                                    <path
                                        d="M8 10V7a4 4 0 0 1 8 0v3"
                                    ></path>
                                </svg>

                            </div>

                        </div>

                        <div class="profile-auth-content">

                            <span class="profile-auth-label">
                                امنیت حساب
                            </span>

                            <h2>
                                قبل از ویرایش، هویت شما را بررسی می‌کنیم
                            </h2>

                            <p>
                                برای دسترسی به اطلاعات حساب،
                                رمز عبور فعلی خود را وارد کنید.
                                پس از تأیید، به مدت ۱۵ دقیقه امکان ویرایش خواهید داشت.
                            </p>


                            <?php if ($isLocked): ?>

                                <div class="profile-lock-message">

                                    <span>🔒</span>

                                    <div>
                                        <strong>
                                            حساب ویرایش موقتاً قفل است
                                        </strong>

                                        <small>
                                            به دلیل چند تلاش ناموفق،
                                            لطفاً بعداً دوباره تلاش کنید.
                                        </small>
                                    </div>

                                </div>

                            <?php else: ?>

                                <form
                                    method="POST"
                                    class="profile-auth-form"
                                    autocomplete="off"
                                >

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= htmlspecialchars(
                                            $csrfToken,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="verify_profile"
                                    >

                                    <label
                                        class="profile-field"
                                    >

                                        <span>
                                            رمز عبور فعلی
                                        </span>

                                        <div class="profile-input-wrap">

                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                            >
                                                <rect
                                                    x="5"
                                                    y="10"
                                                    width="14"
                                                    height="10"
                                                    rx="2"
                                                ></rect>

                                                <path
                                                    d="M8 10V7a4 4 0 0 1 8 0v3"
                                                ></path>
                                            </svg>

                                            <input
                                                type="password"
                                                name="current_password"
                                                placeholder="رمز عبور فعلی"
                                                required
                                                autocomplete="current-password"
                                            >

                                            <button
                                                type="button"
                                                class="profile-password-toggle"
                                                data-password-toggle
                                                aria-label="نمایش رمز عبور"
                                            >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                >
                                                    <path
                                                        d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"
                                                    ></path>

                                                    <circle
                                                        cx="12"
                                                        cy="12"
                                                        r="2.5"
                                                    ></circle>
                                                </svg>
                                            </button>

                                        </div>

                                    </label>


                                    <button
                                        type="submit"
                                        class="profile-primary-button"
                                    >

                                        <span>
                                            تأیید و ورود به پروفایل
                                        </span>

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                d="M5 12h14"
                                            ></path>

                                            <path
                                                d="m13 6 6 6-6 6"
                                            ></path>
                                        </svg>

                                    </button>

                                </form>

                            <?php endif; ?>

                        </div>

                    </div>

                <?php else: ?>

                    <!-- =============================================
                         VERIFIED PROFILE
                    ============================================== -->

                    <form
                        method="POST"
                        enctype="multipart/form-data"
                        id="profileUpdateForm"
                        class="profile-form"
                    >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars(
                                $csrfToken,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                        >

                        <input
                            type="hidden"
                            name="action"
                            value="update_profile"
                        >


                        <!-- =========================================
                             PERSONAL INFORMATION
                        ========================================== -->

                        <section
                            class="profile-section"
                            id="personal-information"
                        >

                            <div class="profile-section-heading">

                                <div>

                                    <span>
                                        ۰۱
                                    </span>

                                    <div>

                                        <h2>
                                            اطلاعات شخصی
                                        </h2>

                                        <p>
                                            مشخصات اصلی حساب خود را مدیریت کنید.
                                        </p>

                                    </div>

                                </div>

                            </div>


                            <div class="profile-image-editor">

                                <div class="profile-edit-avatar">

                                    <?php if ($profileImageUrl !== ""): ?>

                                        <img
                                            src="<?= htmlspecialchars(
                                                $profileImageUrl,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            alt="تصویر پروفایل"
                                            id="profileFormAvatar"
                                        >

                                    <?php else: ?>

                                        <div
                                            class="profile-form-avatar-placeholder"
                                            id="profileFormAvatarPlaceholder"
                                        >
                                            <?= htmlspecialchars(
                                                mb_substr(
                                                    $firstname ?: "ک",
                                                    0,
                                                    1,
                                                    "UTF-8"
                                                ),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </div>

                                        <img
                                            src=""
                                            alt=""
                                            id="profileFormAvatar"
                                            class="profile-avatar-preview-hidden"
                                        >

                                    <?php endif; ?>

                                </div>


                                <div class="profile-image-content">

                                    <strong>
                                        تصویر پروفایل
                                    </strong>

                                    <p>
                                        تصویر خود را انتخاب کنید.
                                        فرمت‌های JPG، PNG و WEBP تا حجم ۲ مگابایت.
                                    </p>

                                    <label class="profile-upload-button">

                                        <input
                                            type="file"
                                            name="profile_image"
                                            id="profileImageInput"
                                            accept="image/jpeg,image/png,image/webp"
                                        >

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                d="M12 16V4"
                                            ></path>

                                            <path
                                                d="m7 9 5-5 5 5"
                                            ></path>

                                            <path
                                                d="M5 20h14"
                                            ></path>
                                        </svg>

                                        انتخاب تصویر

                                    </label>

                                    <span
                                        class="profile-file-name"
                                        id="profileFileName"
                                    >
                                        هنوز تصویری انتخاب نشده
                                    </span>

                                </div>

                            </div>


                            <div class="profile-fields-grid">

                                <label class="profile-field">

                                    <span>
                                        نام
                                    </span>

                                    <div class="profile-input-wrap">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <circle
                                                cx="12"
                                                cy="8"
                                                r="3"
                                            ></circle>

                                            <path
                                                d="M5 20c.8-3.2 3.1-5 7-5s6.2 1.8 7 5"
                                            ></path>
                                        </svg>

                                        <input
                                            type="text"
                                            name="firstname"
                                            value="<?= htmlspecialchars(
                                                $firstname,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            data-persian-only
                                            maxlength="50"
                                            required
                                        >

                                    </div>

                                </label>


                                <label class="profile-field">

                                    <span>
                                        نام خانوادگی
                                    </span>

                                    <div class="profile-input-wrap">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                d="M4 20c.7-3.7 3.3-6 8-6s7.3 2.3 8 6"
                                            ></path>

                                            <circle
                                                cx="12"
                                                cy="7"
                                                r="3"
                                            ></circle>
                                        </svg>

                                        <input
                                            type="text"
                                            name="lastname"
                                            value="<?= htmlspecialchars(
                                                $lastname,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            data-persian-only
                                            maxlength="60"
                                            required
                                        >

                                    </div>

                                </label>

                            </div>

                        </section>


                        <!-- =========================================
                             CONTACT INFORMATION
                        ========================================== -->

                        <section
                            class="profile-section"
                            id="contact-information"
                        >

                            <div class="profile-section-heading">

                                <div>

                                    <span>
                                        ۰۲
                                    </span>

                                    <div>

                                        <h2>
                                            اطلاعات تماس
                                        </h2>

                                        <p>
                                            اطلاعات ارتباطی حساب خود را مدیریت کنید.
                                        </p>

                                    </div>

                                </div>

                            </div>


                            <div class="profile-fields-grid">

                                <!-- MOBILE -->

                                <label
                                    class="profile-field profile-field-disabled"
                                >

                                    <span>
                                        شماره موبایل
                                    </span>

                                    <div class="profile-input-wrap">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <rect
                                                x="7"
                                                y="3"
                                                width="10"
                                                height="18"
                                                rx="2"
                                            ></rect>

                                            <path
                                                d="M10 6h4"
                                            ></path>

                                            <circle
                                                cx="12"
                                                cy="18"
                                                r="1"
                                            ></circle>
                                        </svg>

                                        <input
                                            type="text"
                                            value="<?= htmlspecialchars(
                                                $phone,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            disabled
                                        >

                                        <span class="profile-locked-badge">
                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                            >
                                                <rect
                                                    x="5"
                                                    y="10"
                                                    width="14"
                                                    height="10"
                                                    rx="2"
                                                ></rect>

                                                <path
                                                    d="M8 10V7a4 4 0 0 1 8 0v3"
                                                ></path>
                                            </svg>
                                        </span>

                                    </div>

                                    <small>
                                        شماره موبایل قابل تغییر نیست.
                                    </small>

                                </label>


                                <!-- EMAIL -->

                                <label class="profile-field">

                                    <span>
                                        ایمیل
                                    </span>

                                    <div class="profile-input-wrap">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <rect
                                                x="3"
                                                y="5"
                                                width="18"
                                                height="14"
                                                rx="2"
                                            ></rect>

                                            <path
                                                d="m4 7 8 6 8-6"
                                            ></path>
                                        </svg>

                                        <input
                                            type="email"
                                            name="email"
                                            value="<?= htmlspecialchars(
                                                $email,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>"
                                            maxlength="150"
                                            placeholder="example@email.com"
                                        >

                                    </div>

                                </label>


                                <!-- ADDRESS -->

                                <label class="profile-field profile-field-full">

                                    <span>
                                        آدرس
                                    </span>

                                    <div class="profile-input-wrap profile-textarea-wrap">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"
                                            ></path>

                                            <circle
                                                cx="12"
                                                cy="10"
                                                r="2.5"
                                            ></circle>
                                        </svg>

                                        <textarea
                                            name="address"
                                            rows="4"
                                            maxlength="500"
                                            placeholder="آدرس خود را وارد کنید..."
                                        ><?= htmlspecialchars(
                                            $address,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?></textarea>

                                    </div>

                                </label>

                            </div>

                        </section>


                        <!-- =========================================
                             SECURITY
                        ========================================== -->

                        <section
                            class="profile-section"
                            id="security-information"
                        >

                            <div class="profile-section-heading">

                                <div>

                                    <span>
                                        ۰۳
                                    </span>

                                    <div>

                                        <h2>
                                            امنیت حساب
                                        </h2>

                                        <p>
                                            در صورت نیاز رمز عبور حساب خود را تغییر دهید.
                                        </p>

                                    </div>

                                </div>

                            </div>


                            <div class="profile-security-box">

                                <div class="profile-security-header">

                                    <div class="profile-security-icon">

                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <rect
                                                x="5"
                                                y="10"
                                                width="14"
                                                height="10"
                                                rx="2"
                                            ></rect>

                                            <path
                                                d="M8 10V7a4 4 0 0 1 8 0v3"
                                            ></path>
                                        </svg>

                                    </div>

                                    <div>

                                        <strong>
                                            تغییر رمز عبور
                                        </strong>

                                        <span>
                                            برای حفظ امنیت، رمز عبور جدید حداقل ۸ کاراکتر باشد.
                                        </span>

                                    </div>

                                </div>


                                <div class="profile-fields-grid">

                                    <label class="profile-field">

                                        <span>
                                            رمز عبور فعلی
                                        </span>

                                        <div class="profile-input-wrap">

                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                            >
                                                <rect
                                                    x="5"
                                                    y="10"
                                                    width="14"
                                                    height="10"
                                                    rx="2"
                                                ></rect>

                                                <path
                                                    d="M8 10V7a4 4 0 0 1 8 0v3"
                                                ></path>
                                            </svg>

                                            <input
                                                type="password"
                                                name="current_password_change"
                                                autocomplete="current-password"
                                                placeholder="در صورت تغییر رمز"
                                            >

                                            <button
                                                type="button"
                                                class="profile-password-toggle"
                                                data-password-toggle
                                            >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                >
                                                    <path
                                                        d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"
                                                    ></path>

                                                    <circle
                                                        cx="12"
                                                        cy="12"
                                                        r="2.5"
                                                    ></circle>
                                                </svg>
                                            </button>

                                        </div>

                                    </label>


                                    <label class="profile-field">

                                        <span>
                                            رمز عبور جدید
                                        </span>

                                        <div class="profile-input-wrap">

                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                            >
                                                <path
                                                    d="M12 3 5 6v5c0 4.6 2.9 8.1 7 10 4.1-1.9 7-5.4 7-10V6l-7-3Z"
                                                ></path>

                                                <path
                                                    d="m9 12 2 2 4-4"
                                                ></path>
                                            </svg>

                                            <input
                                                type="password"
                                                name="new_password"
                                                id="newPassword"
                                                minlength="8"
                                                autocomplete="new-password"
                                                placeholder="حداقل ۸ کاراکتر"
                                            >

                                            <button
                                                type="button"
                                                class="profile-password-toggle"
                                                data-password-toggle
                                            >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                >
                                                    <path
                                                        d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"
                                                    ></path>

                                                    <circle
                                                        cx="12"
                                                        cy="12"
                                                        r="2.5"
                                                    ></circle>
                                                </svg>
                                            </button>

                                        </div>

                                    </label>


                                    <label class="profile-field">

                                        <span>
                                            تکرار رمز جدید
                                        </span>

                                        <div class="profile-input-wrap">

                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                            >
                                                <path
                                                    d="M12 3 5 6v5c0 4.6 2.9 8.1 7 10 4.1-1.9 7-5.4 7-10V6l-7-3Z"
                                                ></path>

                                                <path
                                                    d="m9 12 2 2 4-4"
                                                ></path>
                                            </svg>

                                            <input
                                                type="password"
                                                name="confirm_password"
                                                id="confirmPassword"
                                                minlength="8"
                                                autocomplete="new-password"
                                                placeholder="تکرار رمز جدید"
                                            >

                                            <button
                                                type="button"
                                                class="profile-password-toggle"
                                                data-password-toggle
                                            >
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                >
                                                    <path
                                                        d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"
                                                    ></path>

                                                    <circle
                                                        cx="12"
                                                        cy="12"
                                                        r="2.5"
                                                    ></circle>
                                                </svg>
                                            </button>

                                        </div>

                                    </label>

                                </div>

                            </div>

                        </section>


                        <!-- =========================================
                             SAVE BAR
                        ========================================== -->

                        <div class="profile-save-bar">

                            <div class="profile-save-info">

                                <span class="profile-save-icon">
                                    ✓
                                </span>

                                <div>

                                    <strong>
                                        تغییرات حساب
                                    </strong>

                                    <span>
                                        اطلاعات شما در همین حساب ذخیره می‌شود.
                                    </span>

                                </div>

                            </div>


                            <div class="profile-save-actions">

                                <a
                                    href="index.php"
                                    class="profile-cancel-button"
                                >
                                    انصراف
                                </a>

                                <button
                                    type="submit"
                                    class="profile-save-button"
                                    id="profileSaveButton"
                                >

                                    <span>
                                        ذخیره تغییرات
                                    </span>

                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            d="M5 12h14"
                                        ></path>

                                        <path
                                            d="m13 6 6 6-6 6"
                                        ></path>
                                    </svg>

                                </button>

                            </div>

                        </div>

                    </form>

                <?php endif; ?>

            </section>

        </div>

    </div>

</main>

<?php

require_once "./include/footer.php";

?>