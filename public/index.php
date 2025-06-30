<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
})->setName('main');

$app->get('/users', function ($request, $response) use ($app) {
    $queryParams = $request->getQueryParams();
    $s = $queryParams['s'];
    $userDataFilename = __DIR__ . '/../userData.json';
    $fileData = file_get_contents($userDataFilename);
    $usersData = json_decode($fileData, true);
    $usersUrl = $app->getRouteCollector()->getRouteParser()->urlFor('users');
    $newUserUrl = $app->getRouteCollector()->getRouteParser()->urlFor('newuser');
    $messages = $this->get('flash')->getMessages();
    $params = [
        'users' => $usersData,
        'usersUrl' => $usersUrl,
        'newUserUrl' => $newUserUrl,
        'flash' => $messages,
        's' => $s
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) {
    $params = [];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('newuser');


$app->post('/users', function ($request, $response) {
    $postParams = $request->getParsedBody();
    $nickname = $postParams['user']['nickname'];
    $email = $postParams['user']['email'];
    $userData = [
        'nickname' => $nickname,
        'email' => $email
    ];
    if ($nickname && $email) {
        $userDataFilename = __DIR__ . '/../userData.json';
        $fileData = file_get_contents($userDataFilename);
        $usersData = json_decode($fileData, true) ?? [];
        $id = (array_key_last($usersData) ?? 0) + 1;
        $userData['id'] = $id;
        $usersData[$id] = $userData;
        file_put_contents($userDataFilename, json_encode($usersData, JSON_FORCE_OBJECT));

        $this->get('flash')->addMessage('success', 'User was added successfully');
        return $response->withHeader('Location', '/users')
            ->withStatus(302);
    } else {
        $params = [
            'nickname' => $nickname,
            'email' => $email
        ];
        return $this->get('renderer')->render($response, 'users/new.phtml', $params);
    }
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    $response->getBody()->write("Course id: {$id}");
    return $response;
})->setName('course');

$app->get('/users/{id}', function ($request, $response, $args) {
    $userDataFilename = __DIR__ . '/../userData.json';
    $fileData = file_get_contents($userDataFilename);
    $usersData = json_decode($fileData, true);
    $id = $args['id'];
    if (array_key_exists($id, $usersData)) {
        $params = [
            'user' => $usersData[$id]
        ];
        return $this->get('renderer')->render($response, 'users/show.phtml', $params);
    }
    return $response->withStatus(404);
})->setName('user');

$app->run();
