<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>PHP shop main page</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>

<h1>PHP shop</h1>

<?php
require_once("code_base.php");
$handler = new Data_Handler($_POST, 1);

if ($handler->get_mode() == "login_attempt") {
    $loader = new Db_Loader();
    $preparer = new Data_Preparer();

    $login_data = $handler->get_colective_data();
    $is_successful_login = $loader->check_login_attempt($login_data, $preparer);
}

$diff_table_btn = new Btn_Form("login", "f_btn_submit", ["mode"=>"login"], "login_page.php", "r_btn");
$diff_table_btn->create();

$diff_table_btn = new Btn_Form("register", "f_btn_submit", ["mode"=>"register"], "login_page.php", "r_btn");
$diff_table_btn->create()
?>

</body>
</html>