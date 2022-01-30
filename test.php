<?php

require_once("code_base.php");

$loader = new Db_Loader();

$prep = new Data_Preparer();

$runner = new Query_Runner($loader, $prep);

$data = [
    "name"=>"mateusz",
    "surname"=>"sikorski",
    "mail"=>"mateusz.sidsakfdsorski@gmail.com",
    "password"=>"haslo"];

$shop = new Shop_Handler($runner, $data);

$satus = $shop->register_user($data);

print($satus);

// $logger = new Login_handler($runner, [
//     "mail"=>"mateusz.sikorski@gmail.com",
//     "password"=>"haslo"
// ]);

// $logger = new Login_handler($runner, [
//     "mail"=>"mateusz.sikorski@gmail.com",
//     "token"=>"cc2862cc167dd721"
// ]);

// print($logger->is_logged());
// print("<br><br>");
// print($logger->is_admin());