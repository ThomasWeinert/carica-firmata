<?php

require('../vendor/autoload.php');

Carica\Io\Loader::map(
  ['Carica\Firmata' => __DIR__.'/../src/Carica/Firmata']
);
Carica\Io\Loader::register();

use Carica\Io;
use Carica\Firmata;

return  new Firmata\Board(
  //new Io\Stream\Serial('COM7')
  new Io\Stream\Tcp('127.0.0.1', 5339)
);