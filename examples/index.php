<?php
include_once "../vendor/autoload.php";

use WoohooLabs\Yin\Examples\Book\Action\CreateBookAction;
use WoohooLabs\Yin\Examples\Book\Action\GetAuthorsOfBookAction;
use WoohooLabs\Yin\Examples\Book\Action\GetBookAction;
use WoohooLabs\Yin\Examples\Book\Action\GetBookRelationshipsAction;
use WoohooLabs\Yin\Examples\Book\Action\UpdateBookAction;
use WoohooLabs\Yin\Examples\User\Action\GetUserAction;
use WoohooLabs\Yin\Examples\User\Action\GetUserRelationshipsAction;
use WoohooLabs\Yin\Examples\User\Action\GetUsersAction;
use WoohooLabs\Yin\JsonApi\Exception\ExceptionFactory;
use WoohooLabs\Yin\JsonApi\JsonApi;
use WoohooLabs\Yin\JsonApi\Request\Request;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequestFactory;

// Defining routes
$routes = [
    "GET /books/{id}" => function (Request $request, $matches) {
        return $request
            ->withAttribute("action", GetBookAction::class)
            ->withAttribute("id", $matches[1]);
    },
    "GET /books/{id}/relationships/{rel}" => function (Request $request, $matches) {
        return $request
            ->withAttribute("action", GetBookRelationshipsAction::class)
            ->withAttribute("id", $matches[1])
            ->withAttribute("rel", $matches[2]);
    },
    "GET /books/{id}/authors" => function (Request $request, $matches) {
        return $request
            ->withAttribute("action", GetAuthorsOfBookAction::class)
            ->withAttribute("book_id", $matches[1]);
    },
    "POST /books" => function (Request $request) {
        return $request
            ->withAttribute("action", CreateBookAction::class);
    },
    "PATCH /books/{id}" => function (Request $request, $matches) {
        return $request
            ->withAttribute("action", UpdateBookAction::class)
            ->withAttribute("id", $matches[1]);
    },

    "GET /users" => function (Request $request) {
        return $request
            ->withAttribute("action", GetUsersAction::class);
    },
    "GET /users/{id}" => function (Request $request, $matches) {
        return $request
            ->withAttribute("action", GetUserAction::class)
            ->withAttribute("id", $matches[1]);
    },
    "GET /users/{id}/relationships/{rel}" => function (Request $request, $matches) {
        return $request
            ->withAttribute("action", GetUserRelationshipsAction::class)
            ->withAttribute("id", $matches[1])
            ->withAttribute("rel", $matches[2]);
    },
];

// Find the current route
$request = new Request(ServerRequestFactory::fromGlobals());
$request = findRoute($request, $routes);

// Invoking the current action
$jsonApi = new JsonApi($request, new Response(), new ExceptionFactory());
$action = $request->getAttribute("action");
$response = call_user_func(new $action(), $jsonApi);

// Emitting the response
$emitter = new \Zend\Diactoros\Response\SapiEmitter();
$emitter->emit($response);

/**
 * @param Request $request
 * @param array $routes
 * @return Request
 */
function findRoute(Request $request, array $routes)
{
    $queryParams = $request->getQueryParams();
    if (isset($queryParams["path"]) === false) {
        die("You must provide the 'path' query parameter!");
    }

    $method = $request->getMethod();
    $path = $queryParams["path"];
    $requestLine = $method . " " . $path;

    foreach ($routes as $pattern => $route) {
        $matches = [];
        $pattern = str_replace("{id}", "([A-Za-z0-9-]+)", $pattern);
        $pattern = str_replace("{rel}", "([A-Za-z0-9-]+)", $pattern);
        if (preg_match("#^$pattern/{0,1}$#", $requestLine, $matches) === 1) {
            return $route($request, $matches);
        }
    }

    die("Resource not found!");
}
