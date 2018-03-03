<?php

namespace Carica\Firmata\Exception {

  include_once(__DIR__ . '/../Bootstrap.php');

  class UnsupportedModeTest extends \PHPUnit\Framework\TestCase {

    public function testConstructor() {
      $exception = new UnsupportedMode(42, \Carica\Firmata\Pin::MODE_OUTPUT);
      $this->assertEquals(
        'Pin 42 does not support mode "digital output"',
        $exception->getMessage()
      );
    }
  }
}
