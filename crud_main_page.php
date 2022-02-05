<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>CRUD main page</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>

<h1>CRUD main page</h1>

<?php
require_once("code_base.php");

$post_handler = new Post_Data_Handler($_POST);
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
    // btn used to move to page where admin can choose table
    $choose_table_btn = new Btn_Form(["mode"=>"vis"], "choose_table.php", "r_btn");
    $choose_table_btn->set_text("Choose table");
    $choose_table_btn->set_name("f_btn_submit");
    $choose_table_btn->create();
    // btn used to move to page where admin can add records
    $add_record_btn = new Btn_Form(["mode"=>"add"], "choose_table.php", "r_btn");
    $add_record_btn->set_text("Add record");
    $add_record_btn->set_name("f_btn_submit");
    $add_record_btn->create();
} elseif ($logger->is_logged()) {
    $login_mess = new Text_Field("insufficient permissions.", "login_mess");
    $login_mess->create();
} else {
    $login_mess = new Text_Field("insufficient permissions.<br>You should be logged in.", "login_mess");
    $login_mess->create();
}

$go_main_page_btn = new Btn_Form();
$go_main_page_btn->set_text("Go to the main page");
$go_main_page_btn->set_name("f_btn_submit");
$go_main_page_btn->set_link("index.php");
$go_main_page_btn->set_class_name("r_btn");
$go_main_page_btn->create();
?>

</body>
</html>