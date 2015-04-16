<?php

namespace Carica\Firmata\I2C {

  include_once(__DIR__ . '/../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;

  class ReplyTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\I2C\Reply
     */
    public function testConstructor() {
      $reply = new Reply(
        Firmata\I2C::REPLY,
        [
          0x01, 0x00,
          0x02, 0x00,
          0x48, 0x00, 0x61, 0x00, 0x6C, 0x00, 0x6C, 0x00, 0x6F, 0x00
        ]
      );

      $this->assertEquals(1, $reply->slaveAddress);
      $this->assertEquals(2, $reply->register);
      $this->assertEquals([72, 97, 108, 108, 111], $reply->data);
      $this->assertEquals(Firmata\I2C::REPLY, $reply->command);
    }
  }
}