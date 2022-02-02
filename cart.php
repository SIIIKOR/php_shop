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
// object used to run queries
$runner = new Query_Runner($loader, $preparer);
// object used to procces login data
$logger = new Login_handler($runner);

if (isset($_COOKIE["cookie_token"])) {
    $logger->set_login($_COOKIE, TRUE);
}

if ($logger->is_logged()) {
    $post_handler = new Post_Data_Handler($_POST);
    // how many records will be displayed per page
    $records_per_page = 5;
    $page_num = $post_handler->get_post_arg("page_num");
    if (!$page_num) {
        // if pagination wasn't used yet.
        $page_num = 0;
    }
    // delete product from cart
    if ($post_handler->get_post_arg("mode") == "out_cart_id") {
        $product_id = $post_handler->get_post_arg("product_id");
        $runner->delete_table_row(["cart"], ["id"=>$product_id]);
        $runner->update_table_row(["is_available"=>TRUE], ["products"], ["id"=>$product_id]);
    }
    // if user just added product to cart.
    if ($post_handler->get_post_arg("mode") == "add_to_cart") {
        // get product id.
        $product_id = $post_handler->get_post_arg("product_id");
        // insert data into database.
        $runner->insert_table_row(["cart"], [$product_id, $logger->get_user_id()]);
        // change availability status
        $runner->update_table_row(["is_available"=>FALSE], ["products"], ["id"=>$product_id]);
    }
    // create table with user's car contents.
    $cart_contents_data = $runner->get_table_contents_sq();
    $cart_contents_data = $loader->get_cart_contents($user_id, "cart", FALSE, $page_num, $records_per_page);
    if (is_array($cart_contents_data)) {
        $cart_contents = new Table($cart_contents_data, NULL, ["product_id"=>1], NULL, $page_num, "cart.php");    
        $cart_contents->set_btn_data(["mode"=>"out_cart_id"]);
        $cart_contents->create();
    }
    // create pagination so that users can display big amounts of data.
    $total_row_count = $loader->get_cart_contents($user_id, "cart", TRUE)[0]["count"];
    $pagination = new Pagination(NULL, $page_num, $records_per_page, $total_row_count, "cart.php");
    $pagination->create();

    $place_order = new Btn_Form("Place your order", "f_btn_submit", ["mode"=>"place_order"], "orders.php", "r_btn");
    $place_order->create();
} else {
    $login_mess = new Text_Field("You should be logged in to display your cart.", "login_mess");
    $login_mess->create();
}

$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "index.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>