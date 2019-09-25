<?php
$board = require(__DIR__.'/bootstrap.php');

use Carica\Io;
use Carica\Firmata;

// create the event loop
$loop = Io\Event\Loop\Factory::get();

// activate the board
$board
  ->activate()
  ->done(
    // after the board got activated
    static function () use ($board, $loop) {
      // output the version
      echo 'Firmata '.$board->version." active\n";

      // get pin 13 (most boards already have an led on this pin)
      $pin = $board->pins[13];
      // set the mode of pin 13 to digital output
      $pin->mode = Firmata\Pin::MODE_OUTPUT;

      // add an callback to the event loop that is called every second
      $loop->setInterval(
        static function() use ($pin) {
          // toggle the pin (digital) value
          $pin->digital = !$pin->digital;
          // output the current status
          echo 'LED: '.($pin->digital ? 'on' : 'off')."\n";
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

// start the event loop
$loop->run();


