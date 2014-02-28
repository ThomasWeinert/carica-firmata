<?php
require(__DIR__ . '/../vendor/autoload.php');

use Carica\Io;
use Carica\Firmata;

// get the event loop
$loop = Io\Events\Loop\Factory::get();

// get a arduino board
$board = new Firmata\Board(
  // connected to a serial port
  new Io\Stream\Serial('/dev/tty.usbserial-A1001NQe')
);

$board
  ->activate()
  ->done(
    // if the board is successfully activated
    function () use ($board, $loop) {
      // set the pin mode
      $board->pins[13]->mode = Firmata\Board::PIN_MODE_OUTPUT;

      // add a repeated 2 seconds timer to the event loop
      $loop->setInterval(
        function () use ($board) {
          // toggle the led
          $board->pins[13]->digital = !$board->pins[13]->digital;
        },
        2000
      );
    }
  );

$loop->run();