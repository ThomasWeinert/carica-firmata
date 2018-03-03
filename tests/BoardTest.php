<?php

namespace Carica\Firmata {

  use Carica\Io\Deferred;
  use Carica\Io\Deferred\Promise;
  use Carica\Io\Event\Emitter;
  use Carica\Io\Stream;
  use Carica\Io\Stream\Tcp;
  use PHPUnit\Framework\MockObject\MockObject;

  include_once(__DIR__ . '/Bootstrap.php');

  class BoardTest extends \PHPUnit\Framework\TestCase {

    /**
     * @covers \Carica\Firmata\Board::__construct
     * @covers \Carica\Firmata\Board::stream
     */
    public function testConstructor() {
      $events = $this->getMockBuilder(Emitter::class)->getMock();
      $events
        ->expects($this->exactly(1))
        ->method('on')
        ->with('read-data', $this->isInstanceOf('Closure'));
      $board = new Board($stream = $this->getStreamFixture($events));
      $this->assertSame($stream, $board->stream());
    }

    /**
     * @covers \Carica\Firmata\Board::isActive
     */
    public function testIsActiveExpectingFalse() {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('isOpen')
        ->will($this->returnValue(FALSE));
      $board = new Board($stream);
      $this->assertFalse($board->isActive());
    }

    /**
     * @covers \Carica\Firmata\Board::__get
     * @covers \Carica\Firmata\Board::pins
     */
    public function testGetPropertyPinsImplicitCreate() {
      $board = new Board($this->getStreamFixture());
      $this->assertInstanceOf(Pins::class, $board->pins);
    }

    /**
     * @covers \Carica\Firmata\Board::__get
     * @covers \Carica\Firmata\Board::__set
     * @covers \Carica\Firmata\Board::pins
     */
    public function testGetPropertyPinsAfterSet() {
      $pins = $this
        ->getMockBuilder(Pins::class)
        ->disableOriginalConstructor()
        ->getMock();
      $board = new Board($this->getStreamFixture());
      $board->pins($pins);
      $this->assertSame($pins, $board->pins);
    }

    /**
     * @covers \Carica\Firmata\Board::__get
     */
    public function testGetPropertyVersion() {
      $board = new Board($this->getStreamFixture());
      $this->assertInstanceOf(Version::class, $board->version);
    }

    /**
     * @covers \Carica\Firmata\Board::__get
     */
    public function testGetPropertyFirmware() {
      $board = new Board($this->getStreamFixture());
      $this->assertInstanceOf(Version::class, $board->firmware);
    }

    /**
     * @covers \Carica\Firmata\Board::__get
     */
    public function testGetPropertyWithUnknownPropertyName() {
      $board = new Board($this->getStreamFixture());
      $this->expectException(\LogicException::class);
      $board->INVALID_PROPERTY;
    }

    /**
     * @covers \Carica\Firmata\Board::__set
     */
    public function testSetPropertyVersionExpectingException() {
      $board = new Board($this->getStreamFixture());
      $this->expectException(\LogicException::class);
      $board->version = 'trigger';
    }

    /**
     * @covers \Carica\Firmata\Board::__set
     */
    public function testSetPropertyFirmwareExpectingException() {
      $board = new Board($this->getStreamFixture());
      $this->expectException(\LogicException::class);
      $board->firmware = 'trigger';
    }

    /**
     * @covers \Carica\Firmata\Board::__set
     */
    public function testSetPropertyWithUnknownPropertyName() {
      $board = new Board($this->getStreamFixture());
      $this->expectException(\LogicException::class);
      $board->INVALID_PROPERTY = 'trigger';
    }

    /**
     * @covers \Carica\Firmata\Board::activate
     */
    public function testActivateStreamOpenReturnsFalse() {
      $events = $this->getMockBuilder(Emitter::class)->getMock();
      $events
        ->expects($this->exactly(1))
        ->method('once')
        ->with('error', $this->isInstanceOf('Closure'));

      /** @var MockObject|Tcp $stream */
      $stream = $this->getMockBuilder(Tcp::class)->getMock();
      $stream
        ->expects($this->any())
        ->method('events')
        ->will($this->returnValue($events));
      $stream
        ->expects($this->once())
        ->method('open')
        ->will($this->returnValue(FALSE));

      $board = new Board($stream);
      $promise = $board->activate(function(){});
      $this->assertInstanceOf(Promise::class, $promise);
    }

