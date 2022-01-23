<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>PHP shop main page</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>

<h1>PHP shop</h1>

<?php
require_once("code_base.php");

if (isset($_COOKIE["mail"])) {
    $_SESSION = $_COOKIE;
}

$handler = new Data_Handler($_POST, 1);
$loader = new Db_Loader();
$preparer = new Data_Preparer();

if ($handler->get_mode() == "login_attempt") {
    $login_data = $handler->get_colective_data();
    $is_successful_login = $loader->check_login_attempt($login_data, $preparer);
    if ($is_successful_login) {
        $_SESSION = $login_data;
        setcookie("mail", $login_data["mail"], time() + 60);
        setcookie("password", $login_data["password"], time() + 60);
        $err_mess = new Text_Field("Login successful.", "login_mess");
        $err_mess->create();
    }
}

if (isset($_SESSION["mail"])) {
    $is_successful_login = $loader->check_login_attempt($_SESSION, $preparer);
    if ($is_successful_login) {
        $err_mess = new Text_Field("Logged in.", "login_mess");
        $err_mess->create();
    }
} else {
    $err_mess = new Text_Field("Guest mode.", "login_mess");
    $err_mess->create();
}

$cart_btn = new Btn_Form("cart", "f_btn_submit", NULL, "cart.php", "r_btn");
$cart_btn->create();

$orders_btn = new Btn_Form("orders", "f_btn_submit", NULL, "orders.php", "r_btn");
$orders_btn->create();

$login_btn = new Btn_Form("login", "f_btn_submit", ["mode"=>"login"], "login_page.php", "r_btn");
$login_btn->create();

$register_btn = new Btn_Form("register", "f_btn_submit", ["mode"=>"register"], "login_page.php", "r_btn");
$register_btn->create()
?>

</body>
</html>