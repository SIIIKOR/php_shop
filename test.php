<?php

require_once("code_base.php");

$preparer = new Data_Preparer();

$query = new Psql_Query_Select_With();
$query->set_preparer($preparer);

$query->set_statement_values_arr([
    "products.product_id", "product_name",
    "price", "category_name", "description"
    ]);
$query->set_table_names_arr([
    "product_groups", "products", "sq_name"
    ]);
$query->set_condition_arr([
        "sq_name.product_id"=>"products.product-id",
        "products.group_id"=>"product_groups.group_id"
    ]);
$query->set_wstatement_values_arr(["product_id"]);
$query->set_wtable_names_arr(["cart"]);
$query->set_wcondition_arr(["user_id"=>88]);