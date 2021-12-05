<?php
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/utils/connectDatabase.php";

$num = $_GET["num"];

$query = $database->getConnection()->query(
    "SELECT Consumer_id, First_name, Last_name, Patronymic, 
                      Consumer_email, Birthday, Telephone_number FROM Consumer
               ORDER BY Last_name, First_name LIMIT $num, 4"
);
$news = $query->fetchAll();

echo json_encode($news,JSON_UNESCAPED_UNICODE);
