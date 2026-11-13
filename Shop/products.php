<?php

require_once "./db.php";
require_once "./include/header.php";


/*
|--------------------------------------------------------------------------
| توابع
|--------------------------------------------------------------------------
*/

function persianNumber($number)
{
    $english = [
        "0", "1", "2", "3", "4",
        "5", "6", "7", "8", "9"
    ];

    $persian = [
        "۰", "۱", "۲", "۳", "۴",
        "۵", "۶", "۷", "۸", "۹"
    ];

    return str_replace(
        $english,
        $persian,
        number_format((int) $number)
    );
}


/*
|--------------------------------------------------------------------------
| دریافت فیلترها
|--------------------------------------------------------------------------
*/

$selectedCategory = isset($_GET["category"])
    ? trim((string) $_GET["category"])
    : "";

$minPrice = isset($_GET["min_price"])
    ? (int) $_GET["min_price"]
    : 0;

$maxPrice = isset($_GET["max_price"])
    ? (int) $_GET["max_price"]
    : 0;

$selectedSort = isset($_GET["sort"])
    ? trim((string) $_GET["sort"])
    : "newest";


/*
|--------------------------------------------------------------------------
| جلوگیری از قیمت نامعتبر
|--------------------------------------------------------------------------
*/

if ($minPrice < 0) {
    $minPrice = 0;
}

if ($maxPrice < 0) {
    $maxPrice = 0;
}


/*
|--------------------------------------------------------------------------
| اگر حداقل قیمت از حداکثر بیشتر بود
|--------------------------------------------------------------------------
*/

if (
    $minPrice > 0 &&
    $maxPrice > 0 &&
    $minPrice > $maxPrice
) {
    $temp = $minPrice;
    $minPrice = $maxPrice;
    $maxPrice = $temp;
}


/*
|--------------------------------------------------------------------------
| مرتب‌سازی‌های مجاز
|--------------------------------------------------------------------------
*/

$allowedSorts = [
    "newest",
    "oldest",
    "price_low",
    "price_high",
    "popular"
];

if (!in_array($selectedSort, $allowedSorts, true)) {
    $selectedSort = "newest";
}


/*
|--------------------------------------------------------------------------
| مرتب‌سازی SQL
|--------------------------------------------------------------------------
|
| چون نام ستون نمی‌تواند با ? جایگزین شود،
| مقدار sort فقط از لیست سفید انتخاب می‌شود.
|
*/

switch ($selectedSort) {

    case "oldest":
        $orderBy = "p.created_at ASC";
        break;

    case "price_low":
        $orderBy = "p.price ASC";
        break;

    case "price_high":
        $orderBy = "p.price DESC";
        break;

    case "popular":
        $orderBy = "p.sales_count DESC, p.created_at DESC";
        break;

    case "newest":
    default:
        $orderBy = "p.created_at DESC";
        break;
}


/*
|--------------------------------------------------------------------------
| دریافت دسته‌بندی‌ها
|--------------------------------------------------------------------------
*/

$categoriesSql = "
    SELECT
        id,
        name,
        slug
    FROM categories
    WHERE is_active = 1
    ORDER BY sort_order ASC, id ASC
";

$categoriesResult = mysqli_query($conn, $categoriesSql);

if (!$categoriesResult) {
    die("خطا در دریافت دسته‌بندی‌ها.");
}

$categories = [];

while ($categoryRow = mysqli_fetch_assoc($categoriesResult)) {

    $categories[] = [
        "id" => (int) $categoryRow["id"],
        "name" => $categoryRow["name"],
        "slug" => $categoryRow["slug"]
    ];
}


/*
|--------------------------------------------------------------------------
| اعتبارسنجی دسته‌بندی
|--------------------------------------------------------------------------
*/

$validCategory = true;

if ($selectedCategory !== "") {

    $validCategory = false;

    foreach ($categories as $category) {

        if ($category["slug"] === $selectedCategory) {
            $validCategory = true;
            break;
        }
    }

    if (!$validCategory) {
        $selectedCategory = "";
    }
}


/*
|--------------------------------------------------------------------------
| صفحه‌بندی
|--------------------------------------------------------------------------
*/

$perPage = 16;

$currentPage = isset($_GET["page"])
    ? (int) $_GET["page"]
    : 1;

if ($currentPage < 1) {
    $currentPage = 1;
}


