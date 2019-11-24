<?php

namespace Carica\Firmata {

  include_once(__DIR__ . '/Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;
  use PHPUnit\Framework\MockObject\MockObject;
  use PHPUnit\Framework\TestCase;

  class PulseInTest extends TestCase {

    /**
     * @covers \Carica\Firmata\PulseIn
     */
    public function testPulsInTrigger(): void {
      $stream = $this->getMockBuilder(Io\Stream::class)->getMock();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with("\xF0\x74\x03\x01\x00\x00\x00\x00\x00\x00\x05\x00\x00\x00\x0f\x00\x42\x00\x40\x00\xF7");

      $events = new Io\Event\Emitter();
      /** @var MockObject|Firmata\Board $board */
      $board = $this
        ->getMockBuilder(Board::class)
        ->disableOriginalConstructor()
        ->getMock();
      $board
        ->method('events')
        ->willReturn($events);
      $board
        ->method('stream')
        ->willReturn($stream);

      $pulse = new PulseIn($board);
      $actualDuration = null;
      $pulse(3)->done(
        static function($duration) use (&$actualDuration) {
          $actualDuration = $duration;
        }
      );
      $events->emit(
        'response',
        new Firmata\Response(
          PulseIn::COMMAND, [0x74, 0x03, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x2A, 0x00]
        )
      );
      $this->assertSame(42, $actualDuration);
    }
  }
}
