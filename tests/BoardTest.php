<?php

namespace Carica\Firmata {

  use ArrayIterator;
  use Carica\Io\Deferred;
  use Carica\Io\Deferred\Promise;
  use Carica\Io\Event\Emitter as EventEmitter;
  use Carica\Io\Stream;
  use PHPUnit\Framework\MockObject\MockObject;
  use PHPUnit\Framework\TestCase;

  include_once(__DIR__ . '/Bootstrap.php');

  class BoardTest extends TestCase {

    /**
     * @covers \Carica\Firmata\Board::__construct
     * @covers \Carica\Firmata\Board::stream
     */
    public function testConstructor(): void {
      /** @var MockObject|EventEmitter $events */
      $events = $this->getMockBuilder(EventEmitter::class)->getMock();
      $events
        ->expects($this->once())
        ->method('on')
        ->with(Stream::EVENT_READ_DATA, $this->isInstanceOf('Closure'));
      $board = new Board($stream = $this->getStreamFixture($events));
      $this->assertSame($stream, $board->stream());
    }

    /**
     * @covers \Carica\Firmata\Board::isActive
     */
    public function testIsActiveExpectingFalse(): void {
      $stream = $this->getStreamFixture();
      $board = new Board($stream);
      $this->assertFalse($board->isActive());
    }

    /**
     * @covers \Carica\Firmata\Board::__get
     * @covers \Carica\Firmata\Board::pins
     */
    public function testGetPropertyPinsImplicitCreate(): void {
      $board = new Board($this->getStreamFixture());
      $this->assertInstanceOf(Pins::class, $board->pins);
    }

