<?php
$board = require('../bootstrap.php');

use Carica\Io;
use Carica\Firmata;
use Carica\Io\Network\Http;

$route = new Carica\Io\Network\Http\Route();
$route->match(
  '/rgb',
  function (Http\Request $request) use ($board) {
    if (isset($request->query['r'])) {
      $red = (int)$request->query['r'];
      $board->pins[10]->analog = ($red > 0 && $red < 256) ? $red : 0;
    }
    if (isset($request->query['g'])) {
      $green = (int)$request->query['g'];
      $board->pins[11]->analog = ($green > 0 && $green < 256) ? $green : 0;
    }
    if (isset($request->query['b'])) {
      $blue = (int)$request->query['b'];
      $board->pins[9]->analog = ($blue > 0 && $blue < 256) ? $blue : 0;
    }
    $response = $request->createResponse();
    $response->content = new Http\Response\Content\String(
      'Red: '.$board->pins[10]->analog.", ".
      'Green: '.$board->pins[11]->analog.", ".
      'Blue: '.$board->pins[9]->analog
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
    $board->pins[9]->mode = Firmata\PIN_STATE_PWM;
    $board->pins[10]->mode = Firmata\PIN_STATE_PWM;
    $board->pins[11]->mode = Firmata\PIN_STATE_PWM;
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