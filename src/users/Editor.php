<?php

declare(strict_types=1);

namespace App\users;

use App\Database;
use App\Session;

class Editor
{
    /**
     * @var Database
     */
    private Database $database;

    private Session $session;

    private string $pattern_phone = '/^(\s*)?(\+)?([- _():=+]?\d[- _():=+]?){10,14}(\s*)?$/';
    private string $pattern_password = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/';

    /**
     * Authorization constructor
     * @param Database $database
     */
    public function __construct(Database $database, Session $session)
    {
        $this->database = $database;
        $this->session = $session;
    }

    /**
     * Edit client
     * @param array $data
     * @param $client_id
     * @return bool
     * @throws AuthorizationException
     */
    public function edit_client(array $data, $client_id): bool
    {
        if(empty($data['surname'])) {
            throw new AuthorizationException('Фамилия не должна быть пустой');
        }
        if(empty($data['given_name'])) {
            throw new AuthorizationException('Имя не должно быть пустым');
        }
        if(empty($data['age'])) {
            throw new AuthorizationException('Возраст не должен быть пустым');
        }
        if($data['age'] < 18) {
            throw new AuthorizationException('Пользователю должно быть больше 18');
        }
        if(empty($data['series'])) {
            throw new AuthorizationException('Серия паспорта не должна быть пустой');
        }
        if(strlen($data['series']) != 4) {
            throw new AuthorizationException('Серия паспорта должна состоять из 4 цифр');
        }
        if(empty($data['number'])) {
            throw new AuthorizationException('Номер паспорта не должен быть пустым');
        }
        if(100 > (int)$data['number'] || (int)$data['number'] > 1000000) {
            throw new AuthorizationException('Неправильный номер паспорта');
        }
        if(empty($data['phone'])) {
            throw new AuthorizationException('Номер телефона не должен быть пустым');
        }
        if(preg_match($data['phone'], $this->pattern_phone)) {
            throw new AuthorizationException('Неправильный формат номера');
        }
        if(empty($data['password'])) {
            throw new AuthorizationException('Пароль не должен быть пустым');
        }
        if(preg_match($data['password'], $this->pattern_password)) {
            throw new AuthorizationException('Слишком простой пароль');
        }
        if($data['password'] !== $data['confirm_password']) {
            throw new AuthorizationException('Пароли должны совпадать');
        }

        $statement = $this->database->getConnection()->prepare(
            'SELECT * FROM client WHERE phone = :phone'
        );
        $statement->execute([
            'phone' => $data['phone']
        ]);
        if($statement->rowCount() > 1) {
            throw new AuthorizationException('Пользователь с таким номером телефона уже зарегистрирован');
        }

        $statement = $this->database->getConnection()->prepare(
            'SELECT * FROM client WHERE series = :series AND number = :number'
        );
        $statement->execute([
            'series' => $data['series'],
            'number' => $data['number']
        ]);
        if($statement->rowCount() > 1) {
            throw new AuthorizationException('Пользователь с таким паспортом уже зарегистрирован');
        }

        $statement = $this->database->getConnection()->prepare(
            'UPDATE client SET surname = :surname, given_name = :given_name, patronymic = :patronymic,
                  age = :age, series = :series, number = :number, phone = :phone, password = :password
                  WHERE id = :client_id'
        );
        $statement->execute([
            'surname' => $data['surname'],
            'given_name' => $data['given_name'],
            'patronymic' => $data['patronymic'],
            'age' => $data['age'],
            'series' => $data['series'],
            'number' => $data['number'],
            'phone' => $data['phone'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'client_id' => $client_id
        ]);
        $client = $statement->fetch();
        $this->session->setData('user', [
            'user_id' => $client_id,
            'given_name' => $data["given_name"],
            'phone' => $client['phone'],
            'is_staff' => false,
        ]);
        return true;
    }

    /**
     * Delete client
     * @param int $client_id
     * @return void
     */
    public function delete_client(int $client_id)
    {
//        var_dump($client_id);
        $statement = $this->database->getConnection()->prepare(
            'DELETE FROM client WHERE id = :client_id'
        );
        $statement->execute([
            'client_id' => $client_id
        ]);
        $this->session->setData('user', null);
    }

    /**
     * Edit client
     * @param array $data
     * @param $employee_id
     * @return bool
     * @throws AuthorizationException
     */
    public function edit_employee(array $data, $employee_id): bool
    {
        if(empty($data['surname'])) {
            throw new AuthorizationException('Фамилия не должна быть пустой');
        }
        if(empty($data['given_name'])) {
            throw new AuthorizationException('Имя не должно быть пустым');
        }
        if(empty($data['age'])) {
            throw new AuthorizationException('Возраст не должен быть пустым');
        }
        if($data['age'] < 18) {
            throw new AuthorizationException('Пользователю должно быть больше 18');
        }
        if(empty($data['phone'])) {
            throw new AuthorizationException('Номер телефона не должен быть пустым');
        }
        if(preg_match($data['phone'], $this->pattern_phone)) {
            throw new AuthorizationException('Неправильный формат номера');
        }
        if(empty($data['password'])) {
            throw new AuthorizationException('Пароль не должен быть пустым');
        }
        if(preg_match($data['password'], $this->pattern_password)) {
            throw new AuthorizationException('Слишком простой пароль');
        }
        if($data['password'] !== $data['confirm_password']) {
            throw new AuthorizationException('Пароли должны совпадать');
        }

        $statement = $this->database->getConnection()->prepare(
            'SELECT * FROM employee WHERE phone = :phone'
        );
        $statement->execute([
            'phone' => $data['phone']
        ]);
        if($statement->rowCount() > 1) {
            throw new AuthorizationException('Сотрудник с таким номером телефона уже зарегистрирован');
        }

        $statement = $this->database->getConnection()->prepare(
            'UPDATE employee SET surname = :surname, given_name = :given_name, patronymic = :patronymic,
                  age = :age, phone = :phone, password = :password
                  WHERE id = :employee_id'
        );
        $statement->execute([
            'surname' => $data['surname'],
            'given_name' => $data['given_name'],
            'patronymic' => $data['patronymic'],
            'age' => $data['age'],
            'phone' => $data['phone'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'employee_id' => $employee_id
        ]);
        $client = $statement->fetch();
        $this->session->setData('user', [
            'user_id' => $employee_id,
            'given_name' => $data["given_name"],
            'phone' => $client['phone'],
            'is_staff' => true,
        ]);
        return true;
    }

    /**
     * Delete client
     * @param int $employee_id
     * @return void
     */
    public function delete_employee(int $employee_id)
    {
//        var_dump($employee_id);
        $statement = $this->database->getConnection()->prepare(
            'DELETE FROM employee WHERE id = :employee_id'
        );
        $statement->execute([
            'employee_id' => $employee_id
        ]);
        $this->session->setData('user', null);
    }
}