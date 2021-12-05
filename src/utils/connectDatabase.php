<?php
header("Content-Type: text/html; charset=utf-8");
error_reporting(-1);

$config = include_once "/Users/macbookair/Desktop/Housing_and_communal_services/config/databaseInfo.php";
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/Database.php";

use App\Database;

$dsn = $config["dsn"];
$username = $config["username"];
$password = $config["password"];
$database = new Database($dsn, $username, $password);