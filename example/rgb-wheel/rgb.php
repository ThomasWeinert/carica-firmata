<?php
$board = require(__DIR__.'/../bootstrap.php');

use Carica\Io;
use Carica\Firmata;
use Carica\Io\Network\Http;

$route = new Carica\Io\Network\Http\Route();
$route->match(
  '/rgb',
  function (Http\Request $request) use ($board) {
    if (isset($request->query['r'])) {
      $red = (int)$request->query['r'];
      $board->pins[10]->value = ($red > 0 && $red < 256) ? $red : 0;
    }
    if (isset($request->query['g'])) {
      $green = (int)$request->query['g'];
      $board->pins[11]->value = ($green > 0 && $green < 256) ? $green : 0;
    }
    if (isset($request->query['b'])) {
      $blue = (int)$request->query['b'];
      $board->pins[9]->value = ($blue > 0 && $blue < 256) ? $blue : 0;
    }
    $response = $request->createResponse();
    $response->content = new Http\Response\Content\String(
      'Red: '.$board->pins[10]->value.", ".
      'Green: '.$board->pins[11]->value.", ".
      'Blue: '.$board->pins[9]->value
    );
    return $response;
  }
);
$route->startsWith('/files', new Http\Route\File(__DIR__));
$route->match(
  '/',
  function ($request) {
    $response = $request->createResponse();
    $response->content = new Http\Response\Content\File(
      __DIR__.'/index.html', 'text/html; charset=utf-8'
    );
    return $response;
  }
);

$board
  ->activate()
  ->done(
    function () use ($board, $route) {
      $board->pins[9]->mode = Carica\Firmata\Board::PIN_STATE_PWM;
      $board->pins[10]->mode = Carica\Firmata\Board::PIN_STATE_PWM;
      $board->pins[11]->mode = Carica\Firmata\Board::PIN_STATE_PWM;
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