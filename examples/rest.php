<?php
$board = require(__DIR__.'/bootstrap.php');

use Carica\Io;
use Carica\Firmata;
use Carica\Io\Network\Http;

$route = new Carica\Io\Network\Http\Route();
$route->match('/pins', new Firmata\Rest\Pins($board));
$route->match('/pins/{pin}', new Firmata\Rest\PinHandler($board));

$board
  ->activate()
  ->done(
    static function () use ($board, $route) {
      $board->queryAllPinStates();
      $server = new Carica\Io\Network\Http\Server($board->loop(), $route);
      $server->listen(8080);
    }
  )
  ->fail(
    static function ($error) {
      echo $error."\n";
    }
  );


Carica\Io\Event\Loop\Factory::run();
