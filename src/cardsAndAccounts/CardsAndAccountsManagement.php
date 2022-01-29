<?php

declare(strict_types=1);

namespace App\cardsAndAccounts;

use App\Database;
use App\Session;
use App\users\AuthorizationException;
use DateInterval;
use DateTime;
use Exception;

class CardsAndAccountsManagement
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
     * @throws Exception
     */
    public function create_card_and_account($contract_id)
    {
        $statement = $this->database->getConnection()->prepare(
            'SELECT client.id FROM client
                    JOIN contract c on client.id = c.client_id
                    WHERE c.id = :contract_id'
        );
        $statement->execute([
            'contract_id' => $contract_id
        ]);
        $client_id = $statement->fetch()['id'];

        $statement = $this->database->getConnection()->prepare(
            'INSERT INTO account
                    (open_date, balance, client_id, status)
                    VALUES (:open_date, :balance, :client_id, :status)'
        );
        $statement->execute([
            'open_date' => date('Y-m-d H:i:s'),
            'balance' => 0,
            'client_id' => $client_id,
            'status' => 'frozen'
        ]);
        $statement = $this->database->getConnection()->prepare(
            'SELECT id FROM account
                    WHERE client_id = :client_id'
        );
        $statement->execute([
            'client_id' => $client_id
        ]);
        $account_id = $statement->fetch()['id'];

        $statement = $this->database->getConnection()->prepare(
            'INSERT INTO card
                    (service_end_date, status, client_id, account_id)
                    VALUES (:service_end_date, :status, :client_id, :account_id)'
        );
        $diff4years = new DateInterval('P4Y');
        $date = new DateTime(date('Y-m-d H:i:s'));
        $date->add($diff4years);
        $statement->execute([
            'service_end_date' => $date->format('Y-m-d H:i:s'),
            'status' => 'preparing',
            'client_id' => $client_id,
            'account_id' => $account_id
        ]);
    }

    public function fromPreparingToReady($card_id)
    {
        $this->database->getConnection()->query("
            LOCK TABLES card WRITE;
        ");
//        sleep(15);
        $statement = $this->database->getConnection()->prepare(
            'UPDATE card SET
                    status = :status
                    WHERE id = :id'
        );
        $statement->execute([
            'status' => 'ready',
            'id' => $card_id
        ]);

        $this->database->getConnection()->query("UNLOCK TABLES;");
    }

    /**
     * @throws AuthorizationException
     */
    public function validate_user($phone, $password)
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
            $this->session->setData('ready_to_get_card', true);
            return $client['id'];
        }

        throw new AuthorizationException('Неверные номер телефона или пароль');
    }

    /**
     * @throws CardException
     */
    public function edit_card_and_account(array $params, $card_id)
    {
        $status = 'working';
        if ($params['exampleRadios'] == 'rejected') {
            $status = 'lost';
        }
        if (strlen($params['pin']) != 4) {
            throw new CardException('ПИН-код должен состоять из 4 цифр');
        }
        $statement = $this->database->getConnection()->prepare(
            'UPDATE card SET
                    status = :status, pin = :pin
                    WHERE id = :id'
        );
        $statement->execute([
            'status' => $status,
            'pin' => $params['pin'],
            'id' => $card_id
        ]);
        $statement = $this->database->getConnection()->prepare(
            'SELECT * from card WHERE id = :id'
        );
        $statement->execute([
            'id' => $card_id
        ]);

        $account_id = $statement->fetch()['id'];

        if ($status == 'lost') {
            $status = 'frozen';
        }

        $statement = $this->database->getConnection()->prepare(
            'UPDATE account SET
                    status = :status
                    WHERE id = :id'
        );
        $statement->execute([
            'status' => $status,
            'id' => $account_id
        ]);
    }
}