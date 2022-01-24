<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>Product page</title>
    <!-- <link rel="stylesheet" href="styles.css"> -->
</head>

<h1>Product info</h1>

<?php
require_once("code_base.php");

$loader = new Db_Loader();
$preparer = new Data_Preparer();
$handler = new Data_Handler($_POST);

// check login
$login_data = $loader->check_login_status($_COOKIE, $preparer);
$is_logged = $login_data[0];

$group_id = $handler->get_colective_data();
$condition = $preparer->get_query_params($group_id, "pk");
$product_info = $loader->get_table_contents("product_groups", $condition)[0];

print("<h2>{$product_info["product_name"]}</h2>");

$price = new Text_Field($product_info["price"], "price");
$price->create();

$cond_is_available = $group_id;
$cond_is_available["is_available"] = 'TRUE';

$condition = $preparer->get_query_params($cond_is_available, "pk");
$instances_of_product = $loader->get_table_contents("products", $condition);

$products_left = count($instances_of_product);

$price = new Text_Field("{$products_left} left", "avaliable");
$price->create();

if ($is_logged) {  // if user is logged in, enable him to add to cart
    if ($products_left) {
        // if atleast one product is avaliable then we can pick by index 0
        $product_data = $instances_of_product[0];
        $product_id = $product_data["product_id"];
        $buy_btn = new Btn_Form("add to cart", "f_btn_submit", 
         ["mode"=>"add_to_cart", "product_id" => $product_id], "cart.php", "r_btn");
        $buy_btn->create();
    }
}

$description = new Text_Field($product_info["description"], "description");
$description->create();

$login_data = $loader->check_login_status($_COOKIE, $preparer);
$is_logged = $login_data[0];
$is_admin = $login_data[1];

$diff_table_btn = new Btn_Form("Go to the main page", "f_btn_submit", NULL, "index.php", "r_btn");
$diff_table_btn->create();
?>

</body>
</html>