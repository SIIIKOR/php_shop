<?php

require_once("code_base.php");

// $loader = new Db_Loader();

// $prep = new Data_Preparer();

// $runner = new Query_Runner($loader, $prep);

// // $data = [
// //     "name"=>"mateusz",
// //     "surname"=>"sikorski",
// //     "mail"=>"mateusz.sikorski@ymail.com",
// //     "password"=>"haslo"];

// // $shop = new Shop_Handler($runner, $data);

// // $status = $shop->register_user($data);

// // print($status);

// // $logger = new Login_handler($runner, [
// //     "mail"=>"mateusz.sikorski@ymail.com",
// //     "password"=>"haslo"
// // ]);

// // $logger = new Login_handler($runner, [
// //     "mail"=>"admin@mail.com",
// //     "password"=>"admin"
// // ]);

// $logger = new Login_handler($runner, [
//     "mail"=>"admin@mail.com",
//     "cookie_token"=>"admin"
// ], TRUE);

// print("Logged: {$logger->is_logged()}");
// print("<br><br>");
// print("Admin: {$logger->is_admin()}");
$query = new Psql_Query_Select();
$query->set_select_statement(["tokens.id", "cookie_token"]);
$query->set_from_statement(["tokens", "user_ids"]);
$query->set_where_statement(["user_ids.id"=>["tokens.id", "symbolic"]]);

$sub_query = new Psql_Query_Select();
$sub_query->set_select_statement(["id"]);
$sub_query->set_from_statement(["users"]);
$sub_query->set_where_statement(["mail"=>$mail]);
print("Tu<br>");

$out = $query->get_query();
print($out);