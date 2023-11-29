<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/db.php';
require '../helpers/authentication.php';
require '../helpers/error_handling.php';


$app = AppFactory::create();

$app->get('/', function (Request $request, Response $response, array $args) {
    
    $response->getBody()->write("Hello, Bunq");
    return $response;
});

#Users
require "../routes/users.php";

#Groups
require "../routes/chat_groups.php";

#Messages
require "../routes/messages.php";



$app->run();
