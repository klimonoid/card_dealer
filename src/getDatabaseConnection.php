<?php

header("Content-Type: text/html; charset=utf-8");
error_reporting(-1);

$config = include_once "/Users/klim/PhpstormProjects/card_dealer/config/database.php";
require_once "/Users/klim/PhpstormProjects/card_dealer/src/Database.php";

use App\Database;

$dsn = $config["dsn"];
$username = $config["username"];
$password = $config["password"];
$database = new Database($dsn, $username, $password);