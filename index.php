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

$handler = new Post_Data_Handler($_POST);

$loader = new Db_Loader();
$preparer = new Data_Preparer();
// object used to run queries
$runner = new Query_Runner($loader, $prep);

// if cookie is set, it isn't user initial login, thus log user with cookie
if (isset($_COOKIE["cookie_token"])) {
    $logger = new Login_handler($runner, $_COOKIE, TRUE);
    $_SESSION = $_COOKIE;

    // this would be strange.
    if (!$logger->is_logged()) {
        $login_mess = new Text_Field("ERROR.", "login_mess");
        $login_mess->create();
    }
}
// if user pressed logout btn, delete cookie
if ($handler->get_post_arg("mode") == "logout") {
    // so that user will have to login again.
    if ($logger->is_logged()) {
        $logger->delete_cookie();
    }
}
// if user just tried to login.
if ($handler->get_post_arg("mode") == "login_attempt") {
    // data from text form on login page is colective
    $login_data = $handler->get_colective_data();
    $logger = new Login_handler($runner, $login_data);
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

// // how many records will be displayed per page
// $records_per_page = 5;
// $page_num = $handler->get_post_arg("page_num");
// if (!$page_num) {
//     // if pagination wasn't used yet.
//     $page_num = 0;
// }
// // if user chose category, this category is assigned here, else it's False
// $category_name = $handler->get_post_arg("category_name");

// get names of categories and create buttons to choose them.
$category_names = $runner->get_table_contents(["category_name"], ["product_groups"]);
print_r($category_names);
// $query_out_category_names = $loader->get_table_contents("product_groups", NULL, "category_name", TRUE);
// $category_names = $preparer->get_query_output_col_to_list($query_out_category_names, "category_name");
// array_push($category_names, "reset");
// $category_names = $preparer->tag_data("category_name", $category_names);

// $choose_category = new Multichoice_Btn_Form($category_names, "index.php", "category_names");
// $choose_category->create();

// $table_name = "product_groups";
// $col_names = ["group_id", "product_name", "category_name", "price"];
// // based on pressed category button, perform action to create condition for query.
// // if user pressed reset, condition changed to null.
// $condition = NULL;
// if ($category_name) {
//     if ($category_name == "reset") {
//         $condition = NULL;
//     } else {
//         $condition = "category_name = '$category_name'";
//     }
// }

// $data = $loader->get_table_contents($table_name, $condition, $col_names, FALSE, $page_num, $records_per_page);
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