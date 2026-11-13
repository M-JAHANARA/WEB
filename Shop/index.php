<?php

require_once "./db.php";
require_once "./include/header.php";


/*
|--------------------------------------------------------------------------
| محصولات تخفیف‌های ویژه
|--------------------------------------------------------------------------
| محصولات مستقیماً از دیتابیس خوانده می‌شوند.
|--------------------------------------------------------------------------
*/

$discountProducts = [];

$discountProductsSql = "
    SELECT
        p.id,
        p.title,
        p.price,
        p.old_price,
        p.discount_end_at,

        c.name AS category_name,
        c.slug AS category,

        pi.image_path AS image

    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    LEFT JOIN product_images pi
        ON pi.product_id = p.id
        AND pi.is_primary = 1

    WHERE
        p.is_active = 1
        AND p.is_discounted = 1

    ORDER BY p.id ASC
";

$discountProductsResult = mysqli_query(
    $conn,
    $discountProductsSql
);

if ($discountProductsResult) {

    while (
        $product = mysqli_fetch_assoc(
            $discountProductsResult
        )
    ) {

        $price = (int) $product["price"];
        $oldPrice = (int) $product["old_price"];

        $discount = 0;

        if (
            $oldPrice > 0 &&
            $oldPrice > $price
        ) {

            $discount = round(
                (
                    ($oldPrice - $price)
                    / $oldPrice
                ) * 100
            );

        }

        $discountProducts[] = [

            "id" => (int) $product["id"],

            "title" => $product["title"],

            "category" => $product["category"],

            "category_name" => $product["category_name"],

            "price" => $price,

            "old_price" => $oldPrice,

            "discount" => $discount,

            "image" => $product["image"],

            "discount_end_at" =>
                $product["discount_end_at"]

        ];

    }

}


/*
|--------------------------------------------------------------------------
| تاریخ پایان شمارنده تخفیف‌های ویژه
|--------------------------------------------------------------------------
| نزدیک‌ترین زمان پایان تخفیف فعال
|--------------------------------------------------------------------------
*/

$discountCountdownEnd = "";

$discountCountdownSql = "
    SELECT MIN(discount_end_at) AS discount_end_at
    FROM products
    WHERE
        is_active = 1
        AND is_discounted = 1
        AND discount_end_at IS NOT NULL
        AND discount_end_at > NOW()
";

$discountCountdownResult = mysqli_query(
    $conn,
    $discountCountdownSql
);

if ($discountCountdownResult) {

    $discountCountdownRow =
        mysqli_fetch_assoc(
            $discountCountdownResult
        );

    if (
        $discountCountdownRow &&
        !empty($discountCountdownRow["discount_end_at"])
    ) {

        $discountCountdownEnd =
            $discountCountdownRow["discount_end_at"];

    }

}


/*
|--------------------------------------------------------------------------
| محصولات به‌زودی
|--------------------------------------------------------------------------
| فقط محصولاتی که is_upcoming = 1 هستند
|--------------------------------------------------------------------------
*/

$comingProducts = [];

$comingProductsSql = "
    SELECT
        p.id,
        p.title,
        p.short_description,

        c.name AS category_name,
        c.slug AS category,

        pi.image_path AS image

    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    LEFT JOIN product_images pi
        ON pi.product_id = p.id
        AND pi.is_primary = 1

    WHERE
        p.is_active = 1
        AND p.is_upcoming = 1

    ORDER BY p.id ASC
";

$comingProductsResult = mysqli_query(
    $conn,
    $comingProductsSql
);

if ($comingProductsResult) {

    while (
        $product = mysqli_fetch_assoc(
            $comingProductsResult
        )
    ) {

        $comingProducts[] = [

            "id" => (int) $product["id"],

            "title" => $product["title"],

            "description" =>
                $product["short_description"],

            "category" =>
                $product["category"],

            "category_name" =>
                $product["category_name"],

            "image" =>
                $product["image"]

        ];

    }

}

?>

