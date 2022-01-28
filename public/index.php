<?php

use GraphQL\Deferred;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

if(!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'wb'));

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$app->post('/', function (Request $request, Response $response, $args) {

    $authors = [];
    for ($i = 0; $i <= 100; $i++) {
        $authors[$i] = ['name' => 'Name ' . $i];
    }

    $books = [];
    for ($i = 0; $i <= 4000; $i++) {
        $books[$i] = ['title' => 'Title ' . $i, 'authorId' => rand(0, 100)];
    }

    $authorType = new ObjectType([
        'name' => 'Author',
        'fields' => [
            'name' => [
                'type' => Type::string(),
                'resolve' => function ($rootValue, $args) {
                    return $rootValue['name'];
                }
            ],
        ],
    ]);

    $bookType = new ObjectType([
        'name' => 'Book',
        'fields' => [
            'title' => [
                'type' => Type::string(),
                'resolve' => function ($rootValue, $args) {
                    return $rootValue['title'];
                }
            ],
            'author' => [
                'type' => $authorType,
                'resolve' => function ($rootValue, $args) use ($authors) {
                    return new Deferred(function() use ($authors, $rootValue) {
                        return $authors[$rootValue['authorId']];
                    });
                    //return $authors[$rootValue['authorId']];
                }
            ],
        ],
    ]);

    $queryType = new ObjectType([
        'name' => 'Query',
        'fields' => [
            'getBooks' => [
                'type' => Type::listOf($bookType),
                'resolve' => function ($rootValue, $args) use ($books) {
                    return $books;
                }
            ],
        ],
    ]);

    $schema = new Schema([
        'query' => $queryType
    ]);
    $data = json_decode($request->getBody() ?? [], true);
    $data += ['query' => null, 'variables' => null];

    $result = \GraphQL\GraphQL::executeQuery(
        $schema,
        $data['query'],
        null,
        null,
        $data['variables']
    );

    $response->withHeader('Content-Type', 'application/json');

    $response->getBody()->write(json_encode($result->toArray(), 0));

    return $response;
});

$app->run();

$memory = (int)(memory_get_peak_usage(true) / 1024 / 1024);
fwrite(STDOUT, "Used memory: " . $memory . PHP_EOL);