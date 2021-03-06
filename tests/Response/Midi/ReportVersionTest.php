<?php

namespace Carica\Firmata\Response\Midi {

  use PHPUnit\Framework\TestCase;

  include_once(__DIR__.'/../../Bootstrap.php');

  class ReportVersionTest extends TestCase {

    /**
     * @covers \Carica\Firmata\Response\Midi\ReportVersion
     */
    public function testConstructor(): void {
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
