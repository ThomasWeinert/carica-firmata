<?php

namespace Carica\Firmata\Exception {

  use Carica\Firmata\Pin as FirmataPin;
  use PHPUnit\Framework\TestCase;

  include_once(__DIR__.'/../Bootstrap.php');

  class UnsupportedModeTest extends TestCase {

    public function testConstructor(): void {
      $exception = new UnsupportedMode(42, FirmataPin::MODE_OUTPUT);
      $this->assertEquals(
        'Pin 42 does not support mode "digital output"',
        $exception->getMessage()
      );
    }
  }
}
