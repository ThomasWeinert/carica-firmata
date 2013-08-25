<?php

namespace Carica\Firmata\Exception {

  include_once(__DIR__.'/../Bootstrap.php');

  class UnsupportedModeTest extends \PHPUnit_Framework_TestCase {

    public function testConstructor() {
      $exception = new UnsupportedMode(42, \Carica\Firmata\Board::PIN_STATE_OUTPUT);
      $this->assertEquals(
        'Pin 42 does not support mode "digital output"',
        $exception->getMessage()
      );
    }
  }
}
