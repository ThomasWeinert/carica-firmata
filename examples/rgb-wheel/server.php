<?php
$board = require(__DIR__.'/../bootstrap.php');

use Carica\Io;
use Carica\Firmata;
use Carica\Io\Network\Http;

const PIN_RED = 9;
const PIN_GREEN = 10;
const PIN_BLUE = 11;

$route = new Carica\Io\Network\Http\Route();
$route->match(
  '/rgb',
  static function (Http\Request $request) use ($board) {
    if (isset($request->query['r'])) {
      $red = (int)$request->query['r'];
      $board->pins[PIN_RED]->value = ($red > 0 && $red < 256) ? $red : 0;
    }
    if (isset($request->query['g'])) {
      $green = (int)$request->query['g'];
      $board->pins[PIN_GREEN]->value = ($green > 0 && $green < 256) ? $green : 0;
    }
    if (isset($request->query['b'])) {
      $blue = (int)$request->query['b'];
      $board->pins[PIN_BLUE]->value = ($blue > 0 && $blue < 256) ? $blue : 0;
    }
    $response = $request->createResponse();
    $response->content = new Http\Response\Content\Text(
      'Red: '.$board->pins[PIN_RED]->value.', '.
      'Green: '.$board->pins[PIN_GREEN]->value.', '.
      'Blue: '.$board->pins[PIN_BLUE]->value
    );
    return $response;
  }
);
$route->startsWith('/files', new Http\Route\Directory(__DIR__));
$route->match('/', new Http\Route\File(__DIR__.'/index.html'));

$board
  ->activate()
  ->done(
    static function () use ($board, $route) {
      echo "Board activated.\n";

      $board->pins[PIN_RED]->mode = Carica\Firmata\Pin::MODE_PWM;
      $board->pins[PIN_GREEN]->mode = Carica\Firmata\Pin::MODE_PWM;
      $board->pins[PIN_BLUE]->mode = Carica\Firmata\Pin::MODE_PWM;

      echo "Start HTTP server: http://localhost:8080\n";
      $server = new Carica\Io\Network\Http\Server($route);
      $server->listen(8080);
    }
  )
  ->fail(
    static function ($error) {
      echo $error."\n";
    }
  );


Carica\Io\Event\Loop\Factory::run();
