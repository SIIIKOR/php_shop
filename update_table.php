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

$handler = new Data_Handler($_POST);
$primary_keys = $handler->get_colective_data(2);
$table_name = $handler->get_table_name();
$page_num = $handler->get_page_num();

$r_data = ["table_name"=>$table_name, "page_num"=>$page_num,];

$loader = new Db_Loader();
$pre = new Data_Preparer();

$condition = $pre->get_condition($primary_keys);

$chosen_row = $loader->get_table_row($table_name, $condition);

$text_form = new Text_Form([$chosen_row, $r_data], "choose_table.php", "text_form");
$text_form->create();

$diff_table_btn = new Btn_Form("Go back", "f_btn_submit", $r_data, "display_table.php", "r_btn");
$diff_table_btn->create();

$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "index.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>