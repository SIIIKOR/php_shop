<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>Modifying records</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>

<h1>Modify</h1>

<?php
require_once("code_base.php");

$loader = new Db_Loader();
$preparer = new Data_Preparer();

$login_data = $loader->check_login_status($_COOKIE, $preparer);
$is_logged = $login_data[0];
$is_admin = $login_data[1];

if ($is_admin) {
    $handler = new Data_Handler($_POST);
    $cond_values = $handler->get_colective_data();
    $table_name = $handler->get_post_arg("table_name");
    $page_num = $handler->get_post_arg("page_num");
    
    $r_data = ["table_name"=>$table_name, "page_num"=>$page_num,];
    
    $condition = $preparer->get_query_params($cond_values);
    
    $chosen_row = $loader->get_table_contents($table_name, $condition);
    $btn_data = array_merge($preparer->identify_data($cond_values, "pk"), $r_data);
    
    $text_form = new Text_Form([$chosen_row[0], $btn_data], "display_table.php", TRUE, "f_u_btn_submit", "text_form");
    $text_form->create();
    
    $diff_table_btn = new Btn_Form("Delete", "f_d_btn_submit", array_merge($cond_values, $r_data), "display_table.php", "r_btn");
    $diff_table_btn->create();
    
    $diff_table_btn = new Btn_Form("Go back", "f_btn_submit", $r_data, "display_table.php", "r_btn");
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