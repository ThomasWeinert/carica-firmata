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

      $shiftOut = new Firmata\ShiftOut(
        $board->pins[8],
        $board->pins[12],
        $board->pins[11]
      );

      $loop->setInterval(
        function () use ($shiftOut) {
          static $number = 0;
          $shiftOut->write($number);
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

$loop->run();

