<?php
require('../vendor/autoload.php');
Carica\Io\Loader::map(
  ['Carica\Firmata' => __DIR__.'/../src/Carica/Firmata']
);
Carica\Io\Loader::register();

use Carica\Io;

$board = new Firmata\Board(
  //new Io\Stream\Serial('COM3')
  new Io\Stream\Tcp('127.0.0.1', 5333)
);

$loop = Io\Event\Loop\Factory::get();

$board
  ->activate()
  ->done(
    function () use ($board, $loop) {
      echo "Firmata ".$board->version." active\n";

      $buttonPin = 2;
      $ledPin = 13;

      $board->pins[$buttonPin]->mode = Firmata\PIN_STATE_INPUT;
      $board->pins[$ledPin]->mode = Firmata\PIN_STATE_OUTPUT;

      $board->digitalRead(
        $buttonPin,
        function($value) use ($board, $ledPin) {
          echo ($value == Firmata\DIGITAL_HIGH) ? "Button down\n" :  "Button up\n";
          $board->pins[$ledPin]->digital = $value == Firmata\DIGITAL_HIGH;
        }
      );
    }
  )
  ->fail(
    function ($error) {
      echo $error."\n";
    }
  );


if ($board->isActive()) {
  $loop->run();
}


