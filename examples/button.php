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

      $buttonPin = 2;
      $ledPin = 13;

      $board->pins[$buttonPin]->mode = Firmata\Pin::MODE_INPUT;
      $board->pins[$ledPin]->mode = Firmata\Pin::MODE_OUTPUT;

      $board->digitalRead(
        $buttonPin,
        static function($value) use ($board, $ledPin) {
          echo ($value === Firmata\Board::DIGITAL_HIGH) ? "Button down\n" :  "Button up\n";
          $board->pins[$ledPin]->digital = $value === Firmata\Board::DIGITAL_HIGH;
        }
      );
    }
  )
  ->fail(
    static function ($error) {
      echo $error."\n";
    }
  );

$loop->run();


