<?php
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/utils/connectDatabase.php";

$num = $_GET["num"];

$query = $database->getConnection()->query(
    "SELECT News_id, Title, Content, Is_published, Created_at FROM News
               WHERE Is_published = 1 
               ORDER BY Created_at DESC LIMIT $num, 4"
);
$news = $query->fetchAll();

echo json_encode($news, JSON_UNESCAPED_UNICODE);
