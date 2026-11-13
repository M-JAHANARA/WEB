<?php

session_start();

/*
 * پاک کردن تمام اطلاعات Session
 */

$_SESSION = [];


/*
 * حذف Cookie مربوط به Session
 */

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}


/*
 * نابودی Session
 */

session_destroy();


/*
 * برگشت به صفحه اصلی
 */

header("Location: index.php");
exit;