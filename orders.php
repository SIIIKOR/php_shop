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

// check login
$login_data = $loader->check_login_status($_COOKIE, $preparer);
$is_logged = $login_data[0];

if ($is_logged) {
    // get user id
    $handler = new Data_Handler($_POST);
    
    $records_per_page = 5;  // how many records will be displayed per page
    $page_num = $handler->get_post_arg("page_num");
    if (!$page_num) {
        // if pagination wasn't used yet.
        $page_num = 0;
    }
    
    $user_id = $loader->check_login_attempt($_COOKIE, $preparer)[1];
    if ($handler->get_post_arg("mode") == "place_order") {
        $cart_contents_data = $loader->get_cart_contents($user_id, "orders", 
         FALSE, $page_num, $records_per_page);
        $time = date("Y-m-d H:i:s");
        foreach ($cart_contents_data as $row) {
            $product_id = $row["product_id"];
            $in_arr = ["product_id"=>$product_id, 
             "user_id"=>$user_id, "order_placed_data"=>$time];
            $in_values = $preparer->get_query_params($in_arr, "in");
            $loader->insert_table_row("orders", $in_values);
            $loader->delete_table_row("cart", "product_id = $product_id");
        }
    }
    $orders_contents_data = $loader->get_cart_contents($user_id, "orders", FALSE, $page_num, $records_per_page);
    $orders_contents_count = $loader->get_cart_contents($user_id, "orders", TRUE)[0]["count"];
    if (is_array($orders_contents_data)) {
        $orders_contents = new Table($orders_contents_data);    
        $orders_contents->create();

        // create pagination so that users can display big amounts of data.
        $total_row_count = $orders_contents_count;
        $pagination = new Pagination(NULL, $page_num, $records_per_page, $total_row_count, "orders.php");
        $pagination->create();
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