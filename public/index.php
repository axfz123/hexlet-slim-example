<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;

use App\UserRepository;
use App\UserValidator;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});
$container->set('repo', function () {
    return new UserRepository();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
})->setName('main');

$app->get('/users', function ($request, $response) use ($router) {
    $queryParams = $request->getQueryParams();
    $s = $queryParams['s'];
    $usersData = $this->get('repo')->all();
    $usersUrl = $router->urlFor('users');
    $newUserUrl = $router->urlFor('newuser');
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

$app->post('/users', function ($request, $response) use ($router) {
    $postParams = $request->getParsedBody();
    $userData = $postParams['user'];
    $validator = new UserValidator();
    $errors = $validator->validate($userData);
    if (count($errors) === 0) {
        $this->get('repo')->save($userData);
        $this->get('flash')->addMessage('success', 'User was added successfully');
        return $response->withHeader('Location', $router->urlFor('users'))
            ->withStatus(302);
    }
    $params = [
        'userData' => $userData,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params)->withStatus(422);
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    $response->getBody()->write("Course id: {$id}");
    return $response;
})->setName('course');

$app->get('/users/{id}', function ($request, $response, $args) use ($router) {
    $id = $args['id'];
    $isInt = (string) (int) $id === (string) $id;
    if (!$isInt) {
        return $response->withStatus(404);
    }
    $messages = $this->get('flash')->getMessages();
    $userData = $this->get('repo')->find($id);
    if (isset($userData)) {
        $params = [
            'flash' => $messages,
            'user' => $userData,
            'userUrl' => $request->getUri()->getPath()
        ];
        return $this->get('renderer')->render($response, 'users/show.phtml', $params);
    }
    return $response->withStatus(404);
})->setName('user');

$app->get('/users/{id}/edit', function ($request, $response, $args) use ($router) {
    $id = $args['id'];
    $isInt = (string) (int) $id === (string) $id;
    if (!$isInt) {
        return $response->withStatus(404);
    }
    $userData = $this->get('repo')->find($id);
    if (isset($userData)) {
        $params = [
            'user' => $userData,
            'userUrl' => $request->getUri()->getPath()
        ];
        return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
    }

    return $response->withStatus(404);
})->setName('editUser');

$app->patch('/users/{id}', function ($request, $response, array $args) use ($router)  {
    $id = $args['id'];
    $userData = $this->get('repo')->find($id);
    $requestData = $request->getParsedBody();
    $formData = $requestData['user'];

    $validator = new UserValidator();
    $errors = $validator->validate($formData);

    if (count($errors) === 0) {
        $userData['nickname'] = $formData['nickname'];
        $userData['email'] = $formData['email'];

        $this->get('flash')->addMessage('success', 'User has been updated');
        $this->get('repo')->update($userData);
        $url = $router->urlFor('user', ['id' => $userData['id']]);
        return $response->withHeader('Location', $url)
            ->withStatus(302);
    }

    $params = [
        'user' => $userData,
        'errors' => $errors
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});

$app->get('/users/{id}/delete', function ($request, $response, $args) use ($router) {
    $id = $args['id'];
    $isInt = (string) (int) $id === (string) $id;
    if (!$isInt) {
        return $response->withStatus(404);
    }

    $userData['id'] = $id;

    $params = [
        'user' => $userData
    ];
    return $this->get('renderer')->render($response, 'users/delete.phtml', $params);
});

$app->delete('/users/{id}', function ($request, $response, array $args) use ($router)  {
    $id = $args['id'];
    $userData = $this->get('repo')->find($id);
    if (empty($userData)) {
        return $response->withStatus(404);
    }
    $this->get('repo')->delete($id);
    $this->get('flash')->addMessage('success', 'User has been deleted');
    $url = $router->urlFor('users');
    return $response->withHeader('Location', $url)
        ->withStatus(302);
});

$app->run();
