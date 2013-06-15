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

      $dimmerPin = 14;
      $ledPin = 9;
      $board->pins[$dimmerPin]->mode = Firmata\PIN_STATE_ANALOG;
      $board->pins[$ledPin]->mode = Firmata\PIN_STATE_PWM;

      $board->analogRead(
        $dimmerPin,
        function($value) use ($board, $ledPin) {
          $value = 1023 - $value;
          $barLength = round($value * 0.07);
          $dimmed = floor($value * 255 / 1023);
          echo str_pad($dimmed, 3, 0, STR_PAD_LEFT), ' ';
          echo str_repeat('=', $barLength), "\n";
          $board->pins[$ledPin]->analog = $dimmed;
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