<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>Orders page</title>
    <link rel="stylesheet" href="styles.css">
</head>

<h1>Order</h1>

<div id="main">

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
    $handler = new Post_Data_Handler($_POST);
    $shop = new Shop_Handler($runner);

    $records_per_page = 20;  // how many records will be displayed per page
    $page_num = $handler->get_post_arg("page_num");
    if (!$page_num) {
        // if pagination wasn't used yet.
        $page_num = 0;
    }
    $order_id = intval($handler->get_post_arg("id"));

    $order_contents = $shop->get_order_product_info($order_id, FALSE, $page_num, $records_per_page);
    if (!empty($order_contents)) {
        $amount_due = new Text_Field("Amount due {$shop->get_amount_due($order_id)[0]["sum"]} zł", "amount_due");
        $amount_due->create();

        $table = new Table($order_contents);
        $table->set_class_name("table");
        $table->create();
    }

    $total_row_count = $shop->get_order_product_info($order_id, TRUE)[0]["count"];
    $pagination = new Pagination($page_num, $records_per_page, $total_row_count, "order_display.php");
    $pagination->set_btn_data(["id"=>$order_id]);
    $pagination->create();

    $back_btn = new Btn_Form("Go back", "orders.php", ["page_num"=>$page_num]);
    $back_btn->set_class_name("r_c_btn");
    $back_btn->create();
} else {
    $login_mess = new Text_Field("You should be logged in to display your cart.", "login_mess");
    $login_mess->create();
}

$go_main_page_btn = new Btn_Form("Go to the main page", "index.php");
$go_main_page_btn->set_class_name("r_c_btn");
$go_main_page_btn->create();
?>

</div>
</body>
</html>
