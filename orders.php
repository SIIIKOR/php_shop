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
    }

    $orders_data = $runner->get_table_contents(
        ["id", "order_placed_date", "order_finished_date", "is_finished"],
        ["orders"], ["user_id"=>$logger->get_user_id()],
        $page_num, $records_per_page);
    if (is_array($orders_data)) {
        $orders_contents = new Table($orders_data);
        $orders_contents->set_primary_keys(["id"]);
        $orders_contents->set_btn_data(["page_num"=>$page_num]);
        $orders_contents->set_btn_link("order_display.php");
        $orders_contents->create();

        // create pagination so that users can display big amounts of data.
        $total_row_count = $runner->get_table_contents(
            ["count(*)"], ["orders"], ["user_id"=>$logger->get_user_id()])[0]["count"];
        $pagination = new Pagination($page_num, $records_per_page, $total_row_count, "orders.php");
        $pagination->create();
    }
} else {
    $login_mess = new Text_Field("You should be logged in to display your cart.", "login_mess");
    $login_mess->create();
}
$go_main_page_btn = new Btn_Form("Go to the main crud page", "crud_main_page.php", );
$go_main_page_btn->set_class_name("r_btn");
$go_main_page_btn->create();
?>

</body>
</html>