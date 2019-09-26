<?php
$board = require(__DIR__.'/bootstrap.php');

use Carica\Io;
use Carica\Firmata;

$loop = Io\Event\Loop\Factory::get();

$board->events()->on(
  'reportversion',
  static function () use ($board) {
    echo 'Firmata version: '.$board->version."\n";
  }
);
$board->events()->on(
  'queryfirmware',
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
    static function () {
      echo "activated\n";
    }
  )
  ->fail(
    static function ($error) use ($loop) {
      echo $error."\n";
      $loop->stop();
    }
  );

$loop->run();


