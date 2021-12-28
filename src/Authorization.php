<?php

declare(strict_types=1);

namespace App;

class Authorization
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
     * @param array $data
     * @return bool
     * @throws AuthorizationException
     */
    public function register(array $data): bool
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
        $user = $statement->fetch();
        if(!empty($user)) {
            throw new AuthorizationException('Пользователь с таким номером телефона уже зарегистрирован');
        }

        $statement = $this->database->getConnection()->prepare(
            'INSERT INTO client (surname, given_name, patronymic, age, series, number, phone, password) VALUES (:surname, :given_name, :patronymic, :age, :series, :number, :phone, :password)'
        );
        $statement->execute([
            'surname' => $data['surname'],
            'given_name' => $data['given_name'],
            'patronymic' => $data['patronymic'],
            'age' => $data['age'],
            'series' => $data['series'],
            'number' => $data['number'],
            'phone' => $data['phone'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT)
        ]);
        return true;
    }

    /**
     * @param string $phone
     * @param string $password
     * @return bool
     * @throws AuthorizationException
     */
    public function login(string $phone, string $password): bool
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
        $user = $statement->fetch();
        if(empty($user)) {
            throw new AuthorizationException('Пользователя с таким номером телефона не существует');
        }
        if(password_verify($password, $user['password'])) {
            $this->session->setData('user', [
                'user_id' => $user['id'],
                'given_name' => $user['given_name'],
                'phone' => $user['phone'],
            ]);
            return true;
        }

        throw new AuthorizationException('Неверные номер телефона или пароль');
    }
}