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

$handler = new Data_Handler($_POST, 1);
$loader = new Db_Loader();
$preparer = new Data_Preparer();

if ($handler->get_post_arg("mode") == "logout") {
    // if user pressed logout btn delete cookie
    // so that user will have to login again.
    unset($_COOKIE['mail']);
    unset($_COOKIE['password']);
    setcookie('mail', null, -1, '/'); 
    setcookie('password', null, -1, '/'); 
}

if (isset($_COOKIE["mail"])) {
    // if cookie is set, assign it to seassion variable.
    $_SESSION = $_COOKIE;
}

$records_per_page = 5;  // how many records will be displayed per page
$page_num = $handler->get_post_arg("page_num");
if (!$page_num) {
    // if pagination wasn't used yet.
    $page_num = 0;
}
// if user chose category, this category is assigned here, else it's null.
$category_name = $handler->get_post_arg("category_name");

if ($handler->get_post_arg("mode") == "login_attempt") {
    // after using login btn, get inserted data.
    $login_data = $handler->get_colective_data();
    $login_data_out = $loader->check_login_attempt($login_data, $preparer);
    // check if it's correct
    $is_successful_login = $login_data_out[0];
    if ($is_successful_login) {
        // if it's correct create cookie and session var with this data
        $_SESSION = $login_data;
        setcookie("mail", $login_data["mail"], time() + 60*60); // 1h
        setcookie("password", $login_data["password"], time() + 60*60);
        $login_mess = new Text_Field("Login successful.", "login_mess");
        $login_mess->create();
    }
}

// display btns specific for login status.
if (isset($_SESSION["mail"])) {
    // if cookie is set and thus seasion
    $login_data_out = $loader->check_login_attempt($_SESSION, $preparer);
    $is_successful_login = $login_data_out[0];
    if ($is_successful_login) {
        $user_id = $login_data_out[1];
        $is_admin = $loader->check_admin_status(["user_id" => $user_id], $preparer);

        $login_mess = new Text_Field("Logged in.", "login_mess");
        $login_mess->create();

        $log_out_btn = new Btn_Form("logout", "f_btn_submit", ["mode"=>"logout"], "index.php", "r_btn");
        
        // admin mode
        if ($is_admin) {
            $admin_btn = $cart_btn = new Btn_Form("Go CRUD MODE", "f_btn_submit", NULL, "crud_main_page.php", "admin_btn");
            $ul_content = "<ul>
                           <li>{$admin_btn->get_html()}</li>
                           <li>{$log_out_btn->get_html()}</li>
                           </ul>";
        } else { // customer mode
            $cart_btn = new Btn_Form("cart", "f_btn_submit", NULL, "cart.php", "r_btn");
            $orders_btn = new Btn_Form("orders", "f_btn_submit", NULL, "orders.php", "r_btn");
            $ul_content = "<ul>
                           <li>{$cart_btn->get_html()}</li>
                           <li>{$orders_btn->get_html()}</li>
                           <li>{$log_out_btn->get_html()}</li>
                           </ul>";
        }
        
    }
} else {
    // if user isn't logged in then he is in guest mode.
    $login_mess = new Text_Field("Guest mode.", "login_mess");
    $login_mess->create();

    $login_btn = new Btn_Form("login", "f_btn_submit", ["mode"=>"login"], "login_page.php", "r_btn");
    $register_btn = new Btn_Form("register", "f_btn_submit", ["mode"=>"register"], "login_page.php", "r_btn");
    
    $ul_content = "<ul>
                   <li>{$login_btn->get_html()}</li>
                   <li>{$register_btn->get_html()}</li>
                   </ul>";
}

// display user panel.
print($ul_content);

// get names of categories and create buttons to choose them.
$query_out_category_names = $loader->get_table_contents("product_groups", NULL, "category_name", TRUE);
$category_names = $preparer->get_query_output_row_to_list($query_out_category_names, "category_name");
array_push($category_names, "reset");
$category_names = $preparer->tag_data("category_name", $category_names);

$choose_category = new Multichoice_Btn_Form($category_names, "index.php", "category_names");
$choose_category->create();

$table_name = "product_groups";
$col_names = ["group_id", "product_name", "category_name", "price"];
// based on pressed category button, perform action to create condition for query.
// if user pressed reset, condition changed to null.
$condition = NULL;
if ($category_name) {
    if ($category_name == "reset") {
        $condition = NULL;
    } else {
        $condition = "category_name = '$category_name'";
    }
}

$data = $loader->get_table_contents($table_name, $condition, $col_names, FALSE, $page_num, $records_per_page);
// create table
$primary_keys = $loader->get_primary_key_names();
$table = new Table($data, array_slice($col_names, 1), $primary_keys, $table_name, $page_num, "product_page.php");
$table->create();
// create pagination
$total_row_count = $loader->get_table_row_amount($table_name);
$pagination = new Pagination($table_name, $page_num, $records_per_page, $total_row_count, "index.php");
$pagination->create();

?>

</body>
</html>