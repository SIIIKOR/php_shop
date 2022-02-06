<!DOCTYPE HTML>
<html lang="en">
<body>
<head>
    <title>Product page</title>
    <link rel="stylesheet" href="styles.css">
</head>

<h1>Product info</h1>

<div id="main">

<?php
require_once("code_base.php");

$post_handler = new Post_Data_Handler($_POST, 1);
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
// fetch data based on id from post
$group_id = $post_handler->get_colective_data()["id"];
$product_info = $runner->get_table_contents(["*"], ["product_groups"], ["id"=>$group_id])[0];

print("<h2>{$product_info["product_name"]}</h2>");

$price = new Text_Field($product_info["price"], "price");
$price->create();

$instances_of_product = $runner->get_table_contents(
    ["id"], ["products"], ["group_id"=>$group_id, "is_available"=>TRUE]);
$products_left = count($instances_of_product);

$price = new Text_Field("{$products_left} left", "avaliable");

if ($logger->is_logged()) {
    if ($products_left) {
        // if atleast one product is avaliable then we can pick by index 0
        $product_data = $instances_of_product[0];
        $product_id = $product_data["id"];
        $buy_btn = new Btn_Form("add to cart", "cart.php",
            ["mode"=>"add_to_cart", "id" => $product_id]);
        $buy_btn->set_class_name("r_btn");
    }
}
if (isset($buy_btn)) {
    $btn_html = $buy_btn->get_html();
}

print("<div class=\"buy_panel\">{$price->get_html()}{$btn_html}</div>");

$description = new Text_Field($product_info["description"], "description");
$description->create();

$go_main_page_btn = new Btn_Form("Go to the main page", "index.php", );
$go_main_page_btn->set_class_name("r_c_btn");
$go_main_page_btn->create();
?>

</div>
</body>
</html>