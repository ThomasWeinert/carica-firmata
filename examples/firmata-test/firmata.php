<?php
$board = require(__DIR__.'/../bootstrap.php');

use Carica\Firmata;
use Carica\Io\Network\Http;

$route = new Carica\Io\Network\Http\Route();
$route->match('/pins', new Firmata\Rest\Pins($board));
$route->match('/pins/{pin}', new Firmata\Rest\PinHandler($board));
$route->startsWith('/files', new Http\Route\Directory(__DIR__));
$route->match('/', new Http\Route\File(__DIR__.'/index.html'));

echo "Start board:\n";
$board
  ->activate()
  ->done(
    static function () use ($board, $route) {
      echo "...activated.\n";
      $board->queryAllPinStates();
      echo "Start HTTP server:\n";
      $server = new Carica\Io\Network\Http\Server($board->loop(), $route);
      $server->listen(8080);
      echo "...started.\n";
    }
  )
  ->fail(
    function ($error) {
      echo $error."\n";
    }
  );


Carica\Io\Event\Loop\Factory::run();
