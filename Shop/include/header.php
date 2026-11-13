<?php

/* =========================================================
   SESSION
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   LOGIN STATUS
========================================================= */

$isLoggedIn = (
    isset($_SESSION["logged_in"]) &&
    $_SESSION["logged_in"] === true &&
    isset($_SESSION["user_id"])
);


$userId = $isLoggedIn
    ? (int) $_SESSION["user_id"]
    : "";


$firstname = $_SESSION["firstname"] ?? "";
$lastname  = $_SESSION["lastname"] ?? "";

$profileImage = $_SESSION["profile_image"] ?? "";

if ($profileImage === "") {
    $profileImage = "./assets/image/profile-default.png";
}


/* =========================================================
   CURRENT PAGE
========================================================= */

$currentPage = basename($_SERVER["PHP_SELF"]);


/* =========================================================
   HIDE SECOND NAV ON LOGIN / REGISTER
========================================================= */

$hideSecondNav = in_array(
    $currentPage,
    ["login.php", "register.php"],
    true
);

?>

<!DOCTYPE html>

<html lang="fa" dir="rtl">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>DevMarket</title>


    <!-- =================================================
                            VAZIRMATN
    ================================================== -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- =================================================
                            MAIN CSS
    ================================================== -->

    <link
        rel="stylesheet"
        href="./css/style.css"
    >

</head>


<body
    data-logged-in="<?= $isLoggedIn ? 'true' : 'false' ?>"
    data-user-id="<?= $userId ?>"
>

<!-- =====================================================
                        PAGE LOADER
====================================================== -->

<div id="pageLoader" class="page-loader">

    <div class="page-loader-card">

        <div class="page-loader-logo">
            DevMarket
        </div>

        <div class="page-loader-dots">
            <span></span>
            <span></span>
            <span></span>
        </div>

    </div>

</div>

<img
    src="./assets/Ads/Ads3.gif"
    style="object-fit: cover; top: 0; left: 0; right: 0; bottom: 0; width: 100%; position: fixed; height: 35px;"
    alt=""
>


<!-- =====================================================
                        SITE HEADER
====================================================== -->

