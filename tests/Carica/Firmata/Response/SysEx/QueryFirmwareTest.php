<?php

namespace Carica\Firmata\Response\SysEx {

  include_once(__DIR__.'/../../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;

  class QueryFirmwareTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Response\SysEx\QueryFirmware
     */
    public function testConstructor() {
      $version = new QueryFirmware(
        0x79,
        $data = [
          0x79,
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
    }
  }
}