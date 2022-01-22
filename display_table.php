<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>Dsiplay table</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>

<h1>Display</h1>

<?php
require_once("code_base.php");

$handler = new Data_Handler($_POST);

$records_per_page = 5;
$page_num = $handler->get_page_num();
if (!$page_num) {
    $page_num = 0;
}
$table_name = $handler->get_table_name();

$loader = new Db_Loader();
$preparer = new Data_Preparer();

$loader->handle_crud_action($handler, $preparer, $table_name);

$data = $loader->get_db_contents_curr_page($table_name, $page_num, $records_per_page);
$col_names = $loader->get_col_names($table_name);
$primary_keys = $loader->get_primary_key_names();

$table = new Table($data, $col_names, $primary_keys, $table_name, $page_num);
$table->create();

$total_row_count = $loader->get_table_row_amount($table_name);
$pagination = new Pagination($table_name, $page_num, $records_per_page, $total_row_count, "display_table.php");
$pagination->create();

$diff_table_btn = new Btn_Form("Choose different table", "f_btn_submit", ["mode"=>"vis"], "choose_table.php", "r_btn");
$diff_table_btn->create();

$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "crud_main_page.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>