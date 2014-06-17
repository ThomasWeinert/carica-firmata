<?php
/** @var \Carica\Firmata\Board $board */
$board = require(__DIR__.'/bootstrap.php');
Carica\Io\Event\Loop\Factory::run($board->activate());

$board->pinMode(13, 0x01);
while (TRUE) {
  $board->digitalWrite(13, 0xFF);
  sleep(1);
  $board->digitalWrite(13, 0x00);
  sleep(1);
}
