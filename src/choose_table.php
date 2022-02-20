<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>Choose table</title>
    <link rel="stylesheet" href="styles.css">
</head>

<h1>Choose table</h1>

<div id="main">

<?php
require_once("code_base.php");

$loader = new Db_Loader();
$preparer = new Data_Preparer();
// object used to run queries
$runner = new Query_Runner($loader, $preparer);
// object used to procces login data
$logger = new Login_handler($runner);

// check login
if (isset($_COOKIE["cookie_token"])) {
    $logger->set_login($_COOKIE, TRUE);
}

if ($logger->is_admin()) {
    $post_handler = new Post_Data_Handler($_POST);

    if ($post_handler->get_post_arg("mode") == "vis") {
        $link = "display_table.php";
    } elseif($post_handler->get_post_arg("mode") == "add") {
        $link = "add_record.php";
    } else {
        $crud_handler = new Crud_Handler($post_handler, $runner, $preparer);
        $crud_handler->handle_crud_action();
        $link = "add_record.php";
    }
    $table_names_data = $runner->get_table_names();

    $choose_table_form = new Multichoice_Btn_Form(
        $table_names_data, "table_name", $link, "multichoice_btn_form");
    $choose_table_form->create();
} else {
    $login_mess = new Text_Field("insufficient permissions.", "login_mess");
    $login_mess->create();
}
$go_main_page_btn = new Btn_Form("Go to the main crud page", "crud_main_page.php", );
$go_main_page_btn->set_class_name("r_c_btn");
$go_main_page_btn->create();
?>

</div>
</body>
</html>
