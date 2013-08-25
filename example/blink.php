<?php
$board = require(__DIR__.'/bootstrap.php');

use Carica\Io;
use Carica\Firmata;

$loop = Io\Event\Loop\Factory::get();

$board
  ->activate()
  ->done(
    function () use ($board, $loop) {
      echo "Firmata ".$board->version." active\n";

      $led = 9;
      $board->pinMode($led, Firmata\Board::PIN_STATE_OUTPUT);
      echo "PIN: $led\n";

      $loop->setInterval(
        function () use ($board, $led) {
          static $ledOn = TRUE;
          echo 'LED: '.($ledOn ? 'on' : 'off')."\n";
          $board->digitalWrite($led, $ledOn ? Firmata\Board::DIGITAL_HIGH : Firmata\Board::DIGITAL_LOW);
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


