<?php

namespace Carica\Firmata {

  include_once(__DIR__ . '/Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;

  class PulseInTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\PulseIn
     */
    public function testPulsInTrigger() {
      $stream = $this->getMock('Carica\Io\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with("\xF0\x74\x03\x01\x00\x00\x00\x00\x00\x00\x05\x00\x00\x00\x0f\x00\x42\x00\x40\x00\xF7");

      $events = new \Carica\Io\Event\Emitter();
      $board = $this
        ->getMockBuilder('Carica\Firmata\Board')
        ->disableOriginalConstructor()
        ->getMock();
      $board
        ->expects($this->any())
        ->method('events')
        ->will($this->returnValue($events));
      $board
        ->expects($this->any())
        ->method('stream')
        ->will($this->returnValue($stream));

      $pulse = new PulseIn($board);
      $actualDuration = null;
      $pulse(3)->done(
        function($duration) use (&$actualDuration) {
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