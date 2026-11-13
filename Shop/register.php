<?php

session_start();

require_once "./db.php";

$error = "";


/* =========================================================
   FORM DEFAULT VALUES
========================================================= */

$firstname = "";
$lastname  = "";
$mobile    = "";
$password  = "";
$password2 = "";
$otp       = "";
$terms     = false;


/* =========================================================
   REGISTER
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $firstname = trim($_POST["firstname"] ?? "");
    $lastname  = trim($_POST["lastname"] ?? "");
    $mobile    = trim($_POST["mobile"] ?? "");
    $password  = $_POST["password"] ?? "";
    $password2 = $_POST["password2"] ?? "";
    $otp       = trim($_POST["otp"] ?? "");
    $terms     = isset($_POST["terms"]);


    /* =====================================================
       VALIDATION
    ===================================================== */

    /* نام فقط انگلیسی */

    if (
        $firstname === "" ||
        !preg_match('/^[A-Za-z]+$/', $firstname)
    ) {

        $error =
            "نام فقط باید شامل حروف انگلیسی باشد.";

    }


    /* نام خانوادگی فقط انگلیسی */

    elseif (
        $lastname === "" ||
        !preg_match('/^[A-Za-z]+$/', $lastname)
    ) {

        $error =
            "نام خانوادگی فقط باید شامل حروف انگلیسی باشد.";

    }


    /* شماره موبایل فقط اعداد انگلیسی */

    elseif (
        !preg_match('/^09[0-9]{9}$/', $mobile)
    ) {

        $error =
            "شماره موبایل را به صورت صحیح وارد کنید.";

    }


    /* رمز فقط عدد انگلیسی */

    elseif (
        !preg_match('/^[0-9]+$/', $password)
    ) {

        $error =
            "رمز عبور فقط باید شامل اعداد انگلیسی باشد.";

    }


    /* حداقل ۶ رقم */

    elseif (
        strlen($password) < 6
    ) {

        $error =
            "رمز عبور باید حداقل ۶ رقم باشد.";

    }


    /* تکرار رمز */

    elseif (
        $password !== $password2
    ) {

        $error =
            "رمز عبور و تکرار آن یکسان نیستند.";

    }


    /* قوانین */

    elseif (!$terms) {

        $error =
            "برای ثبت نام باید قوانین سایت را بپذیرید.";

    }


    /*
     * فعلاً OTP فقط در صورتی که ارسال شده باشد
     * از نظر فرمت بررسی می‌شود.
     *
     * ارسال SMS واقعی در این مرحله نداریم.
     */

    elseif (
        $otp !== "" &&
        !preg_match('/^[0-9]{6}$/', $otp)
    ) {

        $error =
            "کد تایید باید ۶ رقمی باشد.";

    }


    /* =====================================================
       DATABASE
    ===================================================== */

    else {

        /* =================================================
           CHECK DUPLICATE MOBILE
        ================================================= */

        $check = $conn->prepare(
            "SELECT id
             FROM users
             WHERE mobile = ?
             LIMIT 1"
        );


        if (!$check) {

            $error =
                "خطایی در بررسی اطلاعات رخ داد.";

        } else {

            $check->bind_param(
                "s",
                $mobile
            );

            $check->execute();

            $check->store_result();


            /* =============================================
               MOBILE ALREADY EXISTS
            ============================================= */

            if ($check->num_rows > 0) {

                $error =
                    "این شماره موبایل قبلاً ثبت شده است.";

            }


            /* =============================================
               NEW USER
            ============================================= */

            else {

                /* =============================================
                   HASH PASSWORD
                ============================================= */

                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                if ($hashedPassword === false) {

                    $error =
                        "خطایی در ایجاد رمز عبور رخ داد.";

                } else {


                    /* =========================================
                       INSERT USER
                    ========================================= */

                    $stmt = $conn->prepare(
                        "INSERT INTO users
                        (
                            firstname,
                            lastname,
                            mobile,
                            password
                        )
                        VALUES
                        (?, ?, ?, ?)"
                    );


                    if (!$stmt) {

                        $error =
                            "خطایی در آماده‌سازی ثبت اطلاعات رخ داد.";

                    } else {

                        $stmt->bind_param(
                            "ssss",
                            $firstname,
                            $lastname,
                            $mobile,
                            $hashedPassword
                        );


                        /* =====================================
                           INSERT SUCCESS
                        ===================================== */

                        if ($stmt->execute()) {

                            /*
                             * طبق روند سایت:
                             *
                             * register
                             *      ↓
                             * login
                             *      ↓
                             * index
                             */

                            header(
                                "Location: login.php?registered=1"
                            );

                            exit;

                        }


                        /* =====================================
                           INSERT ERROR
                        ===================================== */

                        else {

                            /*
                             * خطای احتمالی Unique
                             * در صورتی که همزمان رکورد
                             * دیگری با همین شماره ساخته شده باشد.
                             */

                            if ($stmt->errno === 1062) {

                                $error =
                                    "این شماره موبایل قبلاً ثبت شده است.";

                            } else {

                                $error =
                                    "ثبت نام انجام نشد. دوباره تلاش کنید.";

                            }

                        }


                        $stmt->close();

                    }

                }

            }


            $check->close();

        }

    }

}


