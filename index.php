<?php

use App\applicationManagement\ApplicationException;
use App\applicationManagement\ApplicationManagement;
use App\cardsAndAccounts\CardException;
use App\cardsAndAccounts\CardsAndAccountsManagement;
use App\contracts\ContractException;
use App\contracts\ContractManagement;
use App\users\Authorization;
use App\users\AuthorizationException;
use App\Database;
use App\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use App\users\Editor;

//Подключаем composer
require __DIR__ . '/vendor/autoload.php';
require_once "/Users/klim/PhpstormProjects/card_dealer/src/general/utils.php";

//Указываем, откуда подгружать шаблоны
$loader = new FilesystemLoader('templates');
//Подгружаем
$twig = new Environment($loader);

//Создаём приложение
$app = AppFactory::create();
$app->addBodyParsingMiddleware(); //Для работы с POST

// Для отслеживания ошибок
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$session = new Session();
$sessionMiddleware = function (ServerRequestInterface $request,
                               RequestHandlerInterface $handler) use($session) {
    $session->start();
    $response = $handler->handle($request);

    $session->save();
    return $response;
};

$app->add($sessionMiddleware);

//Работа с БД
$config = include_once 'config/database.php';
$dsn = $config['dsn'];
$username = $config['username'];
$password = $config['password'];

$database = new Database($dsn, $username, $password);

$authorization = new Authorization($database, $session);
$editor = new Editor($database, $session);
$applications = new ApplicationManagement($database, $session);
$contracts = new ContractManagement($database, $session);
$cards_n_accounts = new CardsAndAccountsManagement($database, $session);

//Обработчики:
//Домашняя страница с логином и редакированием пользователя!!!!!
$app->get('/',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {

        //Рендерим twig
        $body = $twig->render('authorization/index.twig', [
            'user' => $session->getData('user'),
            'message' => $session->flush('message'),
            'form' => $session->flush('form'),
        ]);

        //Передаём twig на отрисовку
        $response->getBody()->write($body);
        return $response;
    });
//Залогинить клиента
$app->post('/login-post',
    function (ServerRequestInterface $request, ResponseInterface $response) use($authorization, $session) {
        $params = (array) $request->getParsedBody();

        try {
            $authorization->login($params['phone'], $params['password']);
        } catch (AuthorizationException $exception) {
            $session->setData('message', $exception->getMessage());
            $session->setData('form', $params);
            return $response->withHeader('Location', '/')
                ->withStatus(302);
        }

        return $response->withHeader('Location', '/')
            ->withStatus(302);
    });
//Отрисовка регистрации клиента
$app->get('/register',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
        //Рендерим twig
        $body = $twig->render('authorization/register.twig', [
            'message' => $session->flush('message'),
            'form' => $session->flush('form'),
        ]);

        //Передаём twig на отрисовку
        $response->getBody()->write($body);
        return $response;
    });
//Зарегистрировать клиента
$app->post('/register-post',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($authorization, $session) {
        $params = (array)$request->getParsedBody();
        try {
            $authorization->register($params);
        } catch (AuthorizationException $exception) {
            $session->setData('message', $exception->getMessage());
            $session->setData('form', $params);
            return $response->withHeader('Location', '/register')
                ->withStatus(302);
        }

        return $response->withHeader('Location', '/')
            ->withStatus(302);
    });
//Редактирование клиента
$app->post('/edit-post',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($session, $editor) {
        $params = (array)$request->getParsedBody();
        try {
            $editor->edit_client($params, $session->getData('user')['user_id']);
        } catch (AuthorizationException $exception) {
            $session->setData('message', $exception->getMessage());
            $session->setData('form', $params);
            return $response->withHeader('Location', '/')
                ->withStatus(302);
        }

        return $response->withHeader('Location', '/')
            ->withStatus(302);
    });
//Удаление клиента
$app->get('/delete-client',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($session, $editor) {
        $editor->delete_client($session->getData("user")["user_id"]);
        return $response->withHeader('Location', '/')
            ->withStatus(302);
    });


//Сотрудник
//Отрисовка логина сотрудника
$app->get('/login-employee',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
        //Рендерим twig
        $body = $twig->render('authorization/login-employee.twig', [
            'message' => $session->flush('message'),
            'form' => $session->flush('form'),
        ]);

        //Передаём twig на отрисовку
        $response->getBody()->write($body);
        return $response;
    });
