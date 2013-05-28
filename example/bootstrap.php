<?php
require('../vendor/autoload.php');

Carica\Io\Loader::map(
  ['Carica\Firmata' => __DIR__.'/../src/Carica/Firmata']
);
Carica\Io\Loader::register();

use Carica\Io;
use Carica\Firmata;

if (@include('./configuration.php')) {
  if (CARICA_FIRMATA_MODE == 'tcp') {
    return  new Firmata\Board(
      new Io\Stream\Tcp(CARICA_FIRMATA_TCP_SERVER, CARICA_FIRMATA_TCP_PORT)
    );
  } else {
    return  new Firmata\Board(
      new Io\Stream\Serial(CARICA_FIRMATA_SERIAL_DEVICE)
    );
  }
} else {
  die('Please copy "dist.configuration.php" to "configuration.php" and change the configuration options');
}