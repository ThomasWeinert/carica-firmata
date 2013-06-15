<?php
require(__DIR__.'/../vendor/autoload.php');

Carica\Io\Loader::map(
  ['Carica\Firmata' => __DIR__.'/../src/Carica/Firmata']
);
Carica\Io\Loader::register();

use Carica\Io;
use Carica\Firmata;

if (@include(__DIR__.'/configuration.php')) {
  if (CARICA_FIRMATA_MODE == 'tcp') {
    return  new Firmata\Board(
      new Io\Stream\Tcp(CARICA_FIRMATA_TCP_SERVER, CARICA_FIRMATA_TCP_PORT)
    );
  } elseif (CARICA_FIRMATA_MODE == 'serial-dio') {
    return  new Firmata\Board(
      new Io\Stream\Serial\Dio(CARICA_FIRMATA_SERIAL_DEVICE)
    );
  } else {
    return  new Firmata\Board(
      new Io\Stream\Serial(CARICA_FIRMATA_SERIAL_DEVICE)
    );
  }
} else {
  die('Please copy "dist.configuration.php" to "configuration.php" and change the configuration options');
}