//Залогинить сотрудника
$app->post('/login-employee-post',
    function (ServerRequestInterface $request, ResponseInterface $response) use($authorization, $session) {
        $params = (array) $request->getParsedBody();

        try {
            $authorization->login_employee($params['phone'], $params['password']);
        } catch (AuthorizationException $exception) {
            $session->setData('message', $exception->getMessage());
            $session->setData('form', $params);
            return $response->withHeader('Location', '/login-employee')
                ->withStatus(302);
        }

        return $response->withHeader('Location', '/')
            ->withStatus(302);
    });
//Отрисовка регистрации сотрудника
$app->get('/register-employee',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
        //Рендерим twig
        $body = $twig->render('authorization/register-employee.twig', [
            'message' => $session->flush('message'),
            'form' => $session->flush('form'),
        ]);

        //Передаём twig на отрисовку
        $response->getBody()->write($body);
        return $response;
    });
//Зарегистрировать сотрудника
$app->post('/register-employee-post',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($authorization, $session) {
        $params = (array)$request->getParsedBody();
        try {
            $authorization->register_employee($params);
        } catch (AuthorizationException $exception) {
            $session->setData('message', $exception->getMessage());
            $session->setData('form', $params);
            return $response->withHeader('Location', '/register-employee')
                ->withStatus(302);
        }

        return $response->withHeader('Location', '/login-employee')
            ->withStatus(302);
    });
//Редактирование сотрудника
$app->post('/edit-employee-post',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($session, $editor) {
        $params = (array)$request->getParsedBody();
        try {
            $editor->edit_employee($params, $session->getData('user')['user_id']);
        } catch (AuthorizationException $exception) {
            $session->setData('message', $exception->getMessage());
            $session->setData('form', $params);
            return $response->withHeader('Location', '/')
                ->withStatus(302);
        }

        return $response->withHeader('Location', '/')
            ->withStatus(302);
    });
//Удаление сотрудника
$app->get('/delete-employee',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($session, $editor) {
        $editor->delete_employee($session->getData("user")["user_id"]);
        return $response->withHeader('Location', '/login-employee')
            ->withStatus(302);
    });

