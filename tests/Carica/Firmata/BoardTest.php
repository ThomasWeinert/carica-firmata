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
     * @covers Carica\Firmata\Board::__get
     */
    public function testGetPropertyPins() {
      $board = new Board($this->getMock('Carica\Io\Stream'));
      $this->assertInstanceOf('ArrayObject', $board->pins);
    }

    /**
     * @covers Carica\Firmata\Board::__get
     */
    public function testGetPropertyVersion() {
      $board = new Board($this->getMock('Carica\Io\Stream'));
      $this->assertInstanceOf('Carica\Firmata\Version', $board->version);
    }

    /**
     * @covers Carica\Firmata\Board::__get
     */
    public function testGetPropertyFirmware() {
      $board = new Board($this->getMock('Carica\Io\Stream'));
      $this->assertInstanceOf('Carica\Firmata\Version', $board->firmware);
    }

    /**
     * @covers Carica\Firmata\Board::__get
     */
    public function testGetPropertyWithUnknownPropertyName() {
      $board = new Board($this->getMock('Carica\Io\Stream'));
      $this->setExpectedException('LogicException');
      $dummy = $board->INVALID_PROPERTY;
    }

    /**
     * @covers Carica\Firmata\Board::activate
     */
    public function testActivateStreamCanNotOpenExpectingRejectedPromise() {
      $events = $this->getMock('Carica\Io\Event\Emitter');
      $events
        ->expects($this->exactly(3))
        ->method('on')
        ->with($this->logicalOr('error', 'read-data', 'response'));

      $buffer = $this->getMock('Carica\Firmata\Buffer');
      $buffer
        ->expects($this->any())
        ->method('events')
        ->will($this->returnValue($events));

      $stream = $this->getMock('Carica\Io\Stream\Tcp');
      $stream
        ->expects($this->any())
        ->method('events')
        ->will($this->returnValue($events));
      $stream
        ->expects($this->once())
        ->method('open')
        ->will($this->returnValue(FALSE));

      $board = new Board($stream);
      $board->buffer($buffer);

      $promise = $board->activate(function(){});
      $this->assertInstanceOf('Carica\Io\Deferred\Promise', $promise);
    }

    /**
     * @covers Carica\Firmata\Board::onResponse
     */
    public function testOnResponseWithUnknownResponseExpectingException() {
      $response = $this
        ->getMockBuilder('Carica\Firmata\Response\SysEx')
        ->disableOriginalConstructor()
        ->getMock();
      $response
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('command', 0x00)
            )
          )
        );
      $board = new Board($this->getMock('Carica\Io\Stream'));
      $this->setExpectedException(
        'UnexpectedValueException',
        'Unknown response command: 0x00'
      );
      $board->onResponse($response);
    }

    /**
     * @covers Carica\Firmata\Board::onResponse
     * @covers Carica\Firmata\Board::onQueryFirmware
     */
    public function testOnResponseWithqueryFirmware() {
      $response = $this
        ->getMockBuilder('Carica\Firmata\Response\SysEx\QueryFirmware')
        ->disableOriginalConstructor()
        ->getMock();
      $response
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('command', Board::QUERY_FIRMWARE),
              array('major', 42),
              array('minor', 21),
              array('name', 'TEST')
            )
          )
        );
      $events = $this->getMock('Carica\Io\Event\Emitter');
      $events
        ->expects($this->once())
        ->method('emit')
        ->with('queryfirmware');

      $board = new Board($this->getMock('Carica\Io\Stream'));
      $board->events($events);
      $board->onResponse($response);
      $this->assertEquals('TEST 42.21', (string)$board->firmware);
    }

    /**
     * @covers Carica\Firmata\Board::onResponse
     * @covers Carica\Firmata\Board::onStringData
     */
    public function testOnResponseWithStringData() {
      $response = $this
        ->getMockBuilder('Carica\Firmata\Response\SysEx\String')
        ->disableOriginalConstructor()
        ->getMock();
      $response
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('command', Board::STRING_DATA),
              array('text', 'Hello World!')
            )
          )
        );
      $events = $this->getMock('Carica\Io\Event\Emitter');
      $events
        ->expects($this->once())
        ->method('emit')
        ->with('string', 'Hello World!');

      $board = new Board($this->getMock('Carica\Io\Stream'));
      $board->events($events);
      $board->onResponse($response);
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
     * @covers Carica\Firmata\Board::queryAllPinStates
     */
    public function testQueryAllPinStates() {
      $stream = $this->getMock('Carica\Io\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::PIN_STATE_QUERY, 0x2A, Board::END_SYSEX]);
      $pin = $this
        ->getMockBuilder('Carica\Firmata\Pin')
        ->disableOriginalConstructor()
        ->getMock();
      $board = new Board($stream);
      $board->pins[42] = $pin;
      $board->queryAllPinStates();
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
     * @covers Carica\Firmata\Board::digitalWrite
     */
    public function testDigitalWrite() {
      $stream = $this->getMock('Carica\Io\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([0x91, 0x0B, 0x00]);
      $pin = $this->getPinFixture(
        ['pin' => 8, 'mode' => Board::PIN_MODE_OUTPUT, 'digital' => TRUE]
      );
      $pin
        ->expects($this->once())
        ->method('setDigital')
        ->with(Board::DIGITAL_HIGH);

      $board = new Board($stream);
      $board->pins[8] = $pin;
      $board->pins[9] = $this->getPinFixture(
        ['pin' => 9, 'mode' => Board::PIN_MODE_OUTPUT, 'digital' => TRUE]
      );
      $board->pins[10] = $this->getPinFixture(
        ['pin' => 10, 'mode' => Board::PIN_MODE_OUTPUT, 'digital' => FALSE]
      );
      $board->pins[11] = $this->getPinFixture(
        ['pin' => 11, 'mode' => Board::PIN_MODE_OUTPUT, 'digital' => TRUE]
      );
      $board->pins[24] = $this->getPinFixture(
        ['pin' => 24, 'mode' => Board::PIN_MODE_OUTPUT, 'digital' => TRUE]
      );
      $board->digitalWrite(8, Board::DIGITAL_HIGH);
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

    /**
     * @covers Carica\Firmata\Board::pulseIn
     */
    public function testPulseIn() {
      $stream = $this->getMock('Carica\Io\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with("\xF0\x74\x2A\x01\x00\x00\x00\x00\x00\x00\x05\x00\x00\x00\x0f\x00\x42\x00\x40\x00\xF7");
      $board = new Board($stream);
      $board->pulseIn(42, function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('pulse-in-42')
      );
    }

    /****************************
     * Fixtures
     ***************************/

    private function getPinFixture($data) {
      $pin = $this
        ->getMockBuilder('Carica\Firmata\Pin')
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('pin', isset($data['pin']) ? $data['pin'] : 0),
              array('mode', isset($data['mode']) ? $data['mode'] : 0x00),
              array('digital', isset($data['digital']) ? $data['digital'] : FALSE),
              array('analog', isset($data['analog']) ? $data['analog'] : 0),
              array('value', isset($data['value']) ? $data['value'] : 0),
              array('supports', isset($data['supports']) ? $data['supports'] : array()),
            )
          )
        );
      return $pin;
    }

    private function getStartupBytes() {

    }
  }
}