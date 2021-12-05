<?php
header("Content-Type: text/html; charset=utf-8");
error_reporting(-1);

$config = include_once "/Users/macbookair/Desktop/hcsSite/config/databaseInfo.php";
require_once "/Users/macbookair/Desktop/hcsSite/src/Database.php";

use App\Database;

$num = $_GET["num"];

$dsn = $config["dsn"];
$username = $config["username"];
$password = $config["password"];
$database = new Database($dsn, $username, $password);

$query = $database->getConnection()->query(
    "SELECT News_id, Title, Content, Is_published, Created_at FROM News
               WHERE Is_published = 1 
               ORDER BY Created_at DESC LIMIT {$num}, 4"
);
$news = $query->fetchAll();

echo json_encode($news,JSON_UNESCAPED_UNICODE);
