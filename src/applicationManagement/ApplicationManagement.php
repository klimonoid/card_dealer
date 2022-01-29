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

    /**
     * @throws ApplicationException
     */
    public function create_application($client_id)
    {
        $statement = $this->database->getConnection()->prepare(
            'SELECT *
                       FROM application
                       WHERE applicant_id = :client_id AND status = :accepted'
        );
        $statement->execute([
            'client_id' => $client_id,
            'accepted' => 'accepted'
        ]);
        $application = $statement->fetch();
        if ($application != false) {
            throw new ApplicationException(
                'Вы не можете создать новое заявление пока мы не обработаем ваше старое'
            );
        }
        $statement = $this->database->getConnection()->prepare(
            'INSERT INTO application 
                    (applicant_id, date_of_submission, status, comment)
                    VALUES (:applicant_id, :date_of_submittion, :status, :comment)'
        );
        $statement->execute([
            'applicant_id' => $client_id,
            'date_of_submittion' => date('Y-m-d H:i:s'),
            'status' => 'accepted',
            'comment' => ''
        ]);
    }

    /**
     * @throws ApplicationException
     */
    public function edit_application($params, $application_id): bool
    {
        $status = 'approved';
        if ($params['exampleRadios'] == 'rejected') {
            $status = 'rejected';
        }
        if(strlen($params['comment']) > 255) {
            throw new ApplicationException(
                'Ваш комментарий слишком длинный' .
                '(Максимальный размер – 255 символов)'
            );
        }
        $this->database->getConnection()->query("
            LOCK TABLES application WRITE;
        ");
        sleep(15);
        $statement = $this->database->getConnection()->prepare(
            'UPDATE application SET
                    inspector_id = :inspector_id, status = :status, comment = :comment
                    WHERE id = :id'
        );
        if ($status == 'approved' and $params['comment'] == null) {
            $params['comment'] = 'По вашему заявлению подготавливается договор.
            Его готовность вы можете отследить в разделе "Мои договоры"';
        }
        $statement->execute([
            'inspector_id' => $this->session->getData("user")["user_id"],
            'status' => $status,
            'comment' => $params['comment'],
            'id' => $application_id
        ]);
        $this->database->getConnection()->query("UNLOCK TABLES;");
        if ($status == 'approved') {
            return true;
        } else {
            return false;
        }
    }
}