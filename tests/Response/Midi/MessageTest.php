<?php

namespace Carica\Firmata\Response\Midi {

  use PHPUnit\Framework\TestCase;

  include_once(__DIR__.'/../../Bootstrap.php');

  class MessageTest extends TestCase {

    /**
     * @covers       \Carica\Firmata\Response\Midi\Message
     * @dataProvider provideMessageExamples
     * @param int $command
     * @param int $port
     * @param int $value
     * @param array $bytes
     */
    public function testConstructor($command, $port, $value, $bytes): void {
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

    public function provideMessageExamples(): array {
      return [
        [0xE0, 1, 0x00, [0xE0 | 0x01, 0x00, 0x00]],
        [0xE0, 2, 0x01, [0xE0 | 0x02, 0x01, 0x00]]
      ];
    }
  }
}
