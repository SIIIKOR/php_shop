<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>Orders page</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>

<h1>Orders</h1>

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
    // get user id
    $handler = new Post_Data_Handler($_POST);
    $records_per_page = 5;  // how many records will be displayed per page
    $page_num = $handler->get_post_arg("page_num");
    if (!$page_num) {
        // if pagination wasn't used yet.
        $page_num = 0;
    }
    
    $shop = new Shop_Handler($runner);
    $shop->set_user_id($logger->get_user_id());
    if ($handler->get_post_arg("mode") == "place_order") {
        // create new order
        // assign products to this order
        $shop->create_new_order();

    // $orders_contents_data = $loader->get_cart_contents($user_id, "orders", FALSE, $page_num, $records_per_page);
    // $orders_contents_count = $loader->get_cart_contents($user_id, "orders", TRUE)[0]["count"];
    // if (is_array($orders_contents_data)) {
    //     $orders_contents = new Table($orders_contents_data);    
    //     $orders_contents->create();

    //     // create pagination so that users can display big amounts of data.
    //     $total_row_count = $orders_contents_count;
    //     $pagination = new Pagination(NULL, $page_num, $records_per_page, $total_row_count, "orders.php");
    //     $pagination->create();
    }
} else {
    $login_mess = new Text_Field("You should be logged in to display your cart.", "login_mess");
    $login_mess->create();
}

$back_btn = new Btn_Form();
$back_btn->set_text("Go to the main page");
$back_btn->set_name("f_btn_submit");
$back_btn->set_link("index.php");
$back_btn->set_class_name("r_btn");
$back_btn->create();
?>

</body>
</html>