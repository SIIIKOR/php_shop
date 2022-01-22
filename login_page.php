<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>Login|Register page</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>

<?php
require_once("code_base.php");
$handler = new Data_Handler($_POST, 1);

if ($handler->get_mode() == "login_after_register") {
    $loader = new Db_Loader();
    $preparer = new Data_Preparer();

    $register_data = $handler->get_colective_data();
    $is_successful_reg = $loader->register_user($register_data, $preparer);
}

if ($handler->get_mode() == "login" or $is_successful_reg) {
    print("<h1>Login page</h1>");
    $col_names = ["e-mail", "password"];
    $preset = FALSE;
    if ($is_successful_reg) {
        $col_names = ["e-mail"=>$register_data["e-mail"], "password"=>$register_data["password"]];
        $preset = TRUE;
    }
    $text_form = new Text_Form([$col_names, NULL], "index.php", $preset, "f_a_btn_submit", "text_form");
    $text_form->create();
} elseif($handler->get_mode() == "register" or !$is_successful_reg) {
    print("<h1>Register page</h1>");
    $col_names = ["name", "surname", "e-mail", "password"];
    $text_form = new Text_Form([$col_names, ["mode"=>"login_after_register"]], "login_page.php", FALSE, "f_a_btn_submit", "text_form");
    $text_form->create();
}

$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "index.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>