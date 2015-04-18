<?php
$board = require(__DIR__.'/bootstrap.php');

use Carica\Io;
use Carica\Firmata;

$loop = Io\Event\Loop\Factory::get();

$board
  ->activate()
  ->done(
    function () use ($board, $loop) {
      echo "Started:\n";

      /**
       * Fetch the sensor pin
       * @var Firmata\Pin $sensor
       */
      $sensor = $board->pins[16];
      /**
       * Fetch an led pin
       * @var Firmata\Pin $sensor
       */
      $led = $board->pins[13];

      // mode for the sensor pin is analog input
      $sensor->mode = Firmata\Pin::MODE_ANALOG;
      // mode for the led pin is digital output
      $led->mode = Firmata\Pin::MODE_OUTPUT;

      // if the sensor value changes
      $sensor->events()->on(
        'change-value',
        function (Firmata\Pin $sensor) use ($led) {
          // output the actual sensor value a 4 digit string
          echo str_pad($sensor->value, 4, 0, STR_PAD_LEFT), ' ';
          // output a bar using the analog value (float between 0 and 1)
          echo str_repeat('=', round($sensor->analog * 70)), "\n";
          // switch the led to on if the analog value is greater 70 percent
          $led->digital = $sensor->value > 0.7;
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


