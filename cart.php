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
    $shop = new Shop_Handler($runner);
    $shop->set_user_id($logger->get_user_id());

    // delete product from cart
    if ($post_handler->get_post_arg("mode") == "out_cart_id") {
        $product_id = $post_handler->get_post_arg("id");
        $shop->delete_from_cart($product_id);
    }
    // if user just added product to cart.
    if ($post_handler->get_post_arg("mode") == "add_to_cart") {
        // get product id.
        $product_id = $post_handler->get_post_arg("id");
        $shop->add_to_cart($product_id);
    }
    // create table with user's car contents.
    // how many records will be displayed per page
    $records_per_page = 5;
    $page_num = $post_handler->get_post_arg("page_num");
    if (!$page_num) {
        // if pagination wasn't used yet.
        $page_num = 0;
    }
    $cart_contents_data = $shop->get_cart_contents(FALSE, $page_num, $records_per_page);
    $total_row_count = $shop->get_cart_contents(TRUE);
    if (is_array($cart_contents_data) and $total_row_count>0) {
        $cart_contents = new Table($cart_contents_data);
        $cart_contents->set_primary_keys(["id"]);
        $cart_contents->set_btn_data(["mode"=>"out_cart_id"]);
        $cart_contents->set_btn_link("cart.php");
        $cart_contents->create();
    }
    // create pagination so that users can display big amounts of data.
    $pagination = new Pagination($page_num, $records_per_page, $total_row_count, "cart.php");
    $pagination->create();

    $place_order = new Btn_Form(["mode"=>"place_order"], "orders.php", "r_btn");
    $place_order->set_text("Place your order");
    $place_order->set_name("f_btn_submit");
    $place_order->create();
} else {
    $login_mess = new Text_Field("You should be logged in to display your cart.", "login_mess");
    $login_mess->create();
}

$go_main_page_btn = new Btn_Form();
$go_main_page_btn->set_text("Go to the main page");
$go_main_page_btn->set_name("f_btn_submit");
$go_main_page_btn->set_link("index.php");
$go_main_page_btn->set_class_name("r_btn");
$go_main_page_btn->create();
?>

</body>
</html>