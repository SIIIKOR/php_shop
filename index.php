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

$post_handler = new Post_Data_Handler($_POST);

$loader = new Db_Loader();
$preparer = new Data_Preparer();
// object used to run queries
$runner = new Query_Runner($loader, $preparer);
// object used to procces login data
$logger = new Login_handler($runner);

// if cookie is set, it isn't user initial login, thus log user with cookie
if (isset($_COOKIE["cookie_token"])) {
    $logger->set_login($_COOKIE, TRUE);
    $_SESSION = $_COOKIE;

    // this would be strange.
    if (!$logger->is_logged()) {
        $login_mess = new Text_Field("ERROR.", "login_mess");
        $login_mess->create();
    }
}
// if user pressed logout btn, delete cookie
if ($post_handler->get_post_arg("mode") == "logout") {
    // so that user will have to login again.
    if ($logger->is_logged()) {
        $logger->delete_cookie();
    }
}
// if user just tried to login.
if ($post_handler->get_post_arg("mode") == "login_attempt") {
    // data from text form on login page is colective
    $login_data = $post_handler->get_colective_data();
    $logger->set_login($login_data);
    if ($logger->is_logged()) {
        $logger->create_cookie($login_data["mail"]);

        $login_mess = new Text_Field("Login successful.", "login_mess");
        $login_mess->create();
    }
}
// display btns specific for login status.
if ($logger->is_logged()) {
    $login_mess = new Text_Field("Logged in.", "login_mess");
    $login_mess->create();

    $logout_btn = new Btn_Form("r_btn");
    $logout_btn->set_text("logut");
    $logout_btn->set_name("f_btn_submit");
    $logout_btn->set_hidden_data(["mode"=>"logout"]);
    $logout_btn->set_link("index.php");

    if ($logger->is_admin()) { // admin mode
        $admin_btn = new Btn_Form("admin_btn");
        $admin_btn->set_text("Go CRUD MODE");
        $admin_btn->set_name("f_btn_submit");
        $admin_btn->set_link("crud_main_page.php");
        $ul_content = "<ul>
                        <li>{$admin_btn->get_html()}</li>
                        <li>{$log_out_btn->get_html()}</li>
                        </ul>";
    } else { // customer mode
        $cart_btn = new Btn_Form("r_btn");
        $cart_btn->set_text("cart");
        $cart_btn->set_name("f_btn_submit");
        $cart_btn->set_link("cart.php");
        
        $orders_btn = new Btn_Form("r_btn");
        $orders_btn->set_text("orders");
        $orders_btn->set_name("f_btn_submit");
        $orders_btn->set_link("orders.php");

        $ul_content = "<ul>
                        <li>{$cart_btn->get_html()}</li>
                        <li>{$orders_btn->get_html()}</li>
                        <li>{$log_out_btn->get_html()}</li>
                        </ul>";
    }
} else {
    // if user isn't logged in then he is in guest mode.
    $login_mess = new Text_Field("Guest mode.", "login_mess");
    $login_mess->create();

    $login_btn = new Btn_Form("r_btn");
    $login_btn->set_text("login");
    $login_btn->set_name("f_btn_submit");
    $login_btn->set_hidden_data(["mode"=>"login"]);
    $login_btn->set_link("login_page.php");

    $register_btn = new Btn_Form("r_btn");
    $register_btn->set_text("register");
    $register_btn->set_name("f_btn_submit");
    $register_btn->set_hidden_data(["mode"=>"register"]);
    $register_btn->set_link("login_page.php");

    $ul_content = "<ul>
                   <li>{$login_btn->get_html()}</li>
                   <li>{$register_btn->get_html()}</li>
                   </ul>";
}

// display user panel.
print($ul_content);



// get names of categories and create buttons to choose them.
$category_names_query_output = $runner->get_table_contents(["category_name"], ["product_groups"], NULL, TRUE);
$category_names = $preparer->get_query_output_col_to_list($category_names_query_output);
array_push($category_names, "reset"); // adds additional reset btn
// creates btns used to select desired category name
$category_btn = new Multichoice_Btn_Form($category_names, "category_name", "index.php", "cat_multi_btns");
$category_btn->create();

// after using category_btn
$category_name = $post_handler->get_post_arg("category_name");

$condition_arr = NULL;
if ($category_name) {  // if cat_multi_btns was pressed
    if ($category_name == "reset") {  // if reset, reset condition_arr
        $condition_arr = NULL;
    } else {  // create condition_arr with chosen category name
        $condition_arr = ["category_name"=>$category_name];
    }
}

// how many records will be displayed per page
$records_per_page = 5;
$page_num = $post_handler->get_post_arg("page_num");
if (!$page_num) {
    // if pagination wasn't used yet.
    $page_num = 0;
}

// desired column names to display
$col_names = ["id", "product_name", "category_name", "price"];
// fetched contents.
$shop_contents = $runner->get_table_contents(
    $col_names, ["product_groups"], $condition_arr, 
    FALSE, $page_num, $records_per_page);

print_r($shop_contents);

// $data = $loader->get_table_contents($table_name, $condition_arr, $col_names, FALSE, $page_num, $records_per_page);
// // create table
// $primary_keys = $loader->get_primary_key_names();
// $table = new Table($data, array_slice($col_names, 1), $primary_keys, $table_name, $page_num, "product_page.php");
// $table->create();
// // create pagination
// $total_row_count = $loader->get_table_row_amount($table_name);
// $pagination = new Pagination($table_name, $page_num, $records_per_page, $total_row_count, "index.php");
// $pagination->create();

?>

</body>
</html>