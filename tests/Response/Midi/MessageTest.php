<?php

namespace Carica\Firmata\Response\Midi {

  include_once(__DIR__ . '/../../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;

  class MessageTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Response\Midi\Message
     * @dataProvider provideMessageExamples
     */
    public function testConstructor($command, $port, $value, $bytes) {
      $message = new Message(
        $command,
        $bytes
      );
      $this->assertEquals(
        $command, $message->command
      );
      $this->assertEquals(
        $port, $message->port
      );
      $this->assertEquals(
        $value, $message->value
      );
    }

    public function provideMessageExamples() {
      return array(
        array(0xE0, 1, 0x00, [0xE0 | 0x01, 0x00, 0x00]),
        array(0xE0, 2, 0x01, [0xE0 | 0x02, 0x01, 0x00])
      );
    }
  }
}