<?php
$board = require(__DIR__.'/bootstrap.php');

use Carica\Io;
use Carica\Firmata;

$loop = Io\Event\Loop\Factory::get();

$board->events()->on(
  Firmata\Board::EVENT_REPORTVERSION,
  static function () use ($board) {
    echo 'Firmata version: '.$board->version."\n";
  }
);
$board->events()->on(
  Firmata\Board::EVENT_QUERYFIRMWARE,
  static function () use ($board) {
    echo 'Firmware version: '.$board->firmware."\n";
  }
);

$board
  ->activate()
  ->progress(
    static function($step, $try = NULL) {
      echo 'Activation Step: ', $step, (isset($try) ? ' #'.$try : ''), "\n";
    }
  )
  ->done(
    static function () use ($board) {
      echo "activated\n";
      $board->events()->on(
        Firmata\Board::EVENT_REACTIVATE,
        static function() {
           echo "reactivated\n";
        }
      );
    }
  )
  ->fail(
    static function ($error) use ($loop) {
      echo $error."\n";
      $loop->stop();
    }
  );

$loop->run();
