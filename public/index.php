<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
});

$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$app->get('/users', function ($request, $response) use ($users) {
    $queryParams = $request->getQueryParams();
    $s = $queryParams['s'];
    $userDataFilename = __DIR__ . '/../userData.json';
    $fileData = file_get_contents($userDataFilename);
    $usersData = json_decode($fileData, true);
    $params = [
        'users' => $usersData,
        's' => $s
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

$app->get('/users/new', function ($request, $response) {
    $params = [];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

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
        $usersData = json_decode($fileData, true);
        $usersData[] = $userData;
        file_put_contents($userDataFilename, json_encode($usersData));
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
});

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->run();
