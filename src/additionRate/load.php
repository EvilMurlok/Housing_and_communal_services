<?php
require_once "/Users/macbookair/Desktop/Housing_and_communal_services/src/utils/connectDatabase.php";

$num = $_GET["num"];

$query = $database->getConnection()->query(
    "SELECT r.Resource_organization_id, r.Service_name, r.Unit, 
                              r.Unit_cost, o.Organization_name, 
                              o.Telephone_number, o.Organization_email
                       FROM Rate r
                       INNER JOIN ResourceOrganization o using (Resource_organization_id)
                       ORDER BY r.Service_name 
                       LIMIT $num, 4"
);

$rates = $query->fetchAll();

echo json_encode($rates, JSON_UNESCAPED_UNICODE);