<?php

include_once(__DIR__.'/../../../vendor/autoload.php');
Carica\Io\Loader::register();
Carica\Io\Loader::map(
  [ 'Carica\\Firmata\\' => __DIR__.'/../../../src/Carica/Firmata']
);
