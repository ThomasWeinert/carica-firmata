<?php
require(__DIR__ . '/../vendor/autoload.php');

use Carica\Io;
use Carica\Firmata;

if (@include(__DIR__ . '/configuration.php')) {
  if (!defined('CARICA_FIRMATA_SERIAL_BAUD')) {
    define('CARICA_FIRMATA_SERIAL_BAUD', 57600);
  }

  switch (CARICA_FIRMATA_MODE) {
    case 'tcp':
      return new Firmata\Board(
        new Io\Stream\Tcp(CARICA_FIRMATA_TCP_SERVER, CARICA_FIRMATA_TCP_PORT)
      );
      break;
    case 'serial-dio':
      return new Firmata\Board(
        new Io\Stream\Serial\Dio(
          CARICA_FIRMATA_SERIAL_DEVICE,
          CARICA_FIRMATA_SERIAL_BAUD
        )
      );
      break;
    case 'serial':
      return new Firmata\Board(
        new Io\Stream\Serial(CARICA_FIRMATA_SERIAL_DEVICE, CARICA_FIRMATA_SERIAL_BAUD)
      );
    default:
      die('Invalid CARICA_FIRMATA_MODE:' . CARICA_FIRMATA_MODE);
  }

} else {
  die('Please copy "dist.configuration.php" to "configuration.php" and change the configuration options');
}