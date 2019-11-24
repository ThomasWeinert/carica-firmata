<?php
$board = require(__DIR__.'/bootstrap.php');

use Carica\Io;
use Carica\Firmata;

$loop = Io\Event\Loop\Factory::get();
$board = new Firmata\Board(
  //new Io\Stream\Serial('COM3')
  new Io\Stream\TCPStream($loop, '127.0.0.1', 5338)
);


$board
  ->activate()
  ->done(
    static function () use ($board, $loop) {
      $loop->setInterval(
        static function () use ($board) {
          $board->pulseIn(
            7,
            static function ($duration) {
              echo round($duration / 29 / 2)." cm\n";
            }
          );
        },
        500
      );
    }
  );

$loop->run();


