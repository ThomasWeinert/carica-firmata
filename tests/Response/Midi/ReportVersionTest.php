<?php

namespace Carica\Firmata\Response\Midi {

  include_once(__DIR__ . '/../../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;

  class ReportVersionTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Response\Midi\ReportVersion
     */
    public function testConstructor() {
      $message = new ReportVersion([0xF9, 0x15, 0x2A]);
      $this->assertEquals(
        0xF9, $message->command
      );
      $this->assertEquals(
        21, $message->major
      );
      $this->assertEquals(
        42, $message->minor
      );
    }
  }
}