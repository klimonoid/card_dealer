<?php

declare(strict_types=1);

namespace App\contracts;

use App\Database;
use App\Session;
use App\users\AuthorizationException;

class ContractManagement
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

    public function create_contract($application_id)
    {
        $statement = $this->database->getConnection()->prepare(
            'SELECT c.id FROM application a
                    JOIN client c on a.applicant_id = c.id
                    WHERE a.id = :application_id'
        );
        $statement->execute([
            'application_id' => $application_id,
        ]);
        $client_id = $statement->fetch()['id'];

        $statement = $this->database->getConnection()->prepare(
            'INSERT INTO contract 
                    (client_id, application_id, status)
                    VALUES (:client_id, :application_id, :status)'
        );
        $statement->execute([
            'client_id' => $client_id,
            'application_id' => $application_id,
            'status' => 'preparing'
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function validate_user($phone, $password): string
    {
        if(empty($phone)) {
            throw new AuthorizationException('Номер телефона не должен быть пустым');
        }
        if(empty($password)) {
            throw new AuthorizationException('Пароль не должен быть пустым');
        }
        $statement = $this->database->getConnection()->prepare(
            'SELECT * FROM client WHERE phone = :phone'
        );
        $statement->execute([
            'phone' => $phone
        ]);
        $client = $statement->fetch();
        if(empty($client)) {
            throw new AuthorizationException('Пользователя с таким номером телефона не существует');
        }
        if(password_verify($password, $client['password'])) {
            $this->session->setData('ready_to_sign_the_contract', true);
            return $client['id'];
        }

        throw new AuthorizationException('Неверные номер телефона или пароль');
    }

    public function fromPreparingToReady($contract_id)
    {
        $this->database->getConnection()->query("
            LOCK TABLES contract WRITE;
        ");
//        sleep(15);
        $statement = $this->database->getConnection()->prepare(
            'UPDATE contract SET
                    status = :status, comment = :comment
                    WHERE id = :id'
        );
        $statement->execute([
            'status' => 'ready',
            'comment' => 'Ждём вас в отделении банка для подписания договора',
            'id' => $contract_id
        ]);

        $this->database->getConnection()->query("UNLOCK TABLES;");
    }

    /**
     * @throws ContractException
     */
    public function edit_contract($params, $contract_id, $inspector_id): bool
    {
        $status = 'accepted';
        if ($params['exampleRadios'] == 'rejected') {
            $status = 'rejected';
        }
        if(strlen($params['comment']) > 255) {
            throw new ContractException(
                'Ваш комментарий слишком длинный' .
                '(Максимальный размер – 255 символов)'
            );
        }
        $statement = $this->database->getConnection()->prepare(
            'UPDATE contract SET
                    inspector_id = :inspector_id, date_of_submission = :date_of_submittion,
                    status = :status, comment = :comment
                    WHERE id = :id'
        );
        if ($status == 'accepted' and $params['comment'] == null) {
            $params['comment'] = 'По вашему договору подготавливается счёт и карта.
            Их готовность вы можете отследить в разделах "Мои счета" и "Мои карты" соответственно';
        }
        $statement->execute([
            'inspector_id' => $inspector_id,
            'date_of_submittion' => date('Y-m-d H:i:s'),
            'status' => $status,
            'comment' => $params['comment'],
            'id' => $contract_id
        ]);
        if ($status == 'accepted') {
            return true;
        }
        return false;
    }
}