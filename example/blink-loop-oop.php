<?php
/** @var \Carica\Firmata\Board $board */
$board = require(__DIR__.'/bootstrap.php');
Carica\Io\Event\Loop\Factory::run($board->activate());

$board->pins[13]->mode = Carica\Firmata\Pin::MODE_OUTPUT;
while (TRUE) {
  $board->pins[13]->digital = !$board->pins[13]->digital;
  sleep(1);
}
