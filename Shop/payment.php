<?php

session_start();

include("./include/header.php");


/*
|--------------------------------------------------------------------------
| محصولات نمونه
|--------------------------------------------------------------------------
| فعلاً محصولات از دیتابیس دریافت نمی‌شوند.
| بعداً این بخش را به MySQL متصل می‌کنیم.
*/

$products = [

    1 => [
        "name" => "قالب فروشگاهی مدرن",
        "price" => 9.99,
        "image" => "./assets/image/product1.jpg"
    ],

    2 => [
        "name" => "UI Kit حرفه‌ای",
        "price" => 4.99,
        "image" => "./assets/image/product2.jpg"
    ],

    3 => [
        "name" => "پروژه PHP فروشگاهی",
        "price" => 14.99,
        "image" => "./assets/image/product3.jpg"
    ]

];


/*
|--------------------------------------------------------------------------
| سبد خرید آزمایشی
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["cart"])) {

    $_SESSION["cart"] = [];

}


/*
|--------------------------------------------------------------------------
| افزودن محصول به سبد
|--------------------------------------------------------------------------
*/

if (isset($_GET["add"])) {

    $productId = (int) $_GET["add"];

    if (isset($products[$productId])) {

        if (isset($_SESSION["cart"][$productId])) {

            $_SESSION["cart"][$productId]++;

        } else {

            $_SESSION["cart"][$productId] = 1;

        }

    }

    header("Location: payment.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| افزایش تعداد
|--------------------------------------------------------------------------
*/

if (isset($_GET["increase"])) {

    $productId = (int) $_GET["increase"];

    if (isset($_SESSION["cart"][$productId])) {

        $_SESSION["cart"][$productId]++;

    }

    header("Location: payment.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| کاهش تعداد
|--------------------------------------------------------------------------
*/

if (isset($_GET["decrease"])) {

    $productId = (int) $_GET["decrease"];

    if (isset($_SESSION["cart"][$productId])) {

        $_SESSION["cart"][$productId]--;

        if ($_SESSION["cart"][$productId] <= 0) {

            unset($_SESSION["cart"][$productId]);

        }

    }

    header("Location: payment.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| حذف محصول
|--------------------------------------------------------------------------
*/

if (isset($_GET["remove"])) {

    $productId = (int) $_GET["remove"];

    if (isset($_SESSION["cart"][$productId])) {

        unset($_SESSION["cart"][$productId]);

    }

    header("Location: payment.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| محاسبه جمع کل
|--------------------------------------------------------------------------
*/

$total = 0;

$totalItems = 0;


foreach ($_SESSION["cart"] as $productId => $quantity) {

    if (isset($products[$productId])) {

        $total += $products[$productId]["price"] * $quantity;

        $totalItems += $quantity;

    }

}

?>

<main class="payment-page">

    <div class="container">

        <div class="payment-header">

            <h1>
                سبد خرید
            </h1>

        </div>


        <?php if (empty($_SESSION["cart"])): ?>

            <!-- سبد خالی -->

            <section class="empty-cart">

                <div class="empty-cart-icon">

                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.7">

                        <path
                            d="M3 3h2l2.4 11.2
                            a2 2 0 0 0 2 1.6h7.8
                            a2 2 0 0 0 2-1.6L21 7H6">
                        </path>

                        <circle
                            cx="10"
                            cy="20"
                            r="1">
                        </circle>

                        <circle
                            cx="18"
                            cy="20"
                            r="1">
                        </circle>

                    </svg>

                </div>


                <h2>
                    سبد خرید شما خالی است
                </h2>


                <p>
                    هنوز محصولی به سبد خرید خود اضافه نکرده‌اید.
                </p>


                <a
                    href="index.php"
                    class="continue-shopping">

                    مشاهده محصولات

                </a>

            </section>


        <?php else: ?>


            <section class="cart-layout">


                <!-- محصولات -->

                <div class="cart-products">

                    <div class="cart-products-header">

                        <h2>
                            محصولات سبد خرید
                        </h2>

                        <span>
                            <?= $totalItems ?> محصول
                        </span>

                    </div>


                    <?php foreach ($_SESSION["cart"] as $productId => $quantity): ?>

                        <?php if (!isset($products[$productId])) continue; ?>

                        <?php

                        $product = $products[$productId];

                        $subtotal = $product["price"] * $quantity;

                        ?>


                        <div class="cart-item">


                            <!-- تصویر -->

                            <div class="cart-item-image">

                                <img
                                    src="<?= htmlspecialchars($product["image"]) ?>"
                                    alt="<?= htmlspecialchars($product["name"]) ?>">

                            </div>


                            <!-- اطلاعات -->

                            <div class="cart-item-info">

                                <h3>

                                    <?= htmlspecialchars($product["name"]) ?>

                                </h3>


                                <span class="cart-item-price">

                                    $<?= number_format($product["price"], 2) ?>

                                </span>

                            </div>


                            <!-- تعداد -->

                            <div class="quantity-control">

                                <a
                                    href="payment.php?decrease=<?= $productId ?>"
                                    class="quantity-btn">

                                    −

                                </a>


                                <span class="quantity">

                                    <?= $quantity ?>

                                </span>


                                <a
                                    href="payment.php?increase=<?= $productId ?>"
                                    class="quantity-btn">

                                    +

                                </a>

                            </div>


                            <!-- قیمت -->

                            <div class="cart-item-total">

                                $<?= number_format($subtotal, 2) ?>

                            </div>


                            <!-- حذف -->

                            <a
                                href="payment.php?remove=<?= $productId ?>"
                                class="remove-item"
                                aria-label="حذف محصول">

                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2">

                                    <path d="M3 6h18"></path>

                                    <path d="M8 6V4h8v2"></path>

                                    <path d="M19 6l-1 14H6L5 6"></path>

                                    <path d="M10 11v5"></path>

                                    <path d="M14 11v5"></path>

                                </svg>

                            </a>


                        </div>


                    <?php endforeach; ?>


                </div>


                <!-- خلاصه سفارش -->

                <aside class="cart-summary">

                    <h2>
                        خلاصه سفارش
                    </h2>


                    <div class="summary-row">

                        <span>
                            تعداد محصولات
                        </span>

                        <span>
                            <?= $totalItems ?>
                        </span>

                    </div>


                    <div class="summary-row">

                        <span>
                            مبلغ محصولات
                        </span>

                        <span>
                            $<?= number_format($total, 2) ?>
                        </span>

                    </div>


                    <div class="summary-divider"></div>


                    <div class="summary-total">

                        <span>
                            مبلغ قابل پرداخت
                        </span>

                        <strong>
                            $<?= number_format($total, 2) ?>
                        </strong>

                    </div>


                    <a
                        href="#"
                        class="payment-btn">

                        ادامه به پرداخت

                    </a>


                    <a
                        href="index.php"
                        class="back-shopping">

                        ادامه خرید

                    </a>


                </aside>


            </section>


        <?php endif; ?>


    </div>

</main>


<?php include("./include/footer.php"); ?>