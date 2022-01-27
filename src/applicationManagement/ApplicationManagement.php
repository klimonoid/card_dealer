<?php

declare(strict_types=1);

namespace App\applicationManagement;

use App\Database;
use App\Session;

class ApplicationManagement
{
    /**
     * @var Database
     */
    private Database $database;

    private Session $session;

    /**
     * UserEditor constructor
     * @param Database $database
     * @param Session $session
     */
    public function __construct(Database $database, Session $session)
    {
        $this->database = $database;
        $this->session = $session;
    }

    public function create_application($client_id)
    {
        $statement = $this->database->getConnection()->prepare(
            'INSERT INTO application (applicant_id, date_of_submission, status, comment)
                    VALUES (:applicant_id, :date_of_submittion, :status, :comment)'
        );
        $statement->execute([
            'applicant_id' => $client_id,
            'date_of_submittion' => date('Y-m-d H:i:s'),
            'status' => 'accepted',
            'comment' => ''
        ]);
    }

    public function edit_application($params, $application_id)
    {
        $status = 'approved';
        if ($params['exampleRadios'] == 'rejected') {
            $status = 'rejected';
        }
        if(strlen($params['comment']) > 255) {
            throw new ApplicationException('Ваш комментарий слишком длинный (Максимальный размер – 255 символов)');
        }
        $this->database->getConnection()->query("
            LOCK TABLES application WRITE;
        ");
//        sleep(15);
        $statement = $this->database->getConnection()->prepare(
            'UPDATE application SET status = :status, comment = :comment
                    WHERE id = :id'
        );
        $this->database->getConnection()->query("UNLOCK TABLES;");
        $statement->execute([
            'status' => $status,
            'comment' => $params['comment'],
            'id' => $application_id
        ]);
    }
}