    /**
     * @covers \Carica\Firmata\Board::__get
     * @covers \Carica\Firmata\Board::__set
     * @covers \Carica\Firmata\Board::pins
     */
    public function testGetPropertyPinsAfterSet(): void {
      /** @var MockObject|Pins $pins */
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
    public function testGetPropertyVersion(): void {
      $board = new Board($this->getStreamFixture());
      $this->assertInstanceOf(Version::class, $board->version);
    }

    /**
     * @covers \Carica\Firmata\Board::__get
     */
    public function testGetPropertyFirmware(): void {
      $board = new Board($this->getStreamFixture());
      $this->assertInstanceOf(Version::class, $board->firmware);
    }

    /**
     * @covers \Carica\Firmata\Board::__get
     */
    public function testGetPropertyWithUnknownPropertyName(): void {
      $board = new Board($this->getStreamFixture());
      $this->expectException(\LogicException::class);
      $board->INVALID_PROPERTY;
    }

    /**
     * @covers \Carica\Firmata\Board::__set
     */
    public function testSetPropertyVersionExpectingException(): void {
      $board = new Board($this->getStreamFixture());
      $this->expectException(\LogicException::class);
      $board->version = 'trigger';
    }

    /**
     * @covers \Carica\Firmata\Board::__set
     */
    public function testSetPropertyFirmwareExpectingException(): void {
      $board = new Board($this->getStreamFixture());
      $this->expectException(\LogicException::class);
      $board->firmware = 'trigger';
    }

    /**
     * @covers \Carica\Firmata\Board::__set
     */
    public function testSetPropertyWithUnknownPropertyName(): void {
      $board = new Board($this->getStreamFixture());
      $this->expectException(\LogicException::class);
      $board->INVALID_PROPERTY = 'trigger';
    }

    /**
     * @covers \Carica\Firmata\Board::activate
     */
    public function testActivateStreamOpenReturnsFalse(): void {
      $events = $this->getMockBuilder(EventEmitter::class)->getMock();
      $events
        ->expects($this->once())
        ->method('once')
        ->with('error', $this->isInstanceOf('Closure'));

      /** @var MockObject|Stream\TCPStream $stream */
      $stream = $this->createMock(Stream\TCPStream::class);
      $stream
        ->method('events')
        ->willReturn($events);
      $stream
        ->expects($this->once())
        ->method('open')
        ->willReturn(FALSE);

      $board = new Board($stream);
      $promise = $board->activate(static function(){});
      $this->assertInstanceOf(Promise::class, $promise);
    }

    /**
     * @covers \Carica\Firmata\Board::activate
     */
    public function testActivateStreamErrorRejectsPromise(): void {
      $events = new EventEmitter();

      /** @var MockObject|Stream\TCPStream $stream */
      $stream = $this->createMock(Stream\TCPStream::class);
      $stream
        ->method('events')
        ->willReturn($events);
      $stream
        ->expects($this->once())
        ->method('open')
        ->willReturn(FALSE);

      $board = new Board($stream);

      $result = '';
      $promise = $board->activate();
      $promise->fail(
        static function ($message) use (&$result) {
          $result = $message;
        }
      );
      $events->emit('error', 'STREAM_ERROR');
      $this->assertEquals('STREAM_ERROR', $result);
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testActivateWithSimpleStartUp(): void {
      $events = new EventEmitter();

      /** @var MockObject|Stream\TCPStream $stream */
      $stream = $this->createMock(Stream\TCPStream::class);
      $stream
        ->method('events')
        ->willReturn($events);
      $stream
        ->expects($this->once())
        ->method('open')
        ->willReturn(TRUE);

      $board = new Board($stream);
      $promise = $board->activate();
      $board->stream()->events()->emit(
        Stream::EVENT_READ_DATA,
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
      $this->assertEquals(Deferred::STATE_RESOLVED, $promise->state());
      $this->assertEquals('3.2', (string)$board->version);
      $this->assertEquals('Sample 2.3', (string)$board->firmware);
      $this->assertCount(2, $board->pins);
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testVersionResponse(): void {
      $board = new Board($this->getStreamFixture());
      $board->stream()->events()->emit(
        Stream::EVENT_READ_DATA, "\xF9\x03\x02"
      );
      $this->assertEquals(
        '3.2', (string)$board->version
      );
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testStringResponse(): void {
      $board = new Board($this->getStreamFixture());
      $board->stream()->events()->emit(Stream::EVENT_READ_DATA, "\xF9\x03\x02");
      $result = NULL;
      $board->events()->on(
        Board::EVENT_RECIEVE_STRING,
        static function($string) use (&$result) {
          $result = $string;
        }
      );
      $board->stream()->events()->emit(
        Stream::EVENT_READ_DATA,
        "\xF0\x71\x48\x00\x61\x00\x6C\x00\x6C\x00\x6F\x00\xF7"
      );
      $this->assertEquals('Hallo', $result);
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testI2CResponseExpectingResponseEvent(): void {
      $board = new Board($this->getStreamFixture());
      $board->stream()->events()->emit(Stream::EVENT_READ_DATA, "\xF9\x03\x02");
      $result = NULL;
      $board->events()->on(
        Board::EVENT_RESPONSE,
        static function(Response $response) use (&$result) {
          $result = $response;
        }
      );
      $board->stream()->events()->emit(
        Stream::EVENT_READ_DATA,
        "\xF0\x77\x01\x00\x02\x00\x48\x00\x61\x00\x6C\x00\x6C\x00\x6F\x00\xF7"
      );
      $this->assertInstanceOf(Response::class, $result);
      /** @var Response $result */
      $this->assertEquals(I2C::REPLY, $result->getCommand());
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testQueryFirmwareResponse(): void {
      $board = new Board($this->getStreamFixture());
      $board->stream()->events()->emit(Stream::EVENT_READ_DATA, "\xF9\x03\x02");
      $board->stream()->events()->emit(
        Stream::EVENT_READ_DATA,
        "\xF0\x79\x02\x03\x53\x00\x61\x00\x6D\x00\x70\x00\x6C\x00\x65\x00\xF7"
      );
      $this->assertEquals('Sample 2.3', (string)$board->firmware);
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testCapabilityResponse(): void {
      $board = new Board($this->getStreamFixture());
      $board->stream()->events()->emit(Stream::EVENT_READ_DATA, "\xF9\x03\x02");
      $board->stream()->events()->emit(
        Stream::EVENT_READ_DATA,
        "\xF0\x6C\x7F\x7F\x00\x01\x01\x01\x02\x00\xF7"
      );
      $this->assertInstanceOf(Pin::class, $board->pins[2]);
      $this->assertTrue($board->pins[2]->supports(Pin::MODE_ANALOG));
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testAnalogMappingResponse(): void {
      $board = new Board($this->getStreamFixture());
      $board->stream()->events()->emit(Stream::EVENT_READ_DATA, "\xF9\x03\x02");
      $board->stream()->events()->emit(
        Stream::EVENT_READ_DATA, "\xF0\x6C\x7F\x7F\x00\x01\x01\x01\x02\x00\xF7"
      );
      $board->stream()->events()->emit(Stream::EVENT_READ_DATA, "\xF0\x6A\x7F\x7F\x01\x03\xF7");
      $this->assertEquals(2, $board->pins->getPinByChannel(1));
      $this->assertEquals(3, $board->pins->getPinByChannel(3));
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testPinStateResponse(): void {
      $board = new Board($this->getStreamFixture());
      $board->stream()->events()->emit(Stream::EVENT_READ_DATA, "\xF9\x03\x02");
      $result = [];
      $board->events()->on(
        Board::EVENT_PIN_STATE,
        static function($pin, $mode, $value) use (&$result) {
          $result = [
            'pin' => $pin,
            'mode' => $mode,
            'value' => $value
          ];
        }
      );
      $board->stream()->events()->emit(Stream::EVENT_READ_DATA, "\xF0\x6E\x02\x02\x7F\xF7");
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
    public function testReset(): void {
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
    public function testReportVersion(): void {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::REPORT_VERSION]);
      $board = new Board($stream);
      $board->reportVersion(static function() {});
      $this->assertCount(
        1, $board->events()->listeners(Board::EVENT_REPORTVERSION)
      );
    }

    /**
     * @covers \Carica\Firmata\Board::queryFirmware
     */
    public function testQueryFirmware(): void {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::QUERY_FIRMWARE, Board::END_SYSEX]);
      $board = new Board($stream);
      $board->queryFirmware(static function() {});
      $this->assertCount(
         1,
         $board->events()->listeners(Board::EVENT_QUERYFIRMWARE)
      );
    }

    /**
     * @covers \Carica\Firmata\Board::queryCapabilities
     */
    public function testQueryCapabilities(): void {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::CAPABILITY_QUERY, Board::END_SYSEX]);
      $board = new Board($stream);
      $board->queryCapabilities(static function() {});
      $this->assertCount(
         1,
         $board->events()->listeners(Board::EVENT_CAPABILITY_QUERY)
      );
    }

    /**
     * @covers \Carica\Firmata\Board::queryAnalogMapping
     */
    public function testQueryAnalogMapping(): void {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::ANALOG_MAPPING_QUERY, Board::END_SYSEX]);
      $board = new Board($stream);
      $board->queryAnalogMapping(static function() {});
      $this->assertCount(
         1,
         $board->events()->listeners(Board::EVENT_ANALOG_MAPPING_QUERY)
      );
    }

    /**
     * @covers \Carica\Firmata\Board::queryPinState
     */
    public function testQueryPinState(): void {
      $stream = $this->getStreamFixture();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::PIN_STATE_QUERY, 0x2A, Board::END_SYSEX]);
      $board = new Board($stream);
      $board->queryPinState(42, static function() {});
      $this->assertCount(
         1,
         $board->events()->listeners(Board::EVENT_PIN_STATE.'-42')
      );
    }

    /**
     * @covers \Carica\Firmata\Board::queryAllPinStates
     */
    public function testQueryAllPinStates(): void {
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
    public function testAnalogRead(): void {
      $board = new Board($this->getStreamFixture());
      $board->analogRead(42, $callback = static function() {});
      $this->assertCount(
         1,
         $board->events()->listeners(Board::EVENT_ANALOG_READ.'-42')
      );
    }

    /**
     * @covers \Carica\Firmata\Board::digitalRead
     */
    public function testDigitalRead(): void {
      $board = new Board($this->getStreamFixture());
      $board->digitalRead(42, $callback = static function() {});
      $this->assertCount(
         1,
         $board->events()->listeners(Board::EVENT_DIGITAL_READ.'-42')
      );
    }

    /**
     * @covers \Carica\Firmata\Board
     */
    public function testAnalogWrite(): void {
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
    public function testAnalogWriteWithHighPinNumber(): void {
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
    public function testAnalogWriteWithLargeValue(): void {
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
    public function testServoWrite(): void {
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
    public function testDigitalWrite(): void {
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
     * @throws Exception\UnsupportedMode
     */
    public function testPinMode(): void {
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
     * @param EventEmitter $events
     * @return MockObject|Stream
     */
    private function getStreamFixture(EventEmitter $events = NULL): MockObject {
      $stream = $this->getMockBuilder(Stream::class)->getMock();
      $stream
        ->method('events')
        ->willReturn(
          $events ?: new EventEmitter()
        );
      return $stream;
    }

    /**
     * @param array $data
     * @return MockObject|Pin
     */
    private function getPinFixture($data = []): MockObject {
      $pin = $this
        ->getMockBuilder(Pin::class)
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->method('__get')
        ->willReturnMap(
          [
            ['pin', $data['pin'] ?? 0],
            ['mode', $data['mode'] ?? 0x00],
            ['digital', $data['digital'] ?? FALSE],
            ['analog', $data['analog'] ?? 0],
            ['value', $data['value'] ?? 0],
            ['supports', $data['supports'] ?? []],
          ]
        );
      return $pin;
    }

    /**
     * @param array $pins
     * @return MockObject|Pins
     */
    private function getPinsFixture(array $pins = []): MockObject {
      $result = $this
        ->getMockBuilder(Pins::class)
        ->disableOriginalConstructor()
        ->getMock();
      $result
        ->method('offsetExists')
        ->willReturnCallback(
          static function ($pinNumber) use ($pins) {
            return isset($pins[$pinNumber]);
          }
        );
      $result
        ->method('offsetGet')
        ->willReturnCallback(
          static function ($pinNumber) use ($pins) {
            return $pins[$pinNumber];
          }
        );
      $result
        ->method('getIterator')
        ->willReturn(
          new ArrayIterator($pins)
        );
      return $result;
    }
  }
}
