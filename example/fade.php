<?php
$board = require('./bootstrap.php');

use Carica\Io;
use Carica\Firmata;

$loop = Io\Event\Loop\Factory::get();

$board
  ->activate()
  ->done(
    function () use ($board, $loop) {
      echo "Firmata ".$board->version." active\n";

      $led = 3;
      $board->pinMode($led, Firmata\PIN_STATE_PWM);
      echo "PIN: $led\n";

      $loop->setInterval(
        function () use ($board, $led) {
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

if ($board->isActive()) {
  $loop->run();
}

