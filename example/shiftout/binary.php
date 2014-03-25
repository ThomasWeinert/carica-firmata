<?php
$board = require(__DIR__.'/../bootstrap.php');

use Carica\Io;
use Carica\Firmata;

$loop = Io\Event\Loop\Factory::get();

$board
  ->activate()
  ->done(
    function () use ($board, $loop) {
      echo "Firmata ".$board->version." active\n";

      $latchPin = $board->pins[8];
      $clockPin = $board->pins[12];
      $dataPin = $board->pins[11];
      $latchPin->mode = Firmata\Pin::MODE_OUTPUT;
      $clockPin->mode = Firmata\Pin::MODE_OUTPUT;
      $dataPin->mode = Firmata\Pin::MODE_OUTPUT;

      $loop->setInterval(
        function () use ($board, $latchPin, $clockPin, $dataPin) {
          static $number = 0;
          $latchPin->digital = FALSE;
          $board->shiftOut($dataPin->pin, $clockPin->pin, $number);
          $latchPin->digital = TRUE;
          if (++$number > 255) {
            $number = 0;
          }
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

