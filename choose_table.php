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
$pre = new Data_Preparer();

$table_names = $loader->get_table_names();
$table_names_data = $pre->tag_data("table_name", $table_names);

$choose_table_form = new Multichoice_Btn_Form($table_names_data, "display_table.php", "multichoice_btn_form");
$choose_table_form->create();

$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "index.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>