/*
|--------------------------------------------------------------------------
| Query شمارش محصولات
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*) AS total
    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    WHERE
        p.is_active = 1

        AND (
            ? = ''
            OR c.slug = ?
        )

        AND (
            ? = 0
            OR p.price >= ?
        )

        AND (
            ? = 0
            OR p.price <= ?
        )
";


$countStmt = mysqli_prepare($conn, $countSql);

if (!$countStmt) {
    die("خطا در آماده‌سازی Query.");
}


/*
|--------------------------------------------------------------------------
| پارامترهای شمارش
|--------------------------------------------------------------------------
*/

$countCategory1 = $selectedCategory;
$countCategory2 = $selectedCategory;

$countMin1 = $minPrice;
$countMin2 = $minPrice;

$countMax1 = $maxPrice;
$countMax2 = $maxPrice;


mysqli_stmt_bind_param(
    $countStmt,
    "ssiiii",
    $countCategory1,
    $countCategory2,
    $countMin1,
    $countMin2,
    $countMax1,
    $countMax2
);

mysqli_stmt_execute($countStmt);

$countResult = mysqli_stmt_get_result($countStmt);

if (!$countResult) {
    die("خطا در دریافت تعداد محصولات.");
}

$countRow = mysqli_fetch_assoc($countResult);

$totalProducts = (int) ($countRow["total"] ?? 0);

mysqli_stmt_close($countStmt);


/*
|--------------------------------------------------------------------------
| محاسبه صفحات
|--------------------------------------------------------------------------
*/

$totalPages = $totalProducts > 0
    ? (int) ceil($totalProducts / $perPage)
    : 1;

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $perPage;


/*
|--------------------------------------------------------------------------
| دریافت محصولات
|--------------------------------------------------------------------------
*/

$productSql = "
    SELECT

        p.id,
        p.title,
        p.slug,
        p.short_description,
        p.price,
        p.old_price,
        p.is_free,
        p.is_discounted,
        p.is_upcoming,
        p.sales_count,
        p.sales_target,
        p.discount_end_at,
        p.version,
        p.file_size,

        c.name AS category_name,
        c.slug AS category_slug,

        pi.image_path AS image

    FROM products p

    INNER JOIN categories c
        ON c.id = p.category_id

    LEFT JOIN product_images pi
        ON pi.product_id = p.id
        AND pi.is_primary = 1

    WHERE

        p.is_active = 1

        AND (
            ? = ''
            OR c.slug = ?
        )

        AND (
            ? = 0
            OR p.price >= ?
        )

        AND (
            ? = 0
            OR p.price <= ?
        )

    ORDER BY {$orderBy}

    LIMIT ? OFFSET ?
";


$productStmt = mysqli_prepare($conn, $productSql);

if (!$productStmt) {
    die("خطا در آماده‌سازی Query محصولات.");
}


/*
|--------------------------------------------------------------------------
| پارامترهای محصولات
|--------------------------------------------------------------------------
*/

$productCategory1 = $selectedCategory;
$productCategory2 = $selectedCategory;

$productMin1 = $minPrice;
$productMin2 = $minPrice;

$productMax1 = $maxPrice;
$productMax2 = $maxPrice;

$productLimit = $perPage;
$productOffset = $offset;


mysqli_stmt_bind_param(
    $productStmt,
    "ssiiiiii",
    $productCategory1,
    $productCategory2,
    $productMin1,
    $productMin2,
    $productMax1,
    $productMax2,
    $productLimit,
    $productOffset
);

mysqli_stmt_execute($productStmt);

$productResult = mysqli_stmt_get_result($productStmt);

if (!$productResult) {
    die("خطا در دریافت محصولات.");
}


/*
|--------------------------------------------------------------------------
| تبدیل محصولات به آرایه
|--------------------------------------------------------------------------
*/

$products = [];

while ($row = mysqli_fetch_assoc($productResult)) {

    $price = (int) $row["price"];
    $oldPrice = (int) $row["old_price"];

    $discount = 0;

    if (
        $oldPrice > 0 &&
        $oldPrice > $price
    ) {

        $discount = round(
            (($oldPrice - $price) / $oldPrice) * 100
        );
    }


    /*
    |--------------------------------------------------------------------------
    | درصد فروش
    |--------------------------------------------------------------------------
    */

    $sold = (int) $row["sales_count"];
    $salesTarget = (int) $row["sales_target"];

    $progress = 0;

    if ($salesTarget > 0) {

        $progress = round(
            ($sold / $salesTarget) * 100
        );

        if ($progress > 100) {
            $progress = 100;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | آرایه محصول
    |--------------------------------------------------------------------------
    */

    $products[] = [

        "id" => (int) $row["id"],

        "title" => $row["title"],

        "slug" => $row["slug"],

        "short_description" => $row["short_description"],

        "price" => $price,

        "old_price" => $oldPrice,

        "discount" => $discount,

        "is_free" => (int) $row["is_free"],

        "is_discounted" => (int) $row["is_discounted"],

        "is_upcoming" => (int) $row["is_upcoming"],

        "image" => $row["image"],

        "category_name" => $row["category_name"],

        "category_slug" => $row["category_slug"],

        "progress" => $progress,

        "sold" => $sold,

        "sales_target" => $salesTarget,

        "discount_end_at" => $row["discount_end_at"],

        "version" => $row["version"],

        "file_size" => $row["file_size"]
    ];
}

mysqli_stmt_close($productStmt);


/*
|--------------------------------------------------------------------------
| ساخت URL صفحه‌بندی
|--------------------------------------------------------------------------
*/

function productsPageUrl($page)
{
    $params = $_GET;

    $params["page"] = $page;

    return "products.php?" . http_build_query($params);
}

?>

<main class="products-page">

    <section class="products-main-section">

        <div class="container">

            <div class="products-layout">


                <!-- =====================================================
                     فیلتر
                ====================================================== -->

                <aside class="products-filter">

                    <div class="products-filter-header">

                        <div class="products-filter-header-title">

                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M4 6h16"/>
                                <path d="M7 12h10"/>
                                <path d="M10 18h4"/>
                            </svg>

                            <span>
                                فیلتر محصولات
                            </span>

                        </div>

                    </div>


                    <form
                        class="products-filter-form"
                        method="GET"
                        action="products.php"
                    >


                        <!-- =================================================
                             دسته‌بندی
                        ================================================== -->

                        <details
                            class="products-filter-dropdown"
                            open
                        >

                            <summary>

                                <span>
                                    دسته‌بندی
                                </span>

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>

                            </summary>


                            <div class="products-filter-dropdown-content">

                                <!-- همه -->

                                <label class="products-radio-option">

                                    <input
                                        type="radio"
                                        name="category"
                                        value=""
                                        <?php
                                        echo $selectedCategory === ""
                                            ? "checked"
                                            : "";
                                        ?>
                                    >

                                    <span class="products-radio-custom"></span>

                                    <span class="products-radio-label">
                                        همه دسته‌بندی‌ها
                                    </span>

                                </label>


                                <?php foreach ($categories as $category): ?>

                                    <label class="products-radio-option">

                                        <input
                                            type="radio"
                                            name="category"
                                            value="<?php echo htmlspecialchars(
                                                $category["slug"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ); ?>"
                                            <?php
                                            echo $selectedCategory === $category["slug"]
                                                ? "checked"
                                                : "";
                                            ?>
                                        >

                                        <span class="products-radio-custom"></span>

                                        <span class="products-radio-label">

                                            <?php
                                            echo htmlspecialchars(
                                                $category["name"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );
                                            ?>

                                        </span>

                                    </label>

                                <?php endforeach; ?>

                            </div>

                        </details>


                        <!-- =================================================
                             محدوده قیمت
                        ================================================== -->

                        <details
                            class="products-filter-dropdown products-price-filter"
                            open
                        >

                            <summary>

                                <span>
                                    محدوده قیمت
                                </span>

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>

                            </summary>


                            <div class="products-filter-dropdown-content">

                                <div class="products-price-fields">


                                    <!-- از -->

                                    <div class="products-price-field">

                                        <label for="minPrice">
                                            از
                                        </label>

                                        <div class="products-price-input-wrapper">

                                            <input
                                                type="number"
                                                id="minPrice"
                                                name="min_price"
                                                min="0"
                                                max="10000000"
                                                step="10000"
                                                value="<?php
                                                echo $minPrice > 0
                                                    ? htmlspecialchars(
                                                        (string) $minPrice,
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    )
                                                    : "";
                                                ?>"
                                                placeholder="۰"
                                                inputmode="numeric"
                                            >

                                            <span>
                                                تومان
                                            </span>

                                        </div>

                                    </div>


                                    <!-- تا -->

                                    <div class="products-price-field">

                                        <label for="maxPrice">
                                            تا
                                        </label>

                                        <div class="products-price-input-wrapper">

                                            <input
                                                type="number"
                                                id="maxPrice"
                                                name="max_price"
                                                min="0"
                                                max="10000000"
                                                step="10000"
                                                value="<?php
                                                echo $maxPrice > 0
                                                    ? htmlspecialchars(
                                                        (string) $maxPrice,
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    )
                                                    : "";
                                                ?>"
                                                placeholder="۱۰,۰۰۰,۰۰۰"
                                                inputmode="numeric"
                                            >

                                            <span>
                                                تومان
                                            </span>

                                        </div>

                                    </div>

                                </div>


                                <div class="products-price-limit">

                                    حداکثر قیمت قابل انتخاب:

                                    <strong>
                                        <?php echo persianNumber(10000000); ?>
                                    </strong>

                                    تومان

                                </div>

                            </div>

                        </details>


                        <!-- =================================================
                             مرتب‌سازی
                        ================================================== -->

                        <details
                            class="products-filter-dropdown products-sort-filter"
                            open
                        >

                            <summary>

                                <span>
                                    مرتب‌سازی
                                </span>

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>

                            </summary>


                            <div class="products-filter-dropdown-content">

                                <div class="products-sort-wrapper">

                                    <select
                                        name="sort"
                                        id="productsSort"
                                    >

                                        <option
                                            value="newest"
                                            <?php
                                            echo $selectedSort === "newest"
                                                ? "selected"
                                                : "";
                                            ?>
                                        >
                                            جدیدترین
                                        </option>

                                        <option
                                            value="oldest"
                                            <?php
                                            echo $selectedSort === "oldest"
                                                ? "selected"
                                                : "";
                                            ?>
                                        >
                                            قدیمی‌ترین
                                        </option>

                                        <option
                                            value="price_low"
                                            <?php
                                            echo $selectedSort === "price_low"
                                                ? "selected"
                                                : "";
                                            ?>
                                        >
                                            ارزان‌ترین
                                        </option>

                                        <option
                                            value="price_high"
                                            <?php
                                            echo $selectedSort === "price_high"
                                                ? "selected"
                                                : "";
                                            ?>
                                        >
                                            گران‌ترین
                                        </option>

                                        <option
                                            value="popular"
                                            <?php
                                            echo $selectedSort === "popular"
                                                ? "selected"
                                                : "";
                                            ?>
                                        >
                                            پرفروش‌ترین
                                        </option>

                                    </select>

                                </div>

                            </div>

                        </details>


                        <!-- =================================================
                             اعمال
                        ================================================== -->

                        <button
                            type="submit"
                            class="products-filter-submit"
                        >

                            <span>
                                اعمال فیلتر
                            </span>

                        </button>


                        <!-- =================================================
                             حذف فیلتر
                        ================================================== -->

                        <?php if (
                            $selectedCategory !== "" ||
                            $minPrice > 0 ||
                            $maxPrice > 0 ||
                            $selectedSort !== "newest"
                        ): ?>

                            <a
                                href="products.php"
                                class="products-filter-reset"
                            >
                                حذف فیلترها
                            </a>

                        <?php endif; ?>

                    </form>

                </aside>


                <!-- =====================================================
                     محصولات
                ====================================================== -->

                <div class="products-area">

                    <?php if (count($products) > 0): ?>


                        <?php foreach ($products as $product): ?>

                            <?php

                            $isSpecialOffer = (
                                (int) $product["is_discounted"] === 1 &&
                                (int) $product["discount"] > 0 &&
                                !empty($product["discount_end_at"])
                            );

                            $isUpcoming = (int) $product["is_upcoming"] === 1;

                            $productUrl =
                                "product.php?slug=" .
                                urlencode($product["slug"]);

                            ?>

                            <a
                                href="<?php echo htmlspecialchars(
                                    $productUrl,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                class="products-card-link"
                                data-product-card
                                data-product-id="<?php echo (int) $product["id"]; ?>"
                                data-product-title="<?php echo htmlspecialchars($product["title"], ENT_QUOTES, "UTF-8"); ?>"
                                data-product-description="<?php echo htmlspecialchars($product["short_description"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                                data-product-category="<?php echo htmlspecialchars($product["category_name"], ENT_QUOTES, "UTF-8"); ?>"
                                data-product-price="<?php echo (int) $product["price"]; ?>"
                                data-product-old-price="<?php echo (int) $product["old_price"]; ?>"
                                data-product-discount="<?php echo (int) $product["discount"]; ?>"
                                data-product-free="<?php echo (int) $product["is_free"]; ?>"
                                data-product-upcoming="<?php echo $isUpcoming ? 1 : 0; ?>"
                                data-product-special="<?php echo $isSpecialOffer ? 1 : 0; ?>"
                                data-product-image="<?php echo htmlspecialchars($product["image"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                                data-product-url="<?php echo htmlspecialchars($productUrl, ENT_QUOTES, "UTF-8"); ?>"
                                data-product-end="<?php echo htmlspecialchars($product["discount_end_at"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                            >

                                <article
                                    class="offer-product-card<?php echo $isSpecialOffer ? " is-special-offer" : ""; ?><?php echo $isUpcoming ? " is-upcoming" : ""; ?>"
                                >

                                    <div class="offer-card-inner">

                                        <?php if ($isSpecialOffer): ?>

                                            <div class="offer-card-top">
                                                <span>پیشنهاد شگفت‌انگیز</span>
                                            </div>

                                        <?php elseif ($isUpcoming): ?>

                                            <div class="offer-card-top offer-card-top-upcoming">
                                                <span>به‌زودی</span>
                                            </div>

                                        <?php endif; ?>


                                        <div class="offer-card-image">

                                            <?php if (!empty($product["image"])): ?>

                                                <img
                                                    src="<?php echo htmlspecialchars(
                                                        $product["image"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ); ?>"
                                                    alt="<?php echo htmlspecialchars(
                                                        $product["title"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ); ?>"
                                                    loading="lazy"
                                                >

                                            <?php else: ?>

                                                <div class="offer-card-image-placeholder">
                                                    تصویر محصول
                                                </div>

                                            <?php endif; ?>

                                        </div>


                                        <div class="offer-card-content">

                                            <div class="offer-card-meta">

                                                <span class="products-card-category">
                                                    <?php echo htmlspecialchars(
                                                        $product["category_name"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ); ?>
                                                </span>

                                            </div>


                                            <h2>
                                                <?php echo htmlspecialchars(
                                                    $product["title"],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                ); ?>
                                            </h2>


                                            <?php if (!empty($product["short_description"])): ?>

                                                <p class="offer-card-description">
                                                    <?php echo htmlspecialchars(
                                                        $product["short_description"],
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    ); ?>
                                                </p>

                                            <?php endif; ?>


                                            <div class="offer-card-price">

                                                <span class="offer-current-price">

                                                    <?php if ($product["is_free"]): ?>

                                                        رایگان

                                                    <?php else: ?>

                                                        <?php echo persianNumber($product["price"]); ?>

                                                        <small>تومان</small>

                                                    <?php endif; ?>

                                                </span>

                                                <?php if ($product["discount"] > 0): ?>

                                                    <span class="offer-percent">
                                                        <?php echo persianNumber($product["discount"]); ?>٪
                                                    </span>

                                                <?php endif; ?>

                                            </div>


                                            <?php if (
                                                $product["discount"] > 0 &&
                                                $product["old_price"] > $product["price"]
                                            ): ?>

                                                <div class="offer-card-old-price">
                                                    <?php echo persianNumber($product["old_price"]); ?> تومان
                                                </div>

                                            <?php endif; ?>


                                            <div class="offer-card-action">
                                                <span>مشاهده جزئیات</span>

                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="1.8"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    aria-hidden="true"
                                                >
                                                    <path d="m9 18 6-6-6-6"/>
                                                </svg>
                                            </div>


                                            <?php if ($isSpecialOffer): ?>

                                                <div class="offer-card-countdown-wrap">

                                                    <span class="offer-countdown-label">
                                                        زمان باقی‌مانده تخفیف
                                                    </span>

                                                    <span
                                                        class="offer-card-countdown"
                                                        data-end="<?php echo htmlspecialchars(
                                                            $product["discount_end_at"],
                                                            ENT_QUOTES,
                                                            "UTF-8"
                                                        ); ?>"
                                                    >
                                                        <strong>۰۰</strong>
                                                        <b>:</b>
                                                        <strong>۰۰</strong>
                                                        <b>:</b>
                                                        <strong>۰۰</strong>
                                                    </span>

                                                </div>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </article>

                            </a>

                        <?php endforeach; ?>


                    <?php else: ?>


                        <!-- =================================================
                             نتیجه خالی
                        ================================================== -->

                        <div class="products-empty-state">

                            <div class="products-empty-icon">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <circle
                                        cx="11"
                                        cy="11"
                                        r="7"
                                    ></circle>

                                    <path d="m20 20-4-4"></path>

                                </svg>

                            </div>


                            <h2>
                                محصولی پیدا نشد
                            </h2>


                            <p>
                                با فیلترهای انتخاب‌شده محصولی در این بخش وجود ندارد.
                            </p>


                            <a href="products.php">
                                نمایش همه محصولات
                            </a>

                        </div>

                    <?php endif; ?>

                </div>

            </div>


            <!-- =========================================================
                 PRODUCT MODAL
            ========================================================== -->

            <div
                class="product-modal"
                id="productModal"
                aria-hidden="true"
            >

                <div class="product-modal-backdrop" data-modal-close></div>

                <div
                    class="product-modal-dialog"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="productModalTitle"
                >

                    <button
                        type="button"
                        class="product-modal-close"
                        id="productModalClose"
                        aria-label="بستن"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M6 6l12 12"/>
                            <path d="M18 6 6 18"/>
                        </svg>
                    </button>

                    <div class="product-modal-grid">

                        <div class="product-modal-image-wrap">
                            <img
                                id="productModalImage"
                                src=""
                                alt=""
                            >
                        </div>

                        <div class="product-modal-content">

                            <span
                                class="product-modal-category"
                                id="productModalCategory"
                            ></span>

                            <h2 id="productModalTitle"></h2>

                            <p
                                class="product-modal-description"
                                id="productModalDescription"
                            ></p>

                            <div class="product-modal-price-row">

                                <div>
                                    <strong id="productModalPrice"></strong>
                                    <small id="productModalPriceUnit">تومان</small>
                                </div>

                                <span
                                    class="product-modal-discount"
                                    id="productModalDiscount"
                                ></span>

                            </div>

                            <div
                                class="product-modal-old-price"
                                id="productModalOldPrice"
                            ></div>

                            <div
                                class="product-modal-upcoming"
                                id="productModalUpcoming"
                            >
                                این محصول به‌زودی منتشر می‌شود.
                            </div>

                            <div class="product-modal-actions">

                                <button
                                    type="button"
                                    class="product-modal-cart"
                                    id="productModalCart"
                                >
                                    افزودن به سبد خرید
                                </button>

                                <button
                                    type="button"
                                    class="product-modal-favorite"
                                    id="productModalFavorite"
                                >
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        aria-hidden="true"
                                    >
                                        <path d="M20.8 8.7c0 5.2-8.8 10.2-8.8 10.2S3.2 13.9 3.2 8.7A4.7 4.7 0 0 1 12 6.2a4.7 4.7 0 0 1 8.8 2.5Z"/>
                                    </svg>
                                    <span>افزودن به مورد علاقه‌ها</span>
                                </button>

                            </div>

                            <a
                                href="#"
                                class="product-modal-details-link"
                                id="productModalDetailsLink"
                            >
                                ورود به صفحه محصول
                            </a>

                        </div>

                    </div>

                </div>

            </div>


            <!-- =========================================================
                 PAGINATION
            ========================================================== -->

            <?php if ($totalPages > 1): ?>

                <nav
                    class="products-pagination"
                    aria-label="صفحه‌بندی محصولات"
                >

                    <?php if ($currentPage > 1): ?>

                        <a
                            href="<?php echo htmlspecialchars(
                                productsPageUrl($currentPage - 1),
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>"
                            class="products-pagination-arrow"
                        >
                            قبلی
                        </a>

                    <?php endif; ?>


                    <?php for (
                        $page = 1;
                        $page <= $totalPages;
                        $page++
                    ): ?>

                        <?php

                        /*
                        |--------------------------------------------------------------------------
                        | اگر صفحات خیلی زیاد باشند،
                        | فقط محدوده مناسب را نمایش بده.
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $page !== 1 &&
                            $page !== $totalPages &&
                            abs($page - $currentPage) > 2
                        ) {
                            continue;
                        }

                        ?>

                        <a
                            href="<?php echo htmlspecialchars(
                                productsPageUrl($page),
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>"
                            class="
                                products-pagination-number
                                <?php
                                echo $page === $currentPage
                                    ? "active"
                                    : "";
                                ?>
                            "
                        >
                            <?php echo persianNumber($page); ?>
                        </a>

                    <?php endfor; ?>


                    <?php if ($currentPage < $totalPages): ?>

                        <a
                            href="<?php echo htmlspecialchars(
                                productsPageUrl($currentPage + 1),
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>"
                            class="products-pagination-arrow"
                        >
                            بعدی
                        </a>

                    <?php endif; ?>

                </nav>

            <?php endif; ?>

        </div>

    </section>

</main>


<?php require_once "./include/footer.php"; ?>

<script src="./script.js"></script>
<script src="./assets/js/products.js"></script>

</body>
</html>