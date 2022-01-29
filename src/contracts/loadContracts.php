<?php

namespace App\applicationManagement;

require_once "/Users/klim/PhpstormProjects/card_dealer/src/general/getDatabaseConnection.php";

$num = $_GET["num"];

$query = $database->getConnection()->query(
    "SELECT cont.id, cont.number, status,
                       c.surname, c.given_name, c.patronymic
                       FROM contract cont JOIN client c on cont.client_id = c.id
                       WHERE cont.status = 'preparing'
                       ORDER BY cont.number LIMIT $num, 10"
);
$contracts = $query->fetchAll();

echo json_encode($contracts,JSON_UNESCAPED_UNICODE);