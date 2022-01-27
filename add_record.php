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
$preparer = new Data_Preparer();
$loader = new Db_Loader();

$login_data = $loader->check_login_status($_COOKIE, $preparer);
$is_logged = $login_data[0];
$is_admin = $login_data[1];

if ($is_admin) {
    $handler = new Data_Handler($_POST);
    $table_name = $handler->get_post_arg("table_name");
    
    $r_data = ["table_name"=>$table_name, "page_num"=>$page_num,];
    
    $col_names = $loader->get_col_names($table_name);

    $text_form = new Text_Form([$col_names, $r_data], "choose_table.php", FALSE, "f_a_btn_submit", "text_form");
    $text_form->create();
    
    $diff_table_btn = new Btn_Form("Go back", "f_btn_submit", $r_data, "choose_table.php", "r_btn");
    $diff_table_btn->create();
} else {
    $login_mess = new Text_Field("insufficient permissions.", "login_mess");
    $login_mess->create();
}
$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "crud_main_page.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>