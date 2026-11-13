<?php

session_start();

require_once "./db.php";

$error = "";

$mobile = "";
$password = "";


/* =========================================================
   LOGIN
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $mobile = trim($_POST["mobile"] ?? "");
    $password = $_POST["password"] ?? "";

    /* =====================================================
       VALIDATION
    ===================================================== */

    if (!preg_match('/^09[0-9]{9}$/', $mobile)) {

        $error =
            "شماره موبایل را به صورت صحیح وارد کنید.";

    } elseif ($password === "") {

        $error =
            "رمز عبور را وارد کنید.";

    } elseif (strlen($password) < 6) {

        $error =
            "رمز عبور باید حداقل ۶ کاراکتر باشد.";

    } else {


        /* =================================================
           FIND USER
        ================================================= */

        $stmt = $conn->prepare(
            "SELECT
                id,
                firstname,
                lastname,
                mobile,
                password
             FROM users
             WHERE mobile = ?
             LIMIT 1"
        );


        if (!$stmt) {

            $error = "خطایی در پردازش درخواست رخ داد.";

        } else {

            $stmt->bind_param(
                "s",
                $mobile
            );

            $stmt->execute();

            $result = $stmt->get_result();


            /* =============================================
               USER FOUND
            ============================================= */

            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();


                /* =============================================
                   PASSWORD VERIFY
                ============================================= */

                if (
                    password_verify(
                        $password,
                        $user["password"]
                    )
                ) {


                    /* =========================================
                       SESSION REGENERATION
                    ========================================= */

                    session_regenerate_id(true);


                    /* =========================================
                       LOGIN SESSION
                    ========================================= */

                    $_SESSION["user_id"] =
                        (int) $user["id"];


                    $_SESSION["firstname"] =
                        $user["firstname"];


                    $_SESSION["lastname"] =
                        $user["lastname"];


                    $_SESSION["mobile"] =
                        $user["mobile"];


                    $_SESSION["profile_image"] = 
                    $user["profile_image"] ?? "";


                    $_SESSION["logged_in"] =
                        true;


                    /* =========================================
                       LOGIN SUCCESS
                    ========================================= */

                    header(
                        "Location: index.php"
                    );

                    exit;

                } else {

                    /*
                     * عمداً یک پیام عمومی می‌دهیم
                     * تا مشخص نشود شماره وجود دارد
                     * یا رمز اشتباه بوده است.
                     */

                    $error =
                        "شماره موبایل یا رمز عبور اشتباه است.";

                }

            } else {

                /*
                 * کاربر وجود ندارد.
                 */

                $error =
                    "شماره موبایل یا رمز عبور اشتباه است.";

            }


            $stmt->close();

        }

    }

}


include("./include/header.php");

?>


<main class="login-page">

    <section class="login-card">


        <!-- =================================================
             BRAND
        ================================================== -->

        <div class="login-brand">

            <a
                href="index.php"
                class="login-logo">

                Dev<span>Market</span>

            </a>


            <h1>
                خوش آمدید
            </h1>


            <p>
                برای ورود به حساب کاربری خود اطلاعات
                را وارد کنید.
            </p>

        </div>


        <!-- =================================================
             REGISTER SUCCESS MESSAGE
        ================================================== -->

        <?php if (
            isset($_GET["registered"]) &&
            $_GET["registered"] === "1"
        ): ?>

            <div class="form-message success">

                ثبت نام با موفقیت انجام شد.
                اکنون وارد حساب خود شوید.

            </div>

        <?php endif; ?>


        <!-- =================================================
             ERROR MESSAGE
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
             LOGIN FORM
        ================================================== -->

        <form
            action=""
            method="POST"
            class="login-form"
            autocomplete="on">


            <!-- =================================================
                 MOBILE
            ================================================== -->

            <div class="input-group">

                <label for="loginMobile">
                    شماره موبایل
                </label>


                <div class="input-wrapper">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round">

                        <rect
                            x="6"
                            y="2.5"
                            width="12"
                            height="19"
                            rx="2">
                        </rect>

                        <path
                            d="M10 18.5h4">
                        </path>

                    </svg>


                    <input
                        type="tel"
                        id="loginMobile"
                        name="mobile"
                        maxlength="11"
                        inputmode="numeric"
                        placeholder="09xxxxxxxxx"
                        value="<?= htmlspecialchars(
                            $mobile,
                            ENT_QUOTES,
                            "UTF-8"
                        ) ?>"
                        autocomplete="off"
                        required>

                </div>

            </div>


            <!-- =================================================
                 PASSWORD
            ================================================== -->

            <div class="input-group">

                <div class="password-label">

                    <label for="loginPassword">
                        رمز عبور
                    </label>

                    <a href="#">
                        فراموشی رمز عبور؟
                    </a>

                </div>


                <div class="input-wrapper">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round">

                        <rect
                            x="4"
                            y="10"
                            width="16"
                            height="11"
                            rx="2">
                        </rect>

                        <path
                            d="M8 10V7a4 4 0 0 1 8 0v3">
                        </path>

                    </svg>


                    <input
                        type="password"
                        id="loginPassword"
                        name="password"
                        placeholder="رمز عبور خود را وارد کنید"
                        autocomplete="off"
                        required>

                </div>

            </div>


            <!-- =================================================
                 REMEMBER ME
            ================================================== -->

            <label class="remember-me">

                <input
                    type="checkbox"
                    name="remember"
                    value="1">

                <span>
                    مرا به خاطر بسپار
                </span>

            </label>


            <!-- =================================================
                 LOGIN BUTTON
            ================================================== -->

            <button
                type="submit"
                class="login-submit">

                ورود به حساب

            </button>


            <!-- =================================================
                 REGISTER LINK
            ================================================== -->

            <div class="login-footer">

                <span>
                    حساب کاربری ندارید؟
                </span>


                <a href="register.php">
                    ایجاد حساب
                </a>

            </div>


        </form>

    </section>

</main>


<?php include("./include/footer.php"); ?>