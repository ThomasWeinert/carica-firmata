<?php
/** @noinspection PhpUndefinedFieldInspection */

/** @noinspection SuspiciousAssignmentsInspection */

namespace Carica\Firmata {

  use Carica\Io\Event\Emitter as EventEmitter;
  use Carica\Io\Stream;
  use LogicException;
  use PHPUnit\Framework\MockObject\MockObject;
  use PHPUnit\Framework\TestCase;

  include_once(__DIR__.'/Bootstrap.php');

  class PinTest extends TestCase {

    /**
     * @covers \Carica\Firmata\Pin::__construct
     * @covers \Carica\Firmata\Pin::attachEvents
     */
    public function testConstructor(): void {
      $board = $this->getBoardFixture();
      $pin = new Pin($board, 12, [Pin::MODE_OUTPUT => 1]);
      $this->assertSame($board, $pin->board);
      $this->assertEquals(12, $pin->pin);
      $this->assertEquals([Pin::MODE_OUTPUT => 1], $pin->supports);
    }

    /**
     * @covers       \Carica\Firmata\Pin::__isset
     * @dataProvider providePinProperties
     * @param string $propertyName
     */
    public function testPropertyIsset($propertyName): void {
      $pin = new Pin($this->getBoardFixture(), 12, [Pin::MODE_OUTPUT => 1]);
      $this->assertTrue(isset($pin->{$propertyName}));
    }

