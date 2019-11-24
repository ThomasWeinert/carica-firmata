<?php
$board = require(__DIR__.'/../bootstrap.php');

use Carica\Io;
use Carica\Firmata;

$loop = Io\Event\Loop\Factory::get();

$board
  ->activate()
  ->done(
    static function () use ($board, $loop) {
      echo 'Firmata '.$board->version." active\n";

      $shiftOut = new Firmata\ShiftOut(
        $board->pins[8],
        $board->pins[12],
        $board->pins[11]
      );

      $numbers = [
        0x3F, 0x06, 0x5B, 0x4F, 0x66, 0x6D, 0x7D, 0x07, 0x7F, 0x6F
      ];

      $loop->setInterval(
        static function () use ($shiftOut, $numbers) {
          static $number = 0;

          echo $number, "\n";

          $shiftOut->write(0xFF ^ $numbers[$number]);

          if (++$number > 9) {
            $number = 0;
          }
        },
        1000
      );
    }
  )
  ->fail(
    static function ($error) {
      echo $error."\n";
    }
  );

$loop->run();