<main class="main">

     <!-- بنر -->
    <div class="container">

        <section class="banner">

            <div class="banner-slider">

                <div class="slide active">
                    <img src="./assets/image/banner1.png" alt="">
                </div>

                <div class="slide">
                    <img src="./assets/image/banner2.png" alt="">
                </div>

                <div class="slide">
                    <img src="./assets/image/banner3.png" alt="">
                </div>

                <div class="slide">
                    <img src="./assets/image/banner4.png" alt="">
                </div>

                <div class="slide">
                    <img src="./assets/image/banner5.png" alt="">
                </div>

                <button class="prev" type="button">&#10094;</button>
                <button class="next" type="button">&#10095;</button>

                <div class="dots"></div>

            </div>

        </section>

    </div>


    <!-- دسته بندی ها -->

    <div class="container">

       <section class="categories-section">

            <div class="container">

                <div class="categories-header">

                    <h2>
                        دسته‌بندی‌ها
                    </h2>

                </div>

                <div class="categories-list">

                    <a
                        href="#"
                        class="category-item"
                    >

                        <div class="category-image">

                            <img
                                src="./assets/image/categories/web-template.png"
                                alt="قالب‌های وب"
                            >

                        </div>

                        <span>
                            قالب‌های وب
                        </span>

                    </a>


                    <a
                        href="#"
                        class="category-item"
                    >

                        <div class="category-image">

                            <img
                                src="./assets/image/categories/ui-kit.png"
                                alt="UI Kit"
                            >

                        </div>

                        <span>
                            UI Kit
                        </span>

                    </a>


                    <a
                        href="#"
                        class="category-item"
                    >

                        <div class="category-image">

                            <img
                                src="./assets/image/categories/python.png"
                                alt="پایتون"
                            >

                        </div>

                        <span>
                            پروژه‌های پایتون
                        </span>

                    </a>


                    <a
                        href="#"
                        class="category-item"
                    >

                        <div class="category-image">

                            <img
                                src="./assets/image/categories/php-mysql.png"
                                alt="PHP و MySQL"
                            >

                        </div>

                        <span>
                            PHP / MySQL
                        </span>

                    </a>


                    <a
                        href="#"
                        class="category-item"
                    >

                        <div class="category-image">

                            <img
                                src="./assets/image/categories/javascript.png"
                                alt="JavaScript"
                            >

                        </div>

                        <span>
                            JavaScript
                        </span>

                    </a>


                    <a
                        href="#"
                        class="category-item"
                    >

                        <div class="category-image">

                            <img
                                src="./assets/image/categories/projects.png"
                                alt="پروژه‌های آماده"
                            >

                        </div>

                        <span>
                            پروژه‌های آماده
                        </span>

                    </a>

                </div>

            </div>

        </section>

    </div>


<!-- ================= SPECIAL DISCOUNTS ================= -->

