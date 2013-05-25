<?php
require('../vendor/autoload.php');
Carica\Io\Loader::map(
  ['Carica\Firmata' => __DIR__.'/../src/Carica/Firmata']
);
Carica\Io\Loader::register();

use Carica\Io;
use Carica\Firmata;

$board = new Firmata\Board(
  //new Io\Stream\Serial('COM7')
  new Io\Stream\Tcp('127.0.0.1', 5339)
);

$loop = Io\Event\Loop\Factory::get();


$board
  ->activate()
  ->done(
    function () use ($board, $loop) {
      echo "Firmata ".$board->version." active\n";

      $led = 3;
      $board->pinMode($led, Firmata\PIN_STATE_OUTPUT);
      echo "PIN: $led\n";

      $loop->setInterval(
        function () use ($board, $led) {
          static $ledOn = TRUE;
          echo 'LED: '.($ledOn ? 'on' : 'off')."\n";
          $board->digitalWrite($led, $ledOn ? Firmata\DIGITAL_HIGH : Firmata\DIGITAL_LOW);
          $ledOn = !$ledOn;
        },
        1000
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


