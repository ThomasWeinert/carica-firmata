<?php

namespace Carica\Firmata\Response\SysEx {

  include_once(__DIR__.'/../../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;

  class PulseInTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Response\SysEx\PulseIn
     */
    public function testConstructor() {
      $pulse = new PulseIn(
        0x74,
        [0x74, 0x03, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x2A, 0x00]
      );
      $this->assertEquals(
        42,
        $pulse->duration
      );
      $this->assertEquals(
        3,
        $pulse->pin
      );
      $this->assertEquals(
        0x74,
        $pulse->command
      );
    }
  }
}