<section class="special-discounts">

    <div class="container">

        <div class="special-discounts-box">

            <!-- ================= SIDE ================= -->

            <aside class="discount-side">

                <div class="discount-percent-icon">
                    %
                </div>

                <h2>
                     تخفیف‌های ویژه
                </h2>

                <div
                    class="discount-countdown"
                    id="discountCountdown"
                    data-end="<?php
                        echo htmlspecialchars(
                            $discountCountdownEnd,
                            ENT_QUOTES,
                            "UTF-8"
                        );
                    ?>"
                >

                    <div class="countdown-item">

                        <strong id="discountDays">
                            00
                        </strong>

                    </div>

                    <span>:</span>

                    <div class="countdown-item">

                        <strong id="discountHours">
                            00
                        </strong>

                    </div>

                    <span>:</span>

                    <div class="countdown-item">

                        <strong id="discountMinutes">
                            00
                        </strong>

                    </div>

                    <span>:</span>

                    <div class="countdown-item">

                        <strong id="discountSeconds">
                            00
                        </strong>

                    </div>

                </div>

                <a
                    href="incredible-offers.php"
                    class="discount-all-button"
                >

                    <span>
                        مشاهده همه
                    </span>

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <path d="M19 12H5"></path>

                        <path d="m12 19-7-7 7-7"></path>

                    </svg>

                </a>

            </aside>


            <!-- ================= PRODUCTS ================= -->

            <div class="discount-products-wrapper">

                <button
                    type="button"
                    class="discount-arrow discount-arrow-prev"
                    aria-label="محصولات قبلی"
                >

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <path d="M9 18l6-6-6-6"></path>

                    </svg>

                </button>


                <div
                    class="discount-products"
                    id="discountProducts"
                >

                    <?php if (
                        count($discountProducts) > 0
                    ): ?>


                        <?php foreach (
                            $discountProducts
                            as $product
                        ): ?>


                            <a
                                href="#"
                                class="discount-product"
                            >

                                <div class="discount-product-image">

                                    <?php if (
                                        !empty(
                                            $product["image"]
                                        )
                                    ): ?>

                                        <img
                                            src="<?php
                                                echo htmlspecialchars(
                                                    $product["image"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                );
                                            ?>"
                                            alt="<?php
                                                echo htmlspecialchars(
                                                    $product["title"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                );
                                            ?>"
                                        >

                                    <?php endif; ?>

                                </div>


                                <div class="discount-product-info">

                                    <h3>

                                        <?php
                                        echo htmlspecialchars(
                                            $product["title"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </h3>


                                    <div class="discount-price-row">

                                        <span class="old-price">

                                            <?php
                                            echo number_format(
                                                $product["old_price"]
                                            );
                                            ?>

                                        </span>


                                        <span class="discount-badge">

                                            <?php
                                            echo $product["discount"];
                                            ?>%

                                        </span>

                                    </div>


                                    <strong class="final-price">

                                        <?php
                                        echo number_format(
                                            $product["price"]
                                        );
                                        ?>

                                        <small>
                                            تومان
                                        </small>

                                    </strong>

                                </div>

                            </a>


                        <?php endforeach; ?>


                    <?php endif; ?>

                </div>


                <button
                    type="button"
                    class="discount-arrow discount-arrow-next"
                    aria-label="محصولات بعدی"
                >

                    <svg
                        viewBox="0 0 24 24"

                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <path d="M15 18l-6-6 6-6"></path>

                    </svg>

                </button>

            </div>

        </div>

    </div>

</section>

<!-- ================= END SPECIAL DISCOUNTS ================= -->


<!-- ================= COMING SOON ================= -->

<section class="coming-soon">

    <div class="container">

        <div class="coming-soon-box">

            <!-- ================= SIDE ================= -->

            <aside class="coming-soon-side">

                <div class="coming-soon-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <path d="M7 3h10"></path>

                        <path d="M7 21h10"></path>

                        <path d="M8 3c0 4 4 5 4 9s-4 5-4 9"></path>

                        <path d="M16 3c0 4-4 5-4 9s4 5 4 9"></path>

                        <path d="M9 6.5h6"></path>

                        <path d="M9 17.5h6"></path>

                        <path d="M9.5 6.5c.5 1.5 1.5 2.2 2.5 2.2s2-.7 2.5-2.2"></path>

                        <path d="M9.5 17.5c.5-1.5 1.5-2.2 2.5-2.2s2 .7 2.5 2.2"></path>

                    </svg>

                </div>


                <h2>
                    به‌زودی
                </h2>

                <div class="coming-soon-title">

                    محصولات جدید در راه هستند

                </div>

            </aside>


            <!-- ================= PRODUCTS ================= -->

            <div class="coming-products-wrapper">


                <!-- PREVIOUS -->

                <button
                    type="button"
                    class="coming-arrow coming-arrow-prev"
                    aria-label="محصولات قبلی"
                >

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <path d="M9 18l6-6-6-6"></path>

                    </svg>

                </button>


                <!-- PRODUCTS -->

                <div
                    class="coming-products"
                    id="comingProducts"
                >

                    <?php if (
                        count($comingProducts) > 0
                    ): ?>


                        <?php foreach (
                            $comingProducts
                            as $product
                        ): ?>


                            <article class="coming-product">

                                <div class="coming-product-image">

                                    <?php if (
                                        !empty(
                                            $product["image"]
                                        )
                                    ): ?>

                                        <img
                                            src="<?php
                                                echo htmlspecialchars(
                                                    $product["image"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                );
                                            ?>"
                                            alt="<?php
                                                echo htmlspecialchars(
                                                    $product["title"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                );
                                            ?>"
                                        >

                                    <?php endif; ?>


                                    <span class="coming-product-label">

                                        به‌زودی

                                    </span>

                                </div>


                                <div class="coming-product-info">

                                    <h3>

                                        <?php
                                        echo htmlspecialchars(
                                            $product["title"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </h3>


                                    <p>

                                        <?php
                                        echo htmlspecialchars(
                                            $product["description"] ?? "",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>

                                    </p>

                                </div>

                            </article>


                        <?php endforeach; ?>


                    <?php endif; ?>

                </div>


                <!-- NEXT -->

                <button
                    type="button"
                    class="coming-arrow coming-arrow-next"
                    aria-label="محصولات بعدی"
                >

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >

                        <path d="M15 18l-6-6 6-6"></path>

                    </svg>

                </button>


            </div>

        </div>

    </div>

</section>

<!-- =========================================================
     Reviews Section
========================================================= -->

<section class="reviews-section">

    <div class="container">

        <!-- title form -->

        <div class="reviews-heading">

            <h2>
                نظرات کاربران
            </h2>

        </div>


        <!-- =====================================================
             form rating
        ====================================================== -->

        <div class="review-form-box">

            <form
                id="reviewForm"
                method="post"
                novalidate
            >

                <!-- phone number -->

                <div class="review-field">

                    <label for="reviewMobile">
                        شماره موبایل
                    </label>

                    <input
                        type="text"
                        id="reviewMobile"
                        name="mobile"
                        maxlength="11"
                        inputmode="numeric"
                        autocomplete="tel"
                        placeholder="0912xxxxxxx"
                        required
                    >

                </div>


                <!-- rating -->

                <div class="review-field">

                    <label>
                        امتیاز شما
                    </label>


                    <div
                        class="star-selector"
                        id="starSelector"
                        aria-label="انتخاب امتیاز"
                    >

                        <button
                            type="button"
                            data-rating="1"
                            aria-label="۱ ستاره"
                        >
                            ☆
                        </button>

                        <button
                            type="button"
                            data-rating="2"
                            aria-label="۲ ستاره"
                        >
                            ☆
                        </button>

                        <button
                            type="button"
                            data-rating="3"
                            aria-label="۳ ستاره"
                        >
                            ☆
                        </button>

                        <button
                            type="button"
                            data-rating="4"
                            aria-label="۴ ستاره"
                        >
                            ☆
                        </button>

                        <button
                            type="button"
                            data-rating="5"
                            aria-label="۵ ستاره"
                        >
                            ☆
                        </button>

                    </div>


                    <input
                        type="hidden"
                        id="reviewRating"
                        name="rating"
                        value="0"
                    >

                </div>


                <!-- متن نظر -->

                <div class="review-field">

                    <label for="reviewText">
                        نظر شما
                    </label>


                    <textarea
                        id="reviewText"
                        name="comment"
                        maxlength="1000"
                        placeholder="نظر خود را درباره فروشگاه بنویسید..."
                        required
                    ></textarea>


                    <div class="review-counter">

                        <span id="reviewCounter">
                            ۰ / ۱۰۰۰
                        </span>

                    </div>

                </div>


                <!-- دکمه و پیام -->

                <div class="review-form-bottom">

                    <div
                        id="reviewMessage"
                        class="review-message"
                    ></div>


                    <button
                        type="submit"
                        id="reviewSubmit"
                        class="review-submit"
                    >
                        ثبت نظر
                    </button>

                </div>

            </form>

        </div>


        <!-- =====================================================
             لیست نظرات
        ====================================================== -->

        <div class="reviews-list-section">


            <div class="reviews-list-title">

                <h3>
                    آخرین نظرات
                </h3>

            </div>


            <div
                id="reviewsList"
                class="reviews-list"
            >

                <?php

                /*
                |--------------------------------------------------------------------------
                | دریافت نظرات
                |--------------------------------------------------------------------------
                */

                $reviewsQuery = $conn->query("
                    SELECT
                        id,
                        mobile,
                        rating,
                        comment,
                        created_at
                    FROM comments
                    ORDER BY
                        rating DESC,
                        created_at DESC
                ");

                ?>


                <?php if (
                    $reviewsQuery &&
                    $reviewsQuery->num_rows > 0
                ): ?>


                    <?php while (
                        $review =
                        $reviewsQuery->fetch_assoc()
                    ): ?>


                        <?php

                        /*
                        |--------------------------------------------------------------------------
                        | phone number
                        |--------------------------------------------------------------------------
                        */

                        $mobile =
                            (string) $review["mobile"];


                        $maskedMobile =
                            substr(
                                $mobile,
                                0,
                                4
                            )
                            . "****"
                            . substr(
                                $mobile,
                                -3
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | rating
                        |--------------------------------------------------------------------------
                        */

                        $rating =
                            (int) $review["rating"];


                        /*
                        |--------------------------------------------------------------------------
                        | date
                        |--------------------------------------------------------------------------
                        */

                        $displayDate =
                            date(
                                "Y/m/d - H:i",
                                strtotime(
                                    $review["created_at"]
                                )
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | متن نظر
                        |--------------------------------------------------------------------------
                        */

                        $safeComment =
                            htmlspecialchars(
                                $review["comment"],
                                ENT_QUOTES,
                                "UTF-8"
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | شماره موبایل امن
                        |--------------------------------------------------------------------------
                        */

                        $safeMobile =
                            htmlspecialchars(
                                $maskedMobile,
                                ENT_QUOTES,
                                "UTF-8"
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | تاریخ امن
                        |--------------------------------------------------------------------------
                        */

                        $safeDate =
                            htmlspecialchars(
                                $displayDate,
                                ENT_QUOTES,
                                "UTF-8"
                            );

                        ?>


                        <!-- =================================================
                             یک نظر
                        ================================================== -->

                        <article
                            class="review-item"
                            data-rating="<?php echo $rating; ?>"
                            data-review-id="<?php echo (int) $review["id"]; ?>"
                        >


                            <!-- بالای نظر -->

                            <div class="review-item-top">


                                <!-- ستاره‌ها -->

                                <div class="review-stars">

                                    <?php

                                    for (
                                        $i = 1;
                                        $i <= 5;
                                        $i++
                                    ) {

                                        if ($i <= $rating) {

                                            echo "★";

                                        } else {

                                            echo "☆";

                                        }

                                    }

                                    ?>

                                </div>


                                <!-- شماره موبایل -->

                                <div class="review-mobile">

                                    <?php
                                    echo $safeMobile;
                                    ?>

                                </div>


                            </div>


                            <!-- متن نظر -->

                            <div class="review-text">

                                <?php

                                echo nl2br(
                                    $safeComment
                                );

                                ?>

                            </div>


                            <!-- تاریخ -->

                            <div class="review-date">

                                <?php
                                echo $safeDate;
                                ?>

                            </div>


                        </article>


                    <?php endwhile; ?>


                <?php else: ?>


                    <!-- وقتی هنوز نظری وجود ندارد -->

                    <div
                        class="no-reviews"
                        id="noReviews"
                    >

                        هنوز نظری ثبت نشده است.

                    </div>


                <?php endif; ?>


            </div>

        </div>

    </div>

</section>

</main>

<?php include("./include/footer.php"); ?>