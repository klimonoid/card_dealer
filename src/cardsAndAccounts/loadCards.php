<?php

namespace App\applicationManagement;

require_once "/Users/klim/PhpstormProjects/card_dealer/src/general/getDatabaseConnection.php";

$num = $_GET["num"];

$query = $database->getConnection()->query(
    "SELECT card.id, card.number,
                       c.surname, c.given_name, c.patronymic
                       FROM card JOIN client c on card.client_id = c.id
                       WHERE card.status = 'preparing'
                       ORDER BY card.id LIMIT $num, 10"
);
$cards = $query->fetchAll();

echo json_encode($cards,JSON_UNESCAPED_UNICODE);