    /**
     * @covers \Carica\Firmata\Board::activate
     */
    public function testActivateStreamErrorRejectsPromise() {
      $events = new Emitter();

      /** @var MockObject|Tcp $stream */
      $stream = $this->getMockBuilder(Tcp::class)->getMock();
      $stream
        ->expects($this->any())
        ->method('events')
        ->will($this->returnValue($events));
      $stream
        ->expects($this->once())
        ->method('open')
        ->will($this->returnValue(FALSE));

      $board = new Board($stream);

      $result = '';
      $promise = $board->activate();
      $promise->fail(
        function ($message) use (&$result) {
          $result = $message;
        }
      );
      $events->emit('error', 'STREAM_ERROR');
      $this->assertEquals('STREAM_ERROR', $result);
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testActivateWithSimpleStartUp() {
      $events = new Emitter();

      /** @var MockObject|Tcp $stream */
      $stream = $this->getMockBuilder(Tcp::class)->getMock();
      $stream
        ->expects($this->any())
        ->method('events')
        ->will($this->returnValue($events));
      $stream
        ->expects($this->once())
        ->method('open')
        ->will($this->returnValue(TRUE));

      $board = new Board($stream);
      $promise = $board->activate();
      $board->stream()->events()->emit(
        'read-data',
        "\xF9\x03\x02". // Version
        "\xF0\x79". // Firmware
        "\x02\x03".  // Firmware version
        "\x53\x00\x61\x00\x6D\x00\x70\x00\x6C\x00\x65\x00". // Firmware string
        "\xF7".
        "\xF0\x6C". // Capabilities Response
        "\x00\x01\x01\x01\x03\x08\x04\x0e\x7f". // pin 0
        "\x00\x01\x01\x01\x02\x0a\x06\x01\x7f". // pin 1
        "\xF7".
        "\xF0\x6A". // Analog Mapping Response
        "\x7F\x00".
        "\xF7"
      );
      $this->assertInstanceOf(Promise::class, $promise);
      $this->assertEquals(Deferred::STATE_RESOLVED, $promise->state());
      $this->assertEquals('3.2', (string)$board->version);
      $this->assertEquals('Sample 2.3', (string)$board->firmware);
      $this->assertCount(2, $board->pins);
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testVersionResponse() {
      $board = new Board($this->getStreamFixture());
      $board->stream()->events()->emit(
        'read-data', "\xF9\x03\x02"
      );
      $this->assertEquals(
        '3.2', (string)$board->version
      );
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testStringResponse() {
      $board = new Board($this->getStreamFixture());
      $board->stream()->events()->emit('read-data', "\xF9\x03\x02");
      $result = NULL;
      $board->events()->on(
        'string',
        function($string) use (&$result) {
          $result = $string;
        }
      );
      $board->stream()->events()->emit(
        'read-data',
        "\xF0\x71\x48\x00\x61\x00\x6C\x00\x6C\x00\x6F\x00\xF7"
      );
      $this->assertEquals('Hallo', $result);
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testI2CResponseExpectingResponseEvent() {
      $board = new Board($this->getStreamFixture());
      $board->stream()->events()->emit('read-data', "\xF9\x03\x02");
      $result = NULL;
      $board->events()->on(
        'response',
        function(Response $response) use (&$result) {
          $result = $response;
        }
      );
      $board->stream()->events()->emit(
        'read-data',
        "\xF0\x77\x01\x00\x02\x00\x48\x00\x61\x00\x6C\x00\x6C\x00\x6F\x00\xF7"
      );
      $this->assertInstanceOf(Response::class, $result);
      /** @var Response $result */
      $this->assertEquals(I2C::REPLY, $result->getCommand());
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testQueryFirmwareResponse() {
      $board = new Board($this->getStreamFixture());
      $board->stream()->events()->emit('read-data', "\xF9\x03\x02");
      $board->stream()->events()->emit(
        'read-data',
        "\xF0\x79\x02\x03\x53\x00\x61\x00\x6D\x00\x70\x00\x6C\x00\x65\x00\xF7"
      );
      $this->assertEquals('Sample 2.3', (string)$board->firmware);
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testCapabilityResponse() {
      $board = new Board($this->getStreamFixture());
      $board->stream()->events()->emit('read-data', "\xF9\x03\x02");
      $board->stream()->events()->emit(
        'read-data',
        "\xF0\x6C\x7F\x7F\x00\x01\x01\x01\x02\x00\xF7"
      );
      $this->assertInstanceOf(Pin::class, $board->pins[2]);
      $this->assertTrue($board->pins[2]->supports(Pin::MODE_ANALOG));
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testAnalogMappingResponse() {
      $board = new Board($this->getStreamFixture());
      $board->stream()->events()->emit('read-data', "\xF9\x03\x02");
      $board->stream()->events()->emit(
        'read-data', "\xF0\x6C\x7F\x7F\x00\x01\x01\x01\x02\x00\xF7"
      );
      $board->stream()->events()->emit('read-data', "\xF0\x6A\x7F\x7F\x01\x03\xF7");
      $this->assertEquals(2, $board->pins->getPinByChannel(1));
      $this->assertEquals(3, $board->pins->getPinByChannel(3));
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testPinStateResponse() {
      $board = new Board($this->getStreamFixture());
      $board->stream()->events()->emit('read-data', "\xF9\x03\x02");
      $result = [];
      $board->events()->on(
        'pin-state',
        function($pin, $mode, $value) use (&$result) {
          $result = [
            'pin' => $pin,
            'mode' => $mode,
            'value' => $value
          ];
        }
      );
      $board->stream()->events()->emit('read-data', "\xF0\x6E\x02\x02\x7F\xF7");
      $this->assertEquals(
        [
          'pin' => 2,
          'mode' => Pin::MODE_ANALOG,
          'value' => 127
        ],
        $result
      );
    }

    /**
     * @covers \Carica\Firmata\Board::reset
     */
    public function testReset() {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::SYSTEM_RESET]);

      $board = new Board($stream);
      $board->reset();
    }

    /**
     * @covers \Carica\Firmata\Board::reportVersion
     */
    public function testReportVersion() {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::REPORT_VERSION]);
      $board = new Board($stream);
      $board->reportVersion(function() {});
      $this->assertCount(
        1, $board->events()->listeners('reportversion')
      );
    }

    /**
     * @covers \Carica\Firmata\Board::queryFirmware
     */
    public function testQueryFirmware() {
      $stream = $this->getStreamFixture();
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
     * @covers \Carica\Firmata\Board::queryCapabilities
     */
    public function testQueryCapabilities() {
      $stream = $this->getStreamFixture();
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
     * @covers \Carica\Firmata\Board::queryAnalogMapping
     */
    public function testQueryAnalogMapping() {
      $stream = $this->getStreamFixture();
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
     * @covers \Carica\Firmata\Board::queryPinState
     */
    public function testQueryPinState() {
      $stream = $this->getStreamFixture();
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
     * @covers \Carica\Firmata\Board::queryAllPinStates
     */
    public function testQueryAllPinStates() {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::PIN_STATE_QUERY, 0x2A, Board::END_SYSEX]);
      $board = new Board($stream);
      $board->pins = $this->getPinsFixture(
        [
          42 => $this->getPinFixture()
        ]
      );
      $board->queryAllPinStates();
    }

    /**
     * @covers \Carica\Firmata\Board::analogRead
     */
    public function testAnalogRead() {
      $board = new Board($this->getStreamFixture());
      $board->analogRead(42, $callback = function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('analog-read-42')
      );
    }

    /**
     * @covers \Carica\Firmata\Board::digitalRead
     */
    public function testDigitalRead() {
      $board = new Board($this->getStreamFixture());
      $board->digitalRead(42, $callback = function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('digital-read-42')
      );
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testAnalogWrite() {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([0xE3, 0x17, 0x00]);
      $pin = $this
        ->getMockBuilder(Pin::class)
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('setValue')
        ->with(23);

      $board = new Board($stream);
      $board->pins = $this->getPinsFixture([3 => $pin]);
      $board->analogWrite(3, 23);
    }

    /**
     * @covers \Carica\Firmata\Board::analogWrite
     */
    public function testAnalogWriteWithHighPinNumber() {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with(
          [
            Board::START_SYSEX,
            Board::EXTENDED_ANALOG,
            0x2A,
            0x00,
            Board::END_SYSEX
          ]
        );
      $pin = $this
        ->getMockBuilder(Pin::class)
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('setValue')
        ->with(0);

      $board = new Board($stream);
      $board->pins = $this->getPinsFixture([42 => $pin]);
      $board->analogWrite(42, 0);
    }

    /**
     * @covers \Carica\Firmata\Board::analogWrite
     */
    public function testAnalogWriteWithLargeValue() {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with(
          [
            Board::START_SYSEX,
            Board::EXTENDED_ANALOG,
            0x03,
            0x20,
            0x0D,
            0x06,
            Board::END_SYSEX
          ]
        );
      $pin = $this
        ->getMockBuilder(Pin::class)
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('setValue')
        ->with(100000);

      $board = new Board($stream);
      $board->pins = $this->getPinsFixture([3 => $pin]);
      $board->analogWrite(3, 100000);
    }

    /**
     * @covers \Carica\Firmata\Board::servoWrite
     */
    public function testServoWrite() {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([0xE3, 0x17, 0x00]);
      $pin = $this
        ->getMockBuilder(Pin::class)
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('setValue')
        ->with(23);

      $board = new Board($stream);
      $board->pins = $this->getPinsFixture([3 => $pin]);
      $board->servoWrite(3, 23);
    }

    /**
     * @covers \Carica\Firmata\Board::digitalWrite
     */
    public function testDigitalWrite() {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([0x91, 0x0B, 0x00]);
      $pin = $this->getPinFixture(
        ['pin' => 8, 'mode' => Pin::MODE_OUTPUT, 'digital' => TRUE]
      );
      $pin
        ->expects($this->once())
        ->method('setDigital')
        ->with(Board::DIGITAL_HIGH);

      $board = new Board($stream);
      $board->pins = $this->getPinsFixture(
        [
          8 => $pin,
          9 => $this->getPinFixture(
            ['pin' => 9, 'mode' => Pin::MODE_OUTPUT, 'digital' => TRUE]
          ),
          10 => $this->getPinFixture(
            ['pin' => 10, 'mode' => Pin::MODE_OUTPUT, 'digital' => FALSE]
          ),
          11 => $this->getPinFixture(
            ['pin' => 11, 'mode' => Pin::MODE_OUTPUT, 'digital' => TRUE]
          ),
          24 => $this->getPinFixture(
            ['pin' => 24, 'mode' => Pin::MODE_OUTPUT, 'digital' => TRUE]
          )
        ]
      );
      $board->digitalWrite(8, Board::DIGITAL_HIGH);
    }

    /**
     * @covers \Carica\Firmata\Board::pinMode
     */
    public function testPinMode() {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::PIN_MODE, 0x2A, 0x01]);
      $pin = $this
        ->getMockBuilder(Pin::class)
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('setMode')
        ->with(Pin::MODE_OUTPUT);

      $board = new Board($stream);
      $board->pins = $this->getPinsFixture([42 => $pin]);
      $board->pinMode(42, Pin::MODE_OUTPUT);
    }

    /****************************
     * Fixtures
     ***************************/

    /**
     * @param Emitter $events
     * @return \PHPUnit_Framework_MockObject_MockObject|Stream
     */
    private function getStreamFixture(Emitter $events = NULL) {
      $stream = $this->getMockBuilder(Stream::class)->getMock();
      $stream
        ->expects($this->any())
        ->method('events')
        ->will(
          $this->returnValue($events ?: new Emitter())
        );
      return $stream;
    }

    private function getPinFixture($data = []) {
      $pin = $this
        ->getMockBuilder(Pin::class)
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

    private function getPinsFixture(array $pins = []) {
      $result = $this
        ->getMockBuilder(Pins::class)
        ->disableOriginalConstructor()
        ->getMock();
      $result
        ->expects($this->any())
        ->method('offsetExists')
        ->will(
          $this->returnCallback(
            function($pinNumber) use ($pins) { return isset($pins[$pinNumber]); }
          )
        );
      $result
        ->expects($this->any())
        ->method('offsetGet')
        ->will(
          $this->returnCallback(
            function($pinNumber) use ($pins) { return $pins[$pinNumber]; }
          )
        );
      $result
        ->expects($this->any())
        ->method('getIterator')
        ->will(
          $this->returnValue(
            new \ArrayIterator($pins)
          )
        );
      return $result;
    }
  }
}