include("./include/header.php");

?>


<main class="register-page">

    <section class="register-card">


        <!-- =================================================
                             BRAND
        ================================================== -->

        <div class="register-brand">

            <a
                href="index.php"
                class="register-logo"
            >

                Dev<span>Market</span>

            </a>


            <h1>
                ایجاد حساب کاربری
            </h1>


            <p>
                برای خرید و مدیریت محصولات دیجیتال
                حساب کاربری خود را ایجاد کنید.
            </p>

        </div>


        <!-- =================================================
                             ERROR
        ================================================== -->

        <?php if ($error): ?>

            <div class="form-message error">

                <?= htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
                             REGISTER FORM
        ================================================== -->

        <form
            action=""
            method="POST"
            class="register-form"
            autocomplete="off"
        >


            <!-- =================================================
                              FIRST / LAST NAME
            ================================================== -->

            <div class="form-row">


                <!-- FIRST NAME -->

                <div class="input-group">

                    <label for="firstname">
                      نام (به انگلیسی)
                    </label>


                    <div class="input-wrapper">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <circle
                                cx="12"
                                cy="8"
                                r="4"
                            ></circle>

                            <path
                                d="M4 21a8 8 0 0 1 16 0"
                            ></path>

                        </svg>


                        <input
                            type="text"
                            id="firstname"
                            name="firstname"
                            maxlength="50"
                            pattern="[A-Za-z]+"
                            placeholder="First name"
                            value="<?= htmlspecialchars(
                                $firstname,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            autocomplete="given-name"
                            required
                        >

                    </div>

                </div>


                <!-- LAST NAME -->

                <div class="input-group">

                    <label for="lastname">
                       نام خانوداگی (به انگلیسی) 
                    </label>


                    <div class="input-wrapper">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <circle
                                cx="12"
                                cy="8"
                                r="4"
                            ></circle>

                            <path
                                d="M4 21a8 8 0 0 1 16 0"
                            ></path>

                        </svg>


                        <input
                            type="text"
                            id="lastname"
                            name="lastname"
                            maxlength="80"
                            pattern="[A-Za-z]+"
                            placeholder="Last name"
                            value="<?= htmlspecialchars(
                                $lastname,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            autocomplete="family-name"
                            required
                        >

                    </div>

                </div>

            </div>


            <!-- =================================================
                              MOBILE
            ================================================== -->

            <div class="input-group">

                <label for="mobile">
                    شماره موبایل
                </label>


                <div class="otp-input-row">


                    <div class="input-wrapper mobile-input">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <rect
                                x="6"
                                y="2.5"
                                width="12"
                                height="19"
                                rx="2"
                            ></rect>

                            <path
                                d="M10 18.5h4"
                            ></path>

                        </svg>


                        <input
                            type="tel"
                            id="mobile"
                            name="mobile"
                            maxlength="11"
                            inputmode="numeric"
                            pattern="09[0-9]{9}"
                            placeholder="09123456789"
                            value="<?= htmlspecialchars(
                                $mobile,
                                ENT_QUOTES,
                                "UTF-8"
                            ) ?>"
                            autocomplete="tel"
                            required
                        >

                    </div>


                    <button
                        type="button"
                        id="sendOtp"
                        class="send-otp-btn"
                    >

                        ارسال کد

                    </button>


                </div>

            </div>


            <!-- =================================================
                              OTP
            ================================================== -->

            <div
                class="otp-box"
                id="otpBox"
            >

                <div class="input-group">

                    <label for="otp">
                        کد تایید
                    </label>


                    <div class="input-wrapper">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <rect
                                x="3"
                                y="5"
                                width="18"
                                height="14"
                                rx="2"
                            ></rect>

                            <path d="M7 9h.01"></path>
                            <path d="M11 9h.01"></path>
                            <path d="M15 9h.01"></path>

                            <path d="M7 13h.01"></path>
                            <path d="M11 13h.01"></path>
                            <path d="M15 13h.01"></path>

                        </svg>


                        <input
                            type="text"
                            id="otp"
                            name="otp"
                            maxlength="6"
                            inputmode="numeric"
                            pattern="[0-9]{6}"
                            placeholder="123456"
                        >

                    </div>

                </div>

            </div>


            <!-- =================================================
                              PASSWORD
            ================================================== -->

            <div class="form-row">


                <!-- PASSWORD -->

                <div class="input-group">

                    <label for="password">
                        رمز عبور
                    </label>


                    <div class="input-wrapper">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <rect
                                x="4"
                                y="10"
                                width="16"
                                height="11"
                                rx="2"
                            ></rect>

                            <path
                                d="M8 10V7a4 4 0 0 1 8 0v3"
                            ></path>

                        </svg>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            inputmode="numeric"
                            pattern="[0-9]+"
                            minlength="6"
                            placeholder="حداقل ۶ رقم"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                </div>


                <!-- PASSWORD REPEAT -->

                <div class="input-group">

                    <label for="password2">
                        تکرار رمز عبور
                    </label>


                    <div class="input-wrapper">

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <rect
                                x="4"
                                y="10"
                                width="16"
                                height="11"
                                rx="2"
                            ></rect>

                            <path
                                d="M8 10V7a4 4 0 0 1 8 0v3"
                            ></path>

                        </svg>


                        <input
                            type="password"
                            id="password2"
                            name="password2"
                            inputmode="numeric"
                            pattern="[0-9]+"
                            minlength="6"
                            placeholder="تکرار رمز"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                </div>

            </div>


            <!-- =================================================
                              TERMS
            ================================================== -->

            <label class="terms">

                <input
                    type="checkbox"
                    name="terms"
                    value="1"
                    required
                >


                <span>

                    قوانین و شرایط
                    <a href="law.php">
                        DevMarket
                    </a>
                    را می‌پذیرم.

                </span>

            </label>


            <!-- =================================================
                              SUBMIT
            ================================================== -->

            <button
                type="submit"
                class="register-submit"
            >

                ایجاد حساب کاربری

            </button>


            <!-- =================================================
                              LOGIN LINK
            ================================================== -->

            <div class="register-footer">

                <span>
                    قبلاً حساب ساخته‌اید؟
                </span>


                <a href="login.php">
                    ورود به حساب
                </a>

            </div>


        </form>

    </section>

</main>


<?php include("./include/footer.php"); ?>