<?php
$board = require(__DIR__.'/bootstrap.php');

use Carica\Io;
use Carica\Firmata;
use Carica\Io\Network\Http;

$route = new Carica\Io\Network\Http\Route();
$route->match('/pins', new Firmata\Rest\Pins($board));
$route->match('/pins/{pin}', new Firmata\Rest\Pin($board));

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