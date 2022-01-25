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
        $cart_contents_data = $loader->get_cart_contents($user_id);
        $time = date("Y-m-d H:i:s");
        print_r($time);
        print("<br><br><br><br>");
        foreach ($cart_contents_data as $row) {
            print_r($row);
            print("<br><br>");
        }
    }

    // $cart_contents_data = $loader->get_cart_contents($user_id, FALSE, $page_num, $records_per_page);
    // if (is_array($cart_contents_data)) {
    //     $orders_contents = new Table($cart_contents_data, NULL, NULL, NULL, $page_num, "cart.php");    
    //     $orders_contents->create();
    // }

} else {
    $login_mess = new Text_Field("You should be logged in to display your cart.", "login_mess");
    $login_mess->create();
}

$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "index.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>