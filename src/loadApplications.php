<?php
require_once "/Users/klim/PhpstormProjects/card_dealer/src/getDatabaseConnection.php";

$num = $_GET["num"];

$query = $database->getConnection()->query(
    "SELECT a.id, c.surname, c.given_name, c.patronymic ,date_of_submission
                        FROM application a
                        JOIN client c ON c.id = a.applicant_id
                        WHERE status = 'accepted' 
                        ORDER BY date_of_submission LIMIT $num, 10"
);
$applications = $query->fetchAll();

echo json_encode($applications,JSON_UNESCAPED_UNICODE);