//Заявления
//Мои заявления
$app->get("/my-applications",
    function (ServerRequestInterface $request,
              ResponseInterface $response) use ($database, $session, $twig) {
        if (!isClient($session,
            "Для доступа к этой информации необходимо зайти в система в качестве пользователя")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $query = $database->getConnection()->query(
            "SELECT a.id, date_of_submission, status, comment
                       FROM application a
                       WHERE a.applicant_id = '".$session->getData("user")['user_id']."'
                       ORDER BY a.date_of_submission"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "applications/my-applications.twig", "applications");
    });
//Создать заявление
$app->get('/create-application',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($applications, $session) {
        if (!isClient($session,
            "Для этого действия необходимо в качестве пользователя")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        try {
            $applications->create_application($session->getData("user")['user_id']);
        } catch (ApplicationException $exception) {
            $session->setData('message', $exception->getMessage());
            return $response->withHeader('Location', '/my-applications')
                ->withStatus(302);
        }

        return $response->withHeader('Location', '/my-applications')
            ->withStatus(302);
    });
//Все необработанные заявления
$app->get("/applications",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $twig) {
        if (!isEmployee($session,
            "Для доступа к этой информации необходимо быть сотрудником")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $query = $database->getConnection()->query(
            "SELECT a.id, date_of_submission, status, comment,
                       c.surname, c.given_name, c.patronymic
                       FROM application a JOIN client c on a.applicant_id = c.id
                       WHERE a.status = 'accepted'
                       ORDER BY a.date_of_submission LIMIT 10"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "applications/applications.twig", "applications");
    });
//Рассмотреть заявление подробнее
$app->get('/applications/{application_id}',
    function (ServerRequestInterface $request,
              ResponseInterface $response, $args) use ($database, $session, $twig) {
        if (!isEmployee($session,
            "Для доступа к этой информации необходимо быть сотрудником")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $query = $database->getConnection()->query(
            "SELECT a.id, a.date_of_submission, c.given_name, c.surname, c.patronymic, c.age,
                                c.series, c.number, c.phone
                       FROM application a JOIN client c on a.applicant_id = c.id
                       WHERE a.id = {$args['application_id']}"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "applications/application_details.twig", "application", 1);
    });
//Обработать заявление
$app->post('/edit-application/{application_id}',
    function (ServerRequestInterface $request,
              ResponseInterface $response, $args) use ($applications, $session, $contracts) {
        $params = (array)$request->getParsedBody();
        try {
            $result = $applications->edit_application($params, $args['application_id']);
        } catch (ApplicationException $exception) {
            $session->setData('message', $exception->getMessage());
            $session->setData('form', $params);
            return $response->withHeader('Location', "/applications/{$args['application_id']}")
                ->withStatus(302);
        }
        if ($result == true) {
            $contracts->create_contract($args['application_id']);
        }

        return $response->withHeader('Location', '/applications')
            ->withStatus(302);
    });


//Договора
//Мои Договора
$app->get("/my-contracts",
    function (ServerRequestInterface $request,
              ResponseInterface $response) use ($database, $session, $twig) {
        if (!isClient($session,
            "Для доступа к этой информации необходимо зайти в система в качестве пользователя")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $query = $database->getConnection()->query(
            "SELECT number, client_id, application_id, status, comment
                       FROM contract
                       WHERE client_id = '".$session->getData("user")['user_id']."'
                       ORDER BY number DESC"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "contracts/my-contracts.twig", "contracts");
    });
//Страница поиска договора по данным клиента
$app->get('/contract-validation',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
        if (!isEmployee($session,
            "Для доступа к этой информации необходимо быть сотрудником")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $hueta = $session->getData('ready_to_sign_the_contract');
        var_dump($hueta);
        //Рендерим twig
        $body = $twig->render('contracts/validation.twig', [
            'user' => $session->getData('user'),
            'message' => $session->flush('message'),
            'form' => $session->flush('form'),
        ]);
        //Передаём twig на отрисовку
        $response->getBody()->write($body);
        return $response;
    });
//Валидируем пользователя, и ищем его договор
$app->post('/find-contract',
    function (ServerRequestInterface $request,
              ResponseInterface $response) use($contracts, $database, $session) {
        if (!isEmployee($session,
            "Для доступа к этой информации необходимо быть сотрудником")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }

        $params = (array) $request->getParsedBody();

        try {
            $client_id = $contracts->validate_user($params['phone'], $params['password']);
        } catch (AuthorizationException $exception) {
            $session->setData('message', $exception->getMessage());
            $session->setData('form', $params);
            return $response->withHeader('Location', '/contract-validation')
                ->withStatus(302);
        }

        return $response->withHeader('Location', '/contracts/' . $client_id)
            ->withStatus(302);
    });
// Договор для подписания
$app->get("/contracts/{client_id}",
    function (ServerRequestInterface $request,
              ResponseInterface $response, $args) use ($database, $session, $twig) {
        if (!isEmployee($session,
            "Для доступа к этой информации необходимо зайти в система в качестве пользователя")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (!readyToSignTheContract($session,
        "Клиент обязан подтвердить свою личность перед подписанием договора")) {
            return $response->withHeader("Location", "/contract-validation")->withStatus(302);
        }

        $query = $database->getConnection()->query(
        "SELECT cont.id, cont.number as num, c.surname, c.given_name, c.patronymic, c.series, c.number, c.age
                        FROM contract cont JOIN client c on c.id = cont.client_id
                        WHERE client_id = {$args['client_id']}
                        AND status = 'ready'"
        );

        $rows = $query->fetch();

        if ($rows == false) {
            $session->setData("message", "Этот пользователь не имеет договоров, готовых к подписанию");
            $session->flush('ready_to_sign_the_contract');
            return $response->withHeader("Location", "/contract-validation")
                ->withStatus(302);
        }

        $session->setData('contract', $rows);
        $body = $twig->render('contracts/contract_details.twig', [
            "user" => $session->getData("user"),
            "message" => $session->flush("message"),
            'contract' => $session->flush('contract')
        ]);

        var_dump($rows);

        $response->getBody()->write($body);
        return $response;
    });
// Все договоры со статусом preparing
$app->get("/contracts",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $twig) {
        if (!isEmployee($session,
            "Для доступа к этой информации необходимо быть сотрудником")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $query = $database->getConnection()->query(
            "SELECT cont.id, cont.number, status,
                       c.surname, c.given_name, c.patronymic
                       FROM contract cont JOIN client c on cont.client_id = c.id
                       WHERE cont.status = 'preparing'
                       ORDER BY cont.number LIMIT 10"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "contracts/contracts.twig", "contracts");
    });
// Рассмотреть договор подробнее для обработки
$app->get('/process-contract/{contract_id}',
    function (ServerRequestInterface $request,
              ResponseInterface $response, $args) use ($database, $session, $twig) {
        if (!isEmployee($session,
            "Для доступа к этой информации необходимо быть сотрудником")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $query = $database->getConnection()->query(
            "SELECT cont.id, cont.number as num, c.given_name, c.surname, c.patronymic, c.age,
                                c.series, c.number
                       FROM contract cont JOIN client c on cont.client_id = c.id
                       WHERE cont.id = {$args['contract_id']}"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "contracts/make_contract.twig", "contract", 1);
    });
//Обработать создание договора
$app->post('/process-contract-post/{contract_id}',
    function (ServerRequestInterface $request,
              ResponseInterface $response, $args) use ($contracts, $session) {
        try {
            $contracts->fromPreparingToReady($args['contract_id']);
        } catch (ApplicationException $exception) {
            $session->setData('message', $exception->getMessage());
            return $response->withHeader('Location', "/process-contract/{contract_id}")
                ->withStatus(302);
        }

        return $response->withHeader('Location', '/contracts')
            ->withStatus(302);
    });
//Обработать подписание договора
$app->post('/contract-signing-post/{contract_id}',
    function (ServerRequestInterface $request,
              ResponseInterface $response, $args) use ($cards_n_accounts, $database, $contracts, $session) {
        if (!isEmployee($session,
            "Для доступа к этой информации необходимо быть сотрудником")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (!readyToSignTheContract($session,
            "Клиент обязан подтвердить свою личность перед подписанием договора")) {
            return $response->withHeader(
                "Location",
                "/contract-validation"
            )->withStatus(302);
        }
        $params = (array)$request->getParsedBody();
        try {
            $result = $contracts->edit_contract(
                $params,
                $args['contract_id'],
                $session->getData("user")['user_id']
            );
        } catch (ContractException $exception) {
            $session->setData('message', $exception->getMessage());
            $statement = $database->getConnection()->prepare(
                'SELECT client.id FROM client JOIN contract on contract.client_id = client.id
                            WHERE contract.id = :contract_id'
            );
            $statement->execute([
                'contract_id' => $args['contract_id']
            ]);
            $client_id = $statement->fetch()['id'];
            return $response->withHeader('Location', "/contracts/" . $client_id)
                ->withStatus(302);
        }

        $session->flush('ready_to_sign_the_contract');

        if ($result == true) {
            $cards_n_accounts->create_card_and_account($args['contract_id']);
        }

        return $response->withHeader('Location', '/')
            ->withStatus(302);
    });


//Cчета
//Мои Счета
$app->get("/my-accounts",
    function (ServerRequestInterface $request,
              ResponseInterface $response) use ($database, $session, $twig) {
        if (!isClient($session,
            "Для доступа к этой информации необходимо зайти в система в качестве пользователя")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $query = $database->getConnection()->query(
            "SELECT number, correspondent_account, bic, inn, kpp, open_date, balance, status
                       FROM account
                       WHERE client_id = '".$session->getData("user")['user_id']."'
                       ORDER BY number DESC"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "accounts/my-accounts.twig", "accounts");
    });


//Карты
//Мои Карты
$app->get("/my-cards",
    function (ServerRequestInterface $request,
              ResponseInterface $response) use ($database, $session, $twig) {
        if (!isClient($session,
            "Для доступа к этой информации необходимо зайти в система в качестве пользователя")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $query = $database->getConnection()->query(
            "SELECT number, pin, cvv, service_end_date, status
                       FROM card
                       WHERE client_id = '".$session->getData("user")['user_id']."'
                       ORDER BY number DESC"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "cards/my-cards.twig", "cards");
    });
// Все карты со статусом preparing
$app->get("/cards",
    function (ServerRequestInterface $request, ResponseInterface $response) use ($database, $session, $twig) {
        if (!isEmployee($session,
            "Для доступа к этой информации необходимо быть сотрудником")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $query = $database->getConnection()->query(
            "SELECT card.id, card.number,
                       c.surname, c.given_name, c.patronymic
                       FROM card JOIN client c on card.client_id = c.id
                       WHERE card.status = 'preparing'
                       ORDER BY card.id LIMIT 10"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "cards/cards.twig", "cards");
    });
// Рассмотреть карту подробнее для обработки
$app->get('/cards/{card_id}',
    function (ServerRequestInterface $request,
              ResponseInterface $response, $args) use ($database, $session, $twig) {
        if (!isEmployee($session,
            "Для доступа к этой информации необходимо быть сотрудником")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $query = $database->getConnection()->query(
            "SELECT card.id, card.number, pin, cvv, service_end_date, c.surname, c.given_name
                       FROM card JOIN client c on card.client_id = c.id
                       WHERE card.id = {$args['card_id']}"
        );
        return renderPageByQuery($query, $session, $twig, $response,
            "cards/card-details.twig", "card", 1);
    });
//Обработать приезд карты
$app->post('/process-card-post/{card_id}',
    function (ServerRequestInterface $request,
              ResponseInterface $response, $args) use ($cards_n_accounts, $session) {
        try {
            $cards_n_accounts->fromPreparingToReady($args['card_id']);
        } catch (ApplicationException $exception) {
            $session->setData('message', $exception->getMessage());
            return $response->withHeader('Location', "/process-contract/{contract_id}")
                ->withStatus(302);
        }

        return $response->withHeader('Location', '/cards')
            ->withStatus(302);
    });
//Страница поиска карты по данным клиента
$app->get('/card-validation',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
        if (!isEmployee($session,
            "Для доступа к этой информации необходимо быть сотрудником")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        $hueta = $session->getData('ready_to_get_card');
        var_dump($hueta);
        //Рендерим twig
        $body = $twig->render('cards/validation.twig', [
            'user' => $session->getData('user'),
            'message' => $session->flush('message'),
            'form' => $session->flush('form'),
        ]);
        //Передаём twig на отрисовку
        $response->getBody()->write($body);
        return $response;
    });
//Валидируем пользователя, и ищем его карту
$app->post('/find-card',
    function (ServerRequestInterface $request,
              ResponseInterface $response) use($cards_n_accounts, $database, $session) {
        if (!isEmployee($session,
            "Для доступа к этой информации необходимо быть сотрудником")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }

        $params = (array) $request->getParsedBody();

        try {
            $client_id = $cards_n_accounts->validate_user($params['phone'], $params['password']);
        } catch (AuthorizationException $exception) {
            $session->setData('message', $exception->getMessage());
            $session->setData('form', $params);
            return $response->withHeader('Location', '/card-validation')
                ->withStatus(302);
        }

        return $response->withHeader('Location', '/get-card/' . $client_id)
            ->withStatus(302);
    });
// Карта для выдачи
$app->get("/get-card/{client_id}",
    function (ServerRequestInterface $request,
              ResponseInterface $response, $args) use ($database, $session, $twig) {
        if (!isEmployee($session,
            "Для доступа к этой информации необходимо зайти в система в качестве пользователя")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (!readyToGetCard($session,
            "Клиент обязан подтвердить свою личность перед получением карты")) {
            return $response->withHeader("Location", "/card-validation")->withStatus(302);
        }

        $statement = $database->getConnection()->prepare(
            "SELECT card.id, card.number as num, cvv, service_end_date,
                                c.surname,c.given_name, c.patronymic, c.series, c.number, c.age
                        FROM card JOIN client c on card.client_id = c.id
                        WHERE (client_id = :client_id  AND status = :ready)"
        );
        $statement->execute([
            'client_id' => $args['client_id'],
            'ready' => 'ready'
        ]);
        $rows = $statement->fetch();

        if ($rows == false) {
            $session->setData("message", "Этот пользователь не имеет карт, готовых к выдаче");
            $session->flush('ready_to_get_card');
            return $response->withHeader("Location", "/card-validation")->withStatus(302);
        }

        $session->setData('card', $rows);
        $body = $twig->render('cards/give_card.twig', [
            "user" => $session->getData("user"),
            "message" => $session->flush("message"),
            'card' => $session->flush('card')
        ]);

        var_dump($rows);


        $response->getBody()->write($body);
        return $response;
    });
//Обработать выдачу карты
$app->post('/card-giving-post/{card_id}',
    function (ServerRequestInterface $request,
              ResponseInterface $response, $args) use ($cards_n_accounts, $database, $session) {
        if (!isEmployee($session,
            "Для доступа к этой информации необходимо быть сотрудником")) {
            return $response->withHeader("Location", "/")->withStatus(302);
        }
        if (!readyToGetCard($session,
            "Клиент обязан подтвердить свою личность перед подписанием договора")) {
            return $response->withHeader(
                "Location",
                "/card-validation"
            )->withStatus(302);
        }
        $params = (array)$request->getParsedBody();
        try {
            $cards_n_accounts->edit_card_and_account(
                $params,
                $args['card_id']
            );
        } catch (CardException $exception) {
            $session->setData('message', $exception->getMessage());
            $statement = $database->getConnection()->prepare(
                'SELECT client.id FROM client JOIN card on card.client_id = client.id
                            WHERE card.id = :card_id'
            );
            $statement->execute([
                'card_id' => $args['card_id']
            ]);
            $client_id = $statement->fetch()['id'];
            return $response->withHeader('Location', "/get-card/" . $client_id)
                ->withStatus(302);
        }

        $session->flush('ready_to_get_card');

        return $response->withHeader('Location', '/')
            ->withStatus(302);
    });


//Завершить сессию
$app->get('/logout',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($session) {
        $session->setData('user', null);
        return $response->withHeader('Location', '/')
            ->withStatus(302);
    });

//Запускаем приложение
$app->run();