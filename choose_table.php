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
$handler = new Data_Handler($_POST);
$preparer = new Data_Preparer();

$table_names = $loader->get_table_names();
$table_names_data = $preparer->tag_data("table_name", $table_names);

if ($handler->get_mode() == "vis") {
    $link = "display_table.php";
} elseif($handler->get_mode() == "add") {
    $link = "add_record.php";
} else {
    $link = "add_record.php";
    $table_name = $handler->get_table_name();
    $loader->handle_crud_action($handler, $preparer, $table_name);
}

$choose_table_form = new Multichoice_Btn_Form($table_names_data, $link, "multichoice_btn_form");
$choose_table_form->create();

$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "crud_main_page.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>