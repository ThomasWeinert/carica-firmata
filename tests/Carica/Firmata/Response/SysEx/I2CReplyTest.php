<?php

namespace Carica\Firmata\Response\SysEx {

  include_once(__DIR__.'/../../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;

  class I2CReplyTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Response\SysEx\I2CReply
     */
    public function testConstructor() {
      $reply = new I2CReply(
        Firmata\Board::I2C_REPLY,
        [
          0x77,
          0x01, 0x00,
          0x02, 0x00,
          0x48, 0x00, 0x61, 0x00, 0x6C, 0x00, 0x6C, 0x00, 0x6F, 0x00
        ]
      );

      $this->assertEquals(1, $reply->slaveAddress);
      $this->assertEquals(2, $reply->register);
      $this->assertEquals('Hallo', $reply->data);
      $this->assertEquals(Firmata\Board::I2C_REPLY, $reply->command);
    }
  }
}