<?php

require_once("code_base.php");

$loader = new Db_Loader();
$preparer = new Data_Preparer();

$runner = new Query_Runer();
$runner->set_loader($loader);
$runner->set_preparer($preparer);
print_r($runner->get_table_names());
print("<br><br>");
print_r($runner->get_table_row_amount("products"));
print("<br><br>");
print_r($runner->get_column_names("products"));
print("<br><br>");
print_r($runner->get_primary_key_names());
print("<br><br>");
// print_r($runner->get_table_contents(["*"], ["products"]));