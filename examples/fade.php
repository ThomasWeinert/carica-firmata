<?php
$board = require(__DIR__.'/bootstrap.php');

use Carica\Io;
use Carica\Firmata;

$loop = Io\Event\Loop\Factory::get();

$board
  ->activate()
  ->done(
    static function () use ($board, $loop) {
      echo 'Firmata '.$board->version." active\n";

      $led = 9;
      $board->pinMode($led, Firmata\Pin::MODE_PWM);
      echo "PIN: $led\n";

      $loop->setInterval(
        static function () use ($board, $led) {
          static $brightness = 0, $step = 5;
          echo 'LED: '.$brightness."\n";
          $board->analogWrite($led, $brightness);
          $brightness += $step;
          if ($brightness <= 0 || $brightness >= 255) {
            $step = -$step;
          }
        },
        100
      );
    }
  )
  ->fail(
    function ($error) {
      echo $error."\n";
    }
  );

$loop->run();

