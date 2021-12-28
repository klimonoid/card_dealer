<?php

use App\Authorization;
use App\AuthorizationException;
use App\Database;
use App\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

//Подключаем composer
require __DIR__ . '/vendor/autoload.php';

//Указываем, откуда подгружать шаблоны
$loader = new FilesystemLoader('templates');
//Подгружаем
$twig = new Environment($loader);

//Создаём приложение
$app = AppFactory::create();
$app->addBodyParsingMiddleware(); //Для работы с POST

$session = new Session();
$sessionMiddleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use($session) {
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

//Обработчики:
//Домашняя страница
$app->get('/',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
    //Рендерим twig
    $body = $twig->render('index.twig', [
        'user' => $session->getData('user'),
    ]);

    //Передаём twig на отрисовку
    $response->getBody()->write($body);
    return $response;
});

//Отрисовка логина клиента
$app->get('/login',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
    //Рендерим twig
    $body = $twig->render('login.twig', [
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
        return $response->withHeader('Location', '/login')
            ->withStatus(302);
    }

    return $response->withHeader('Location', '/')
        ->withStatus(302);
});


//Отрисовка регистрации клиента
$app->get('/register',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
    //Рендерим twig
    $body = $twig->render('register.twig', [
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
//Завершить сессию для клиента
$app->get('/logout',
    function (ServerRequestInterface $request, ResponseInterface $response) use ($session) {
    $session->setData('user', null);
    return $response->withHeader('Location', '/')
        ->withStatus(302);
});

//Запускаем приложение
$app->run();