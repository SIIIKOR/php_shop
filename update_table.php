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
// object used to run queries
$runner = new Query_Runner($loader, $preparer);
// object used to procces login data
$logger = new Login_handler($runner);

// check login
if (isset($_COOKIE["cookie_token"])) {
    $logger->set_login($_COOKIE, TRUE);
}

if ($logger->is_admin()) {
    $post_handler = new Post_Data_Handler($_POST, 3);
    $table_name = $post_handler->get_post_arg("table_name");
    $page_num = $post_handler->get_post_arg("page_num");
    $pk_values = $post_handler->get_colective_data();

    $r_data = ["table_name"=>$table_name, "page_num"=>$page_num,];
    
    $chosen_row = $runner->get_table_contents(["*"], [$table_name], $pk_values)[0];
    
    $text_form = new Text_Form($chosen_row, "display_table.php", TRUE, "text_form");
    $ident_data = $preparer->get_identified_data($pk_values, "pk");
    $text_form->set_hidden_data(array_merge($ident_data, $r_data, ["mode"=>"update"]));
    $text_form->set_btn_name("f_u_btn_submit");
    $text_form->create();

    $diff_table_btn = new Btn_Form("Delete", "display_table.php", array_merge($pk_values, $r_data, ["mode"=>"delete"]), "f_d_btn_submit");
    $diff_table_btn->set_class_name("r_btn");
    $diff_table_btn->create();
    
    $diff_table_btn = new Btn_Form("Go back", "display_table.php", $r_data);
    $diff_table_btn->set_class_name("r_btn");
    $diff_table_btn->create();
} else {
    $login_mess = new Text_Field("insufficient permissions.", "login_mess");
    $login_mess->create();
}

$go_main_crud_page_btn = new Btn_Form("Go to the main crud page", "crud_main_page.php", );
$go_main_crud_page_btn->set_class_name("r_btn");
$go_main_crud_page_btn->create();
?>

</body>
</html>