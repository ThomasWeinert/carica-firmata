<?php

namespace Carica\Firmata\Response\SysEx {

  include_once(__DIR__ . '/../../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;

  class StringTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Response\SysEx\String
     */
    public function testConstructor() {
      $string = new String(
        0x71,
        [0x71, 0x48, 0x00, 0x61, 0x00, 0x6C, 0x00, 0x6C, 0x00, 0x6F, 0x00]
      );
      $this->assertEquals(
        'Hallo',
        $string->text
      );
      $this->assertEquals(
        0x71,
        $string->command
      );
    }
  }
}