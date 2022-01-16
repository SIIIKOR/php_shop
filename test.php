<?php
require_once("code_base.php");

$loader = new Db_Loader();

$table_names = $loader->get_table_names();

$choose_table_form = new Multichoice_Btn_Form($table_names, "test.php", "test");
$choose_table_form->create();
?>