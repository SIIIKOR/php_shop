<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>CRUD main page</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>

<h1>CRUD main page</h1>

<?php
require_once("code_base.php");

$loader = new Db_Loader();
$preparer = new Data_Preparer();

$login_data = $loader->check_login_status($_COOKIE, $preparer);
$is_logged = $login_data[0];
$is_admin = $login_data[1];

if ($is_admin) {
    $diff_table_btn = new Btn_Form("Choose table", "f_btn_submit", ["mode"=>"vis"], "choose_table.php", "r_btn");
    $diff_table_btn->create();
    
    $diff_table_btn = new Btn_Form("Add record", "f_btn_submit", ["mode"=>"add"], "choose_table.php", "r_btn");
    $diff_table_btn->create();
} elseif ($is_logged) {
    $login_mess = new Text_Field("insufficient permissions.", "login_mess");
    $login_mess->create();
} else {
    $login_mess = new Text_Field("insufficient permissions.<br>You should be logged in.", "login_mess");
    $login_mess->create();
}

$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "index.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>