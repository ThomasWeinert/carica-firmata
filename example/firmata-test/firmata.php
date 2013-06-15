<?php
$board = require(__DIR__.'/../bootstrap.php');

use Carica\Io;
use Carica\Firmata;
use Carica\Io\Network\Http;

$route = new Carica\Io\Network\Http\Route();
$route->match('/pins', new Firmata\Rest\Pins($board));
$route->match('/pins/{pin}', new Firmata\Rest\Pin($board));
$route->startsWith('/files', new Http\Route\File(__DIR__));
$route->match(
  '/',
  function ($request) {
    $response = $request->createResponse();
    $response->content = new Http\Response\Content\File(
      __DIR__.'/index.html', 'text/html', 'utf-8'
    );
    return $response;
  }
);

$board
  ->activate()
  ->done(
    function () use ($board, $route) {
      $board->queryAllPinStates();
      $server = new Carica\Io\Network\Http\Server($route);
      $server->listen(8080);
    }
  )
  ->fail(
    function ($error) {
      echo $error."\n";
    }
  );


Carica\Io\Event\Loop\Factory::run();