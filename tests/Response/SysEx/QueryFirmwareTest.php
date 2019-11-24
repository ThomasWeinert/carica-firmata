<?php

namespace Carica\Firmata\Response\SysEx {

  use PHPUnit\Framework\TestCase;

  include_once(__DIR__.'/../../Bootstrap.php');

  class QueryFirmwareTest extends TestCase {

    /**
     * @covers \Carica\Firmata\Response\SysEx\QueryFirmware
     */
    public function testConstructor(): void {
      $version = new QueryFirmware(
        $data = [
          0x02, 0x03,
          0x53, 0x00, 0x61, 0x00, 0x6D, 0x00, 0x70, 0x00, 0x6C, 0x00, 0x65, 0x00
        ]
      );
      $this->assertEquals(
        2, $version->major
      );
      $this->assertEquals(
        3, $version->minor
      );
      $this->assertEquals(
        'Sample', $version->name
      );
      $this->assertEquals(
        0x79,
        $version->command
      );
    }
  }
}
