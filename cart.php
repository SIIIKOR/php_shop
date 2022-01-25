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
    
    $records_per_page = 5;  // how many records will be displayed per page
    $page_num = $handler->get_post_arg("page_num");
    if (!$page_num) {
        // if pagination wasn't used yet.
        $page_num = 0;
    }

    if ($handler->get_post_arg("mode") == "out_cart_id") { // delete product from cart
        $product_id = $handler->get_post_arg("product_id");
        $loader->delete_table_row("cart", "product_id = {$product_id}");
        $loader->update_table_row("products", "product_id = {$product_id}", "is_available = true");
    }

    // get user id
    $user_id = $loader->check_login_attempt($_COOKIE, $preparer)[1];
    // if user just added product to cart.
    if ($handler->get_post_arg("mode") == "add_to_cart") {
        // get product id.
        $product_id = $handler->get_post_arg("product_id");
        // insert data into database.
        $in_vals = $preparer->get_query_params([$product_id, $user_id], "in");
        $loader->insert_table_row("cart", $in_vals);
        // change availability status
        $loader->update_table_row("products", "product_id = {$product_id}", "is_available = false");
    }
    // create table with user's car contents.
    $cart_contents_data = $loader->get_cart_contents($user_id, FALSE, $page_num, $records_per_page);
    if (is_array($cart_contents_data)) {
        $cart_contents = new Table($cart_contents_data, NULL, ["product_id"=>1], NULL, $page_num, "cart.php");    
        $cart_contents->set_btn_data(["mode"=>"out_cart_id"]);
        $cart_contents->create();
    }
    
    // create pagination so that users can display big amounts of data.
    $total_row_count = $loader->get_cart_contents($user_id, TRUE)[0]["count"];
    $pagination = new Pagination(NULL, $page_num, $records_per_page, $total_row_count, "cart.php");
    $pagination->create();
} else {
    $login_mess = new Text_Field("You should be logged in to display your cart.", "login_mess");
    $login_mess->create();
}

$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "index.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>