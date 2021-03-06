<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>Dsiplay table</title>
    <link rel="stylesheet" href="styles.css">
</head>

<h1>Display</h1>

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
    $records_per_page = 20;
    $page_num = $post_handler->get_post_arg("page_num");
    if (!$page_num) {
        $page_num = 0;
    }
    $crud_handler = new Crud_Handler($post_handler, $runner, $preparer);
    $crud_handler->handle_crud_action();

    $table_name = $post_handler->get_post_arg("table_name");
    $table_data = $runner->get_table_contents(
        ["*"], [$table_name], NULL, FALSE, $page_num, $records_per_page);
    if (!empty($table_data)) {
        $table = new Table($table_data);
        $table->set_class_name("table");
        $table->set_primary_keys($runner->get_primary_key_names()[$table_name]);
        $table->set_btn_data(["table_name"=>$table_name, "page_num"=>$page_num]);
        $table->set_btn_link("update_table.php");
        $table->create();
    }
    
    $total_row_count = $runner->get_table_contents(["count(*)"], [$table_name])[0]["count"];
    $pagination = new Pagination($page_num, $records_per_page, $total_row_count, "display_table.php");
    $pagination->set_btn_data(["table_name"=>$table_name]);
    $pagination->create();
    
    $diff_table_btn = new Btn_Form("Choose different table", "choose_table.php", ["mode"=>"vis"]);
    $diff_table_btn->set_class_name("r_c_btn");
    $diff_table_btn->create();
} else {
    $login_mess = new Text_Field("insufficient permissions.", "login_mess");
    $login_mess->create();
}
$go_main_crud_page_btn = new Btn_Form("Go to the main crud page", "crud_main_page.php");
$go_main_crud_page_btn->set_class_name("r_c_btn");
$go_main_crud_page_btn->create();
?>

</div>
</body>
</html>
