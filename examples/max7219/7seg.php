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

      $digits = 8;
      $maximum = pow(10, $digits) - 0;

      $max = new Firmata\ShiftOut(
        $board->pins[8],
        $board->pins[12],
        $board->pins[11]
      );
      $max->write([0x0F, 0x01]);
      sleep(1);
      $max->write([0x0F, 0x00]);
      // Enable mode B
      $max->write([0x09, 0xFF]);
      // Use max intensity
      $max->write([0x0A, 0xFF]);
      // Only scan eight digits
      $max->write([0x0B, $digits - 1]);
      // Turn on chip
      $max->write([0x0C, 0x01]);

      $loop->setInterval(
        function () use ($max, $maximum) {
          static $number = 0;

          if (--$number < 0) {
            $number = $maximum;
          }

          $string = str_pad($number, 8, ' ',STR_PAD_LEFT);
          echo $string, "\n";

          $length = strlen($string);
          for($i = 0; $i < $length; $i++) {
            $digit = $string[$i] === ' ' ? 0 : (int)$string[$i];
            $max->write([8 - $i, $digit]);
          }

        },
        200
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

