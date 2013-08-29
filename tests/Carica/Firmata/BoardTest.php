<?php

namespace Carica\Firmata {

  include_once(__DIR__.'/Bootstrap.php');

  class BoardTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Board::__construct
     * @covers Carica\Firmata\Board::stream
     */
    public function testConstructor() {
      $board = new Board($stream = $this->getMock('Carica\Io\Stream'));
      $this->assertSame($stream, $board->stream());
    }

    /**
     * @covers Carica\Firmata\Board::isActive
     */
    public function testIsActiveExpectingFalse() {
      $board = new Board($this->getMock('Carica\Io\Stream'));
      $this->assertFalse($board->isActive());
    }

    /**
     * @covers Carica\Firmata\Board::buffer
     */
    public function testBufferGetAfterSet() {
      $buffer = $this->getMock('Carica\Firmata\Buffer');
      $board = new Board($this->getMock('Carica\Io\Stream'));
      $board->buffer($buffer);
      $this->assertSame($buffer, $board->buffer());
    }

    /**
     * @covers Carica\Firmata\Board::buffer
     */
    public function testBufferGetImplicitCreate() {
      $board = new Board($this->getMock('Carica\Io\Stream'));
      $this->assertInstanceOf('Carica\Firmata\Buffer', $board->buffer());
    }

    /**
     * @covers Carica\Firmata\Board::reset
     */
    public function testReset() {
      $stream = $this->getMock('Carica\Io\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::SYSTEM_RESET]);

      $board = new Board($stream);
      $board->reset();
    }

    /**
     * @covers Carica\Firmata\Board::reportVersion
     */
    public function testReportVersion() {
      $stream = $this->getMock('Carica\Io\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::REPORT_VERSION]);
      $board = new Board($stream);
      $board->reportVersion(function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('reportversion')
      );
    }

    /**
     * @covers Carica\Firmata\Board::queryFirmware
     */
    public function testQueryFirmware() {
      $stream = $this->getMock('Carica\Io\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::QUERY_FIRMWARE, Board::END_SYSEX]);
      $board = new Board($stream);
      $board->queryFirmware(function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('queryfirmware')
      );
    }

    /**
     * @covers Carica\Firmata\Board::queryCapabilities
     */
    public function testQueryCapabilities() {
      $stream = $this->getMock('Carica\Io\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::CAPABILITY_QUERY, Board::END_SYSEX]);
      $board = new Board($stream);
      $board->queryCapabilities(function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('capability-query')
      );
    }

    /**
     * @covers Carica\Firmata\Board::queryAnalogMapping
     */
    public function testQueryAnalogMapping() {
      $stream = $this->getMock('Carica\Io\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::ANALOG_MAPPING_QUERY, Board::END_SYSEX]);
      $board = new Board($stream);
      $board->queryAnalogMapping(function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('analog-mapping-query')
      );
    }

    /**
     * @covers Carica\Firmata\Board::queryPinState
     */
    public function testQueryPinState() {
      $stream = $this->getMock('Carica\Io\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::PIN_STATE_QUERY, 0x2A, Board::END_SYSEX]);
      $board = new Board($stream);
      $board->queryPinState(42, function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('pin-state-42')
      );
    }

    /**
     * @covers Carica\Firmata\Board::analogRead
     */
    public function testAnalogRead() {
      $board = new Board($this->getMock('Carica\Io\Stream'));
      $board->analogRead(42, $callback = function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('analog-read-42')
      );
    }

    /**
     * @covers Carica\Firmata\Board::digitalRead
     */
    public function testDigitalRead() {
      $board = new Board($this->getMock('Carica\Io\Stream'));
      $board->digitalRead(42, $callback = function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('digital-read-42')
      );
    }

    /**
     * @covers Carica\Firmata\Board::analogWrite
     */
    public function testAnalogWrite() {
      $stream = $this->getMock('Carica\Io\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([0xEA, 0x17, 0x00]);
      $pin = $this
        ->getMockBuilder('Carica\Firmata\Pin')
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('setValue')
        ->with(23);

      $board = new Board($stream);
      $board->pins[42] = $pin;
      $board->analogWrite(42, 23);
    }

    /**
     * @covers Carica\Firmata\Board::servoWrite
     */
    public function testServoWrite() {
      $stream = $this->getMock('Carica\Io\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([0xEA, 0x17, 0x00]);
      $pin = $this
        ->getMockBuilder('Carica\Firmata\Pin')
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('setValue')
        ->with(23);

      $board = new Board($stream);
      $board->pins[42] = $pin;
      $board->servoWrite(42, 23);
    }

    /**
     * @covers Carica\Firmata\Board::pinMode
     */
    public function testPinMode() {
      $stream = $this->getMock('Carica\Io\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::PIN_MODE, 0x2A, Board::PIN_MODE_OUTPUT]);
      $pin = $this
        ->getMockBuilder('Carica\Firmata\Pin')
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('setMode')
        ->with(Board::PIN_MODE_OUTPUT);

      $board = new Board($stream);
      $board->pins[42] = $pin;
      $board->pinMode(42, Board::PIN_MODE_OUTPUT);
    }
  }
}