    /**
     * @covers \Carica\Firmata\Pin::__isset
     */
    public function testPropertyIssetWithInvalidPropertyExpectingFalse(): void {
      $pin = new Pin($this->getBoardFixture(), 12, [Pin::MODE_OUTPUT => 1]);
      $this->assertFalse(isset($pin->INVALID_PROPERTY));
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     */
    public function testSetInvalidPropertyExpectingException(): void {
      $pin = new Pin($this->getBoardFixture(), 12, [Pin::MODE_OUTPUT => 1]);
      $this->expectException(LogicException::class);
      $pin->INVALID_PROPERTY = 'trigger';
    }

    /**
     * @covers \Carica\Firmata\Pin::__get
     */
    public function testGetInvalidPropertyExpectingException(): void {
      $pin = new Pin($this->getBoardFixture(), 12, [Pin::MODE_OUTPUT => 1]);
      $this->expectException(LogicException::class);
      $pin->INVALID_PROPERTY;
    }

    /**
     * @covers \Carica\Firmata\Pin::__get
     */
    public function testGetBoard(): void {
      $board = $this->getBoardFixture();
      $pin = new Pin($board, 12, [Pin::MODE_OUTPUT => 1]);
      $this->assertSame($board, $pin->board);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     */
    public function testSetBoardExpectingException(): void {
      $board = $this->getBoardFixture();
      $pin = new Pin($board, 12, [Pin::MODE_OUTPUT => 1]);
      $this->expectException(LogicException::class);
      $pin->board = $board;
    }

    /**
     * @covers \Carica\Firmata\Pin::__get
     */
    public function testGetPin(): void {
      $pin = new Pin($this->getBoardFixture(), 12, [Pin::MODE_OUTPUT => 1]);
      $this->assertEquals(12, $pin->pin);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     */
    public function testSetPinExpectingException(): void {
      $pin = new Pin($this->getBoardFixture(), 12, [Pin::MODE_OUTPUT => 1]);
      $this->expectException(LogicException::class);
      $pin->pin = 13;
    }

    /**
     * @covers \Carica\Firmata\Pin::__get
     */
    public function testGetSupports(): void {
      $pin = new Pin(
        $this->getBoardFixture(), 12, [Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023]
      );
      $this->assertEquals([Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023], $pin->supports);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     */
    public function testSetSupportsExpectingException(): void {
      $pin = new Pin($this->getBoardFixture(), 12, [Pin::MODE_OUTPUT => 1]);
      $this->expectException(LogicException::class);
      $pin->supports = [];
    }

    /**
     * @covers \Carica\Firmata\Pin::__get
     */
    public function testGetMode(): void {
      $pin = new Pin($this->getBoardFixture(), 12, [Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023]);
      $this->assertEquals([Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023], $pin->supports);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::__get
     * @covers \Carica\Firmata\Pin::setMode
     */
    public function testSetMode(): void {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->once())
        ->method('pinMode')
        ->with(12, Pin::MODE_OUTPUT);

      $pin = new Pin($board, 12, [Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023]);
      $pin->mode = Pin::MODE_OUTPUT;
      $this->assertEquals(Pin::MODE_OUTPUT, $pin->mode);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::__get
     * @covers \Carica\Firmata\Pin::setMode
     */
    public function testSetModeTwoTimeOnlySentOneTime(): void {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->once())
        ->method('pinMode')
        ->with(12, Pin::MODE_ANALOG);

      $pin = new Pin($board, 12, [Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023]);
      $pin->mode = Pin::MODE_ANALOG;
      $pin->mode = Pin::MODE_ANALOG;
      $this->assertEquals(Pin::MODE_ANALOG, $pin->mode);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::setMode
     */
    public function testSetModeWithUnsupportedModeExpectingException(): void {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->never())
        ->method('pinMode');

      $pin = new Pin($board, 12, [Pin::MODE_OUTPUT => 1]);
      $this->expectException(Exception\UnsupportedMode::class);
      $pin->mode = Pin::MODE_ANALOG;
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::setDigital
     */
    public function testSetAnalogValue(): void {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->once())
        ->method('analogWrite')
        ->with(12, 128);

      $pin = new Pin($board, 12, [Pin::MODE_PWM => 255]);
      $pin->analog = 0.5;
      $pin->analog = 0.5;
      $this->assertEqualsWithDelta(0.5, $pin->analog, 0.01);
      $this->assertEquals(128, $pin->value);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::setDigital
     */
    public function testSetAnalogValueWithNegativeExpectingMinimum(): void {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->once())
        ->method('analogWrite')
        ->with(12, 0);

      $pin = new Pin($board, 12, [Pin::MODE_PWM => 255]);
      $pin->analog = -0.5;
      $this->assertEqualsWithDelta(0, $pin->analog, 0.01);
      $this->assertEquals(0, $pin->value);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::setDigital
     */
    public function testSetAnalogValueWithTooLargeValueExpectingMaximum(): void {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->once())
        ->method('analogWrite')
        ->with(12, 255);

      $pin = new Pin($board, 12, [Pin::MODE_PWM => 255]);
      $pin->analog = 99;
      $this->assertEqualsWithDelta(1, $pin->analog, 0.01);
      $this->assertEquals(255, $pin->value);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::setDigital
     */
    public function testSetDigitalValue(): void {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->once())
        ->method('digitalWrite')
        ->with(12, Board::DIGITAL_HIGH);

      $pin = new Pin($board, 12, [Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023]);
      $pin->digital = TRUE;
      $pin->digital = TRUE;
      $this->assertTrue($pin->digital);
      $this->assertEquals(Board::DIGITAL_HIGH, $pin->value);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::setValue
     */
    public function testSetValue(): void {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->once())
        ->method('analogWrite')
        ->with(12, 128);

      $pin = new Pin($board, 12, [Pin::MODE_PWM => 255]);
      $pin->value = 128;
      $pin->value = 128;
      $this->assertEquals(128, $pin->value);
    }

    /**
     * @covers \Carica\Firmata\Pin
     */
    public function testEventPinState(): void {
      $board = new Board($this->getStreamFixture());
      $pin = new Pin($board, 12, [Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023]);
      $board->events()->emit('pin-state-12', Pin::MODE_ANALOG, 255);
      $this->assertEquals(Pin::MODE_ANALOG, $pin->mode);
      $this->assertEqualsWithDelta(0.25, $pin->analog, 0.01);
    }

    /**
     * @covers \Carica\Firmata\Pin
     */
    public function testEventAnalogRead(): void {
      $board = new Board($this->getStreamFixture());
      $pin = new Pin($board, 12, [Pin::MODE_ANALOG => 1023]);
      $board->events()->emit('analog-read-12', 512);
      $this->assertEqualsWithDelta(0.5, $pin->analog, 0.01);
    }

    /**
     * @covers \Carica\Firmata\Pin
     */
    public function testEventDigitalRead(): void {
      $board = new Board($this->getStreamFixture());
      $pin = new Pin($board, 12, [Pin::MODE_ANALOG => 1023]);
      $board->events()->emit('digital-read-12', TRUE);
      $this->assertTrue($pin->digital);
    }

    /**
     * @covers \Carica\Firmata\Pin
     */
    public function testSupportsExpectingTrue(): void {
      $board = $this->getBoardFixture();
      $pin = new Pin($board, 12, [Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023]);
      $this->assertTrue(
        $pin->supports(Pin::MODE_ANALOG)
      );
    }

    /**
     * @covers \Carica\Firmata\Pin
     */
    public function testSupportsExpectingFalse(): void {
      $board = $this->getBoardFixture();
      $pin = new Pin($board, 12, [Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023]);
      $this->assertFalse(
        $pin->supports(Pin::MODE_PWM)
      );
    }

    /**
     * @covers \Carica\Firmata\Pin
     */
    public function testPinOnChange(): void {
      $board = $this->getBoardFixture();
      $pin = new Pin($board, 12, [Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023]);
      $called = FALSE;
      $pin->onChange(
        static function () use (&$called) {
          $called = TRUE;
        }
      );
      $pin->events()->emit(Pin::EVENT_CHANGE);
      $this->assertTrue($called);
    }

    /*****************
     * Fixtures
     ****************/

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
     * @return MockObject|Board
     */
    private function getBoardFixture(): MockObject {
      $board = $this
        ->getMockBuilder(Board::class)
        ->disableOriginalConstructor()
        ->getMock();
      $board
        ->method('events')
        ->willReturn($this->getMockBuilder(EventEmitter::class)->getMock());
      return $board;
    }

    /*****************
     * Data Provider
     *****************/

    public static function providePinProperties(): array {
      return [
        ['board'],
        ['pin'],
        ['supports'],
        ['mode'],
        ['value'],
        ['digital'],
        ['analog']
      ];
    }
  }
}
