<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>Cart page</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>

<h1>Your cart</h1>

<?php
require_once("code_base.php");

$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "index.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>