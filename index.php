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

if (isset($_COOKIE["mail"])) {
    $_SESSION = $_COOKIE;
}

$handler = new Data_Handler($_POST, 1);
$loader = new Db_Loader();
$preparer = new Data_Preparer();

$records_per_page = 5;
$page_num = $handler->get_page_num();
if (!$page_num) {
    $page_num = 0;
}

if ($handler->get_mode() == "login_attempt") {
    $login_data = $handler->get_colective_data();
    $is_successful_login = $loader->check_login_attempt($login_data, $preparer);
    if ($is_successful_login) {
        $_SESSION = $login_data;
        setcookie("mail", $login_data["mail"], time() + 60);
        setcookie("password", $login_data["password"], time() + 60);
        $login_mess = new Text_Field("Login successful.", "login_mess");
        $login_mess->create();
    }
}

if (isset($_SESSION["mail"])) {
    $is_successful_login = $loader->check_login_attempt($_SESSION, $preparer);
    if ($is_successful_login) {
        $login_mess = new Text_Field("Logged in.", "login_mess");
        $login_mess->create();

        $cart_btn = new Btn_Form("cart", "f_btn_submit", NULL, "cart.php", "r_btn");
        $orders_btn = new Btn_Form("orders", "f_btn_submit", NULL, "orders.php", "r_btn");
        $log_out_btn = new Btn_Form("logout", "f_btn_submit", NULL, "index.php", "r_btn");

        $ul_content = "<ul>
                       <li>{$cart_btn->get_html()}</li>
                       <li>{$orders_btn->get_html()}</li>
                       <li>{$log_out_btn->get_html()}</li>
                       </ul>";
    }
} else {
    $login_mess = new Text_Field("Guest mode.", "login_mess");
    $login_mess->create();

    $login_btn = new Btn_Form("login", "f_btn_submit", ["mode"=>"login"], "login_page.php", "r_btn");
    $register_btn = new Btn_Form("register", "f_btn_submit", ["mode"=>"register"], "login_page.php", "r_btn");
    
    $ul_content = "<ul>
                   <li>{$login_btn->get_html()}</li>
                   <li>{$register_btn->get_html()}</li>
                   </ul>";
}

print($ul_content);

$query_out_category_names = $loader->get_table_col("product_groups", NULL, "category_name", TRUE);
$category_names = $preparer->get_query_output_row_to_list($query_out_category_names, "category_name");
$category_names = $preparer->tag_data("category_name", $category_names);

$choose_category = new Multichoice_Btn_Form($category_names, "index.php", "category_names");
$choose_category->create();

$table_name = "product_groups";
$data = $loader->get_db_contents_curr_page($table_name, $page_num, $records_per_page);
print_r($data);
print("<br>");
// $table = new Table($data, $col_names, $primary_keys, $table_name, $page_num);
// $table->create();

$total_row_count = $loader->get_table_row_amount($table_name);
print_r($total_row_count)
// $pagination = new Pagination($table_name, $page_num, $records_per_page, $total_row_count, "index.php");
// $pagination->create();

?>

</body>
</html>