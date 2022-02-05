<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>Choose table</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>

<h1>Choose table</h1>

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
    $table_name = $post_handler->get_post_arg("table_name");
    $col_names = $runner->get_column_names($table_name);

    $text_form = new Text_Form($col_names, "choose_table.php", FALSE, "text_form");
    $text_form->set_hidden_data(["table_name"=>$table_name, "mode"=>"insert"]);
    $text_form->create();
    
    $diff_table_btn = new Btn_Form(["mode"=>"add"], "choose_table.php", "r_btn");
    $diff_table_btn->set_text("Choose different table");
    $diff_table_btn->set_name("f_btn_submit");
    $diff_table_btn->create();
} else {
    $login_mess = new Text_Field("insufficient permissions.", "login_mess");
    $login_mess->create();
}
$go_main_crud_page_btn = new Btn_Form();
$go_main_crud_page_btn->set_text("Go to the main crud page");
$go_main_crud_page_btn->set_name("f_btn_submit");
$go_main_crud_page_btn->set_link("crud_main_page.php");
$go_main_crud_page_btn->set_class_name("r_btn");
$go_main_crud_page_btn->create();
?>

</body>
</html>