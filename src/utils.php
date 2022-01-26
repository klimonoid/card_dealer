<?php

use Psr\Http\Message\ResponseInterface;

function isEmployee($session, $message): bool
{
    if ($session->getData("user") == null or $session->getData("user")["is_staff"] != true) {
        $session->setData("message", $message);
        return false;
    }
    return true;
}

function isClient($session, $message): bool
{
    if ($session->getData("user") == null or $session->getData("user")["is_staff"] != false) {
        $session->setData("message", $message);
        return false;
    }
    return true;
}

function renderPageByQuery($query, $session, $twig, $response,
                           $name_render_page, $name_form = "form", $need_one = 0): ResponseInterface
{
    if ($need_one == 1) {
        $rows = $query->fetch();
    } else {
        $rows = $query->fetchAll();
    }

    $session->setData($name_form, $rows);
    $body = $twig->render($name_render_page, [
        "user" => $session->getData("user"),
        "message" => $session->get_and_set_null("message"),
        $name_form => $session->flush($name_form)
    ]);

    var_dump($rows);

    $response->getBody()->write($body);
    return $response;
}