<header class="site-header">


    <!-- =================================================
                        HEADER TOP
    ================================================== -->

    <div class="header-top">

        <div class="container header-top-inner">


            <!-- =================================================
                                LOGO
            ================================================== -->

            <a
                href="index.php"
                class="logo"
            >

                <span>DevMarket</span>

            </a>


            <!-- =================================================
                              SEARCH
            ================================================== -->

            <div class="search-box">

                <input
                    type="text"
                    placeholder="جستجوی محصول..."
                    autocomplete="off"
                >


                <button
                    type="button"
                    class="search-icon"
                    aria-label="جستجو"
                >

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <circle
                            cx="11"
                            cy="11"
                            r="7"
                        ></circle>

                        <path
                            d="m20 20-4-4"
                        ></path>

                    </svg>

                </button>

            </div>


            <!-- =================================================
                        HEADER ACTIONS
            ================================================== -->

            <div class="actions">


                <!-- =================================================
                            THEME TOGGLE
                ================================================== -->

                <button
                    type="button"
                    class="header-action theme-toggle"
                    id="themeToggle"
                    aria-label="تغییر حالت نمایش"
                    title="تغییر حالت نمایش"
                >


                    <!-- SUN -->

                    <svg
                        class="theme-icon theme-icon-dark"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <circle
                            cx="12"
                            cy="12"
                            r="4"
                        ></circle>

                        <path d="M12 2v2"></path>
                        <path d="M12 20v2"></path>

                        <path
                            d="m4.93 4.93 1.41 1.41"
                        ></path>

                        <path
                            d="m17.66 17.66 1.41 1.41"
                        ></path>

                        <path d="M2 12h2"></path>
                        <path d="M20 12h2"></path>

                        <path
                            d="m6.34 17.66-1.41 1.41"
                        ></path>

                        <path
                            d="m19.07 4.93-1.41 1.41"
                        ></path>

                    </svg>


                    <!-- MOON -->

                    <svg
                        class="theme-icon theme-icon-light"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <path
                            d="M21 12.79A9 9 0 1 1 11.21 3
                               7 7 0 0 0 21 12.79Z"
                        ></path>

                    </svg>

                </button>


                <!-- =================================================
                            WISHLIST
                ================================================== -->

                <a
                    href="#"
                    class="header-action header-icon-gap"
                    aria-label="علاقه‌مندی‌ها"
                >

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <path
                            d="M20.8 8.6
                               c0 5.5-8.8 10.4-8.8 10.4
                               S3.2 14.1 3.2 8.6
                               A4.4 4.4 0 0 1 12 6.2
                               a4.4 4.4 0 0 1 8.8 2.4Z"
                        ></path>

                    </svg>

                </a>


                <!-- =================================================
                                CART
                ================================================== -->

                <a
                    href="payment.php"
                    class="header-action cart-action header-icon-gap"
                    aria-label="سبد خرید"
                >

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <path
                            d="M3 3h2l2.4 11.2
                               a2 2 0 0 0 2 1.6
                               h7.8
                               a2 2 0 0 0 2-1.6
                               L21 7H6"
                        ></path>

                        <circle
                            cx="10"
                            cy="20"
                            r="1"
                        ></circle>

                        <circle
                            cx="18"
                            cy="20"
                            r="1"
                        ></circle>

                    </svg>


                    <span class="cart-count">
                        0
                    </span>

                </a>


                <!-- =================================================
                        LOGIN / USER / LOGOUT
                ================================================== -->

                <?php if ($isLoggedIn): ?>


                    <!-- =================================================
                            USER PROFILE
                    ================================================== -->

                    <div class="user-profile">


                        <!-- PROFILE BUTTON -->

                        <button
                            type="button"
                            class="user-profile-button"
                            id="userProfileButton"
                            aria-label="منوی حساب کاربری"
                            aria-expanded="false"
                        >


                         <!-- ARROW -->

                            <svg
                                class="profile-arrow"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >

                                <path
                                    d="m6 9 6 6 6-6"
                                ></path>

                            </svg>


                            <!-- USER NAME -->

                            <span class="profile-user-name">

                                <?= htmlspecialchars(
                                    trim(
                                        $firstname . " " . $lastname
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>

                            </span>


                            <!-- PROFILE IMAGE -->

                            <span class="profile-avatar">

                                <img
                                    src="<?= htmlspecialchars($profileImage, ENT_QUOTES, "UTF-8") ?>"
                                    alt="پروفایل"
                                >

                            </span>


                        </button>


                        <!-- =================================================
                                USER DROPDOWN
                        ================================================== -->

                        <div
                            class="user-dropdown"
                            id="userDropdown"
                        >


                            <!-- EDIT ACCOUNT -->

                            <a
                                href="profile.php"
                                class="user-dropdown-item"
                            >

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >

                                    <path
                                        d="M20 21a8 8 0 0 0-16 0"
                                    ></path>

                                    <circle
                                        cx="12"
                                        cy="7"
                                        r="4"
                                    ></circle>

                                </svg>


                                <span>
                                    ویرایش حساب
                                </span>


                            </a>


                            <!-- DIVIDER -->

                            <div class="user-dropdown-divider"></div>


                            <!-- LOGOUT -->

                            <a
                                href="logout.php"
                                class="user-dropdown-item logout-item"
                            >

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >

                                    <path
                                        d="m16 17 5-5-5-5"
                                    ></path>

                                    <path
                                        d="M21 12H9"
                                    ></path>

                                    <path
                                        d="M9 21H5a2 2 0 0 1-2-2V5
                                           a2 2 0 0 1 2-2h4"
                                    ></path>

                                </svg>


                                <span>
                                    خروج از حساب
                                </span>


                            </a>


                        </div>


                    </div>


                <?php else: ?>


                    <!-- LOGIN -->

                    <a
                        href="login.php"
                        class="login header-icon-gap"
                    >

                        ورود

                    </a>


                <?php endif; ?>


            </div>


            <!-- =================================================
                          MOBILE MENU
            ================================================== -->

            <button
                type="button"
                class="mobile-menu"
                aria-label="باز کردن منو"
            >

                <span></span>
                <span></span>
                <span></span>

            </button>


        </div>

    </div>


    <!-- =================================================
                         SECOND NAV
    ================================================== -->

    <div
        class="header-nav-container <?= $hideSecondNav ? "page-nav-hidden" : "" ?>"
    >

        <div class="container">

            <nav class="header-nav">

                <ul class="menu">


                    <!-- =================================================
                                HOME
                    ================================================== -->

                    <li>

                        <a href="index.php">

                            خانه

                        </a>

                    </li>


                    <!-- =================================================
                            CATEGORIES
                    ================================================== -->

                    <li>

                        <a href="about.php">

                            درباره ما

                        </a>

                    </li>


                    <!-- =================================================
                                PRODUCTS
                    ================================================== -->

                    <li>

                        <a href="products.php">

                            محصولات

                        </a>

                    </li>


                    <!-- =================================================
                                INCREDIBLE OFFER
                    ================================================== -->

                    <li>

                        <a href="incredible-offers.php">

                            تخفیف های ویژه

                        </a>

                    </li>


                </ul>

            </nav>

        </div>

    </div>


</header>