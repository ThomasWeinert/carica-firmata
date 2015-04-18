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
      $board->pins[$dimmerPin]->mode = Firmata\Pin::MODE_ANALOG;
      $board->pins[$ledPin]->mode = Firmata\Pin::MODE_PWM;

      $board->analogRead(
        $dimmerPin,
        function () use ($board, $dimmerPin, $ledPin) {
          $dimmed = 1 - round($board->pins[$dimmerPin]->analog, 1);
          $barLength = round(70 * $dimmed);
          echo str_pad(round($dimmed * 1000), 4, 0, STR_PAD_LEFT), ' ';
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