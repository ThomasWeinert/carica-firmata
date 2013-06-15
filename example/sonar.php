<?php
$board = require(__DIR__.'/bootstrap.php');

use Carica\Io;
use Carica\Firmata;

$board = new Firmata\Board(
  //new Io\Stream\Serial('COM3')
  new Io\Stream\Tcp('127.0.0.1', 5338)
);

$loop = Io\Event\Loop\Factory::get();

$board
  ->activate()
  ->done(
    function () use ($board, $loop) {
      $loop->setInterval(
        function () use ($board) {
          $board->pulseIn(
            7,
            function ($duration) {
              echo round($duration / 29 / 2)." cm\n";
            }
          );
        },
        500
      );
    }
  );

if ($board->isActive()) {
  $loop->run();
}


