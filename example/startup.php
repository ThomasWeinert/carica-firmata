<?php
$board = require('./bootstrap.php');

use Carica\Io;
use Carica\Firmata;

$loop = Io\Event\Loop\Factory::get();

$board->events()->on(
  'reportversion',
  function () use ($board) {
    echo 'Firmata version: '.$board->version."\n";
  }
);
$board->events()->on(
  'queryfirmware',
  function () use ($board) {
    echo 'Firmware version: '.$board->firmware."\n";
  }
);

$board
  ->activate()
  ->done(
    function () {
      echo "activated\n";
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


