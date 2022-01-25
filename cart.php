<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>Cart page</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>

<h1>Your cart</h1>

<?php
require_once("code_base.php");

$loader = new Db_Loader();
$preparer = new Data_Preparer();

// check login
$login_data = $loader->check_login_status($_COOKIE, $preparer);
$is_logged = $login_data[0];

if ($is_logged) {
    $handler = new Data_Handler($_POST);
    // if user just added product to cart.
    if ($handler->get_post_arg("mode") == "add_to_cart") {
        // get product id.
        $product_id = $handler->get_post_arg("product_id");
        $user_id = $loader->check_login_attempt($_COOKIE, $preparer)[1];
        // insert data into database.
        $in_vals = $preparer->get_query_params([$user_id, $product_id], "in");
        $loader->insert_table_row("cart", $in_vals);
        // change availability status
        $loader->update_table_row("products", "product_id = {$product_id}", "is_available = false");
    }
} else {
    $login_mess = new Text_Field("You should be logged in to display your cart.", "login_mess");
    $login_mess->create();
}

$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "index.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>