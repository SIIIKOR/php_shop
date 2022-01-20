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

$handler = new Data_Handler($_POST);
$table_name = $handler->get_table_name();

$r_data = ["table_name"=>$table_name, "page_num"=>$page_num,];

$loader = new Db_Loader();
$preparer = new Data_Preparer();

$col_names = $loader->get_col_names($table_name);

$text_form = new Text_Form([$col_names, $r_data], "choose_table.php", FALSE, "f_a_btn_submit", "text_form");
$text_form->create();

$diff_table_btn = new Btn_Form("Go back", "f_btn_submit", $r_data, "choose_table.php", "r_btn");
$diff_table_btn->create();

$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "index.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>