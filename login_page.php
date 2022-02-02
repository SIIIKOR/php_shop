<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>Login|Register page</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>

<?php
require_once("code_base.php");

$post_handler = new Post_Data_Handler($_POST);

$loader = new Db_Loader();
$preparer = new Data_Preparer();
// object used to run queries
$runner = new Query_Runner($loader, $preparer);

// used to register user.
if ($post_handler->get_post_arg("mode") == "login_after_register") {
    $register_data = $post_handler->get_colective_data();
    $shop = new Shop_Handler($runner);
    $is_successful_reg = $shop->register_user($register_data);
}

// used to login, also used to login right after register
if ($post_handler->get_post_arg("mode") == "login" or $is_successful_reg) {
    print("<h1>Login page</h1>");
    $col_names = ["mail", "password"];
    $preset = FALSE;
    if ($is_successful_reg) {
        $col_names = ["mail"=>$register_data["mail"], "password"=>$register_data["password"]];
        $preset = TRUE;
    }
    $text_form = new Text_Form($col_names, "index.php", $preset, "text_form");
    $text_form->set_hidden_data(["mode"=>"login_attempt"]);
    $text_form->create();
} elseif($post_handler->get_post_arg("mode") == "register" or !$is_successful_reg) {
    print("<h1>Register page</h1>");
    $col_names = ["name", "surname", "mail", "password"];
    $text_form = new Text_Form($col_names, "login_page.php", FALSE, "text_form");
    $text_form->set_hidden_data(["mode"=>"login_after_register"]);
    $text_form->create();
}

$diff_table_btn = new Btn_Form();
$diff_table_btn->set_text("Go to the main page");
$diff_table_btn->set_name("f_btn_submit");
$diff_table_btn->set_link("index.php");
$diff_table_btn->set_class_name("r_btn");
$diff_table_btn->create();
?>

</body>
</html>