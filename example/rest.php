<?php
require('../vendor/autoload.php');
Carica\Io\Loader::map(
  ['Carica\Firmata' => __DIR__.'/../src/Carica/Firmata']
);
Carica\Io\Loader::register();

use Carica\Io;
use Carica\Firmata;
use Carica\Io\Network\Http;

$board = new Firmata\Board(
  new Io\Stream\Tcp('127.0.0.1', 5339)
);
$route = new Carica\Io\Network\Http\Route();
$route->match('/pins', new Firmata\Rest\Pins($board));
$route->match('/pins/{pin}', new Firmata\Rest\Pin($board));

$board
  ->activate()
  ->done(
    function () use ($board, $route) {
      $board->queryAllPinStates();
      $server = new Carica\Io\Network\Server();
      $server->events()->on(
        'connection',
        function ($stream) use ($route) {
          $request = new Carica\Io\Network\Http\Connection($stream);
          $request->events()->on(
            'request',
            function ($request) use ($route) {
              echo $request->method.' '.$request->url."\n";
              if (!($response = $route($request))) {
                $response = new Carica\Io\Network\Http\Response\Error(
                  $request, 404
                );
              }
              $response
                ->send()
                ->always(
                  function () use ($request) {
                    $request->connection()->close();
                  }
                );
            }
          );
        }
      );
      $server->listen(8080);
    }
  )
  ->fail(
    function ($error) {
      echo $error."\n";
    }
  );


Carica\Io\Event\Loop\Factory::run();