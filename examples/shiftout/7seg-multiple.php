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

      $segments = 2;
      $numbers = [
        0x3F, 0x06, 0x5B, 0x4F, 0x66, 0x6D, 0x7D, 0x07, 0x7F, 0x6F
      ];


      $loop->setInterval(
        static function () use ($shiftOut, $numbers, $segments) {
          static $number = 0;

          $digits = str_pad($number, $segments, 0, STR_PAD_LEFT);
          $bytes = [];
          for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $bytes[] = 0xFF ^ (int)$numbers[$digits[$i]];
          }
          echo $digits, "\n";

          $shiftOut->write($bytes);

          if (++$number > ((10 ** $segments) - 1)) {
            $number = 0;
          }
        },
        100
      );
    }
  )
  ->fail(
    static function ($error) {
      echo $error."\n";
    }
  );

$loop->run();

