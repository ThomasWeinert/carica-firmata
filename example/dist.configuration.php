<?php

/**
 * The connection mode
 * 
 * serial - serial connection
 * tcp - tcp connection (network shield or serproxy)
 * 
 * @var string
 */
define('CARICA_FIRMATA_MODE', 'serial');

/**
 * serial connection options * 
 */
define('CARICA_FIRMATA_SERIAL_DEVICE', '/dev/tty0');
//define('CARICA_FIRMATA_SERIAL_BAUD', 57600); // default is 57600

/**
 * tcp connection options *
 */
define('CARICA_FIRMATA_TCP_SERVER', '127.0.0.1');
define('CARICA_FIRMATA_TCP_PORT', 5339);