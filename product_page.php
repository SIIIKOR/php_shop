<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>Product page</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>

<h1>Product</h1>

<?php
require_once("code_base.php");

$loader = new Db_Loader();
$preparer = new Data_Preparer();
$handler = new Data_Handler($_POST);

$group_id = $handler->get_colective_data()["group_id"];
print_r($group_id);
// $product_info = $loader->get_table_contents("group_id", ["group_id"=>$group_id]);
// print_r($product_info);

$login_data = $loader->check_login_status($_COOKIE, $preparer);
$is_logged = $login_data[0];
$is_admin = $login_data[1];

$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "index.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>