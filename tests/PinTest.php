<?php

namespace Carica\Firmata {

  use Carica\Io\Event\Emitter;
  use Carica\Io\Stream;

  include_once(__DIR__ . '/Bootstrap.php');

  class PinTest extends \PHPUnit\Framework\TestCase {

    /**
     * @covers \Carica\Firmata\Pin::__construct
     * @covers \Carica\Firmata\Pin::attachEvents
     */
    public function testConstructor() {
      $board = $this->getBoardFixture();
      $pin = new Pin($board, 12, array(Pin::MODE_OUTPUT => 1));
      $this->assertSame($board, $pin->board);
      $this->assertEquals(12, $pin->pin);
      $this->assertEquals(array(Pin::MODE_OUTPUT => 1), $pin->supports);
    }

    /**
     * @covers \Carica\Firmata\Pin::__isset
     * @dataProvider providePinProperties
     * @param string $propertyName
     */
    public function testPropertyIsset($propertyName) {
      $pin = new Pin($this->getBoardFixture(), 12, array(Pin::MODE_OUTPUT => 1));
      $this->assertTrue(isset($pin->{$propertyName}));
    }

    /**
     * @covers \Carica\Firmata\Pin::__isset
     */
    public function testPropertyIssetWithInvalidPropertyExpectingFalse() {
      $pin = new Pin($this->getBoardFixture(), 12, array(Pin::MODE_OUTPUT => 1));
      $this->assertFalse(isset($pin->INVALID_PROPERTY));
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     */
    public function testSetInvalidPropertyExpectingException() {
      $pin = new Pin($this->getBoardFixture(), 12, array(Pin::MODE_OUTPUT => 1));
      $this->expectException(\LogicException::class);
      $pin->INVALID_PROPERTY = 'trigger';
    }

    /**
     * @covers \Carica\Firmata\Pin::__get
     */
    public function testGetInvalidPropertyExpectingException() {
      $pin = new Pin($this->getBoardFixture(), 12, array(Pin::MODE_OUTPUT => 1));
      $this->expectException(\LogicException::class);
      $pin->INVALID_PROPERTY;
    }

    /**
     * @covers \Carica\Firmata\Pin::__get
     */
    public function testGetBoard() {
      $board = $this->getBoardFixture();
      $pin = new Pin($board, 12, array(Pin::MODE_OUTPUT => 1));
      $this->assertSame($board, $pin->board);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     */
    public function testSetBoardExpectingException() {
      $board = $this->getBoardFixture();
      $pin = new Pin($board, 12, array(Pin::MODE_OUTPUT => 1));
      $this->expectException(\LogicException::class);
      $pin->board = $board;
    }

    /**
     * @covers \Carica\Firmata\Pin::__get
     */
    public function testGetPin() {
      $pin = new Pin($this->getBoardFixture(), 12, array(Pin::MODE_OUTPUT => 1));
      $this->assertEquals(12, $pin->pin);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     */
    public function testSetPinExpectingException() {
      $pin = new Pin($this->getBoardFixture(), 12, array(Pin::MODE_OUTPUT => 1));
      $this->expectException(\LogicException::class);
      $pin->pin = 13;
    }

    /**
     * @covers \Carica\Firmata\Pin::__get
     */
    public function testGetSupports() {
      $pin = new Pin(
        $this->getBoardFixture(), 12, array(Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023)
      );
      $this->assertEquals(array(Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023), $pin->supports);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     */
    public function testSetSupportsExpectingException() {
      $pin = new Pin($this->getBoardFixture(), 12, array(Pin::MODE_OUTPUT => 1));
      $this->expectException(\LogicException::class);
      $pin->supports = array();
    }

    /**
     * @covers \Carica\Firmata\Pin::__get
     */
    public function testGetMode() {
      $pin = new Pin($this->getBoardFixture(), 12, array(Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023));
      $this->assertEquals(array(Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023), $pin->supports);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::__get
     * @covers \Carica\Firmata\Pin::setMode
     */
    public function testSetMode() {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->once())
        ->method('pinMode')
        ->with(12, Pin::MODE_OUTPUT);

      $pin = new Pin($board, 12, array(Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023));
      $pin->mode = Pin::MODE_OUTPUT;
      $this->assertEquals(Pin::MODE_OUTPUT, $pin->mode);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::__get
     * @covers \Carica\Firmata\Pin::setMode
     */
    public function testSetModeTwoTimeOnlySentOneTime() {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->once())
        ->method('pinMode')
        ->with(12, Pin::MODE_ANALOG);

      $pin = new Pin($board, 12, array(Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023));
      $pin->mode = Pin::MODE_ANALOG;
      $pin->mode = Pin::MODE_ANALOG;
      $this->assertEquals(Pin::MODE_ANALOG, $pin->mode);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::setMode
     */
    public function testSetModeWithUnsupportedModeExpectingException() {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->never())
        ->method('pinMode');

      $pin = new Pin($board, 12, array(Pin::MODE_OUTPUT => 1));
      $this->expectException(Exception\UnsupportedMode::class);
      $pin->mode = Pin::MODE_ANALOG;
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::setDigital
     */
    public function testSetAnalogValue() {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->once())
        ->method('analogWrite')
        ->with(12, 128);

      $pin = new Pin($board, 12, array(Pin::MODE_PWM => 255));
      $pin->analog = 0.5;
      $pin->analog = 0.5;
      $this->assertEquals(0.5, $pin->analog, '', 0.01);
      $this->assertEquals(128, $pin->value);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::setDigital
     */
    public function testSetAnalogValueWithNegativeExpectingMinimum() {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->once())
        ->method('analogWrite')
        ->with(12, 0);

      $pin = new Pin($board, 12, array(Pin::MODE_PWM => 255));
      $pin->analog = -0.5;
      $this->assertEquals(0, $pin->analog, '', 0.01);
      $this->assertEquals(0, $pin->value);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::setDigital
     */
    public function testSetAnalogValueWithTooLargeValueExpectingMaximum() {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->once())
        ->method('analogWrite')
        ->with(12, 255);

      $pin = new Pin($board, 12, array(Pin::MODE_PWM => 255));
      $pin->analog = 99;
      $this->assertEquals(1, $pin->analog, '', 0.01);
      $this->assertEquals(255, $pin->value);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::setDigital
     */
    public function testSetDigitalValue() {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->once())
        ->method('digitalWrite')
        ->with(12, Board::DIGITAL_HIGH);

      $pin = new Pin($board, 12, array(Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023));
      $pin->digital = TRUE;
      $pin->digital = TRUE;
      $this->assertTrue($pin->digital);
      $this->assertEquals(Board::DIGITAL_HIGH, $pin->value);
    }

    /**
     * @covers \Carica\Firmata\Pin::__set
     * @covers \Carica\Firmata\Pin::setValue
     */
    public function testSetValue() {
      $board = $this->getBoardFixture();
      $board
        ->expects($this->once())
        ->method('analogWrite')
        ->with(12, 128);

      $pin = new Pin($board, 12, array(Pin::MODE_PWM => 255));
      $pin->value = 128;
      $pin->value = 128;
      $this->assertEquals(128, $pin->value);
    }

    /**
     * @covers \Carica\Firmata\Pin
     */
    public function testEventPinState() {
      $board = new Board($this->getStreamFixture());
      $pin = new Pin($board, 12, array(Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023));
      $board->events()->emit('pin-state-12', Pin::MODE_ANALOG, 255);
      $this->assertEquals(Pin::MODE_ANALOG, $pin->mode);
      $this->assertEquals(0.25, $pin->analog, '', 0.01);
    }

    /**
     * @covers \Carica\Firmata\Pin
     */
    public function testEventAnalogRead() {
      $board = new Board($this->getStreamFixture());
      $pin = new Pin($board, 12, array(Pin::MODE_ANALOG => 1023));
      $board->events()->emit('analog-read-12', 512);
      $this->assertEquals(0.5, $pin->analog, '', 0.01);
    }

    /**
     * @covers \Carica\Firmata\Pin
     */
    public function testEventDigitalRead() {
      $board = new Board($this->getStreamFixture());
      $pin = new Pin($board, 12, array(Pin::MODE_ANALOG => 1023));
      $board->events()->emit('digital-read-12', TRUE);
      $this->assertTrue($pin->digital);
    }

    /**
     * @covers \Carica\Firmata\Pin
     */
    public function testSupportsExpectingTrue() {
      $board = $this->getBoardFixture();
      $pin = new Pin($board, 12, array(Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023));
      $this->assertTrue(
        $pin->supports(Pin::MODE_ANALOG)
      );
    }

    /**
     * @covers \Carica\Firmata\Pin
     */
    public function testSupportsExpectingFalse() {
      $board = $this->getBoardFixture();
      $pin = new Pin($board, 12, array(Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023));
      $this->assertFalse(
        $pin->supports(Pin::MODE_PWM)
      );
    }
    
    /**
     * @covers \Carica\Firmata\Pin
     */
    public function testPinOnChange() {
      $board = $this->getBoardFixture();
      $pin = new Pin($board, 12, array(Pin::MODE_OUTPUT => 1, Pin::MODE_ANALOG => 1023));
      $called = FALSE;
      $pin->onChange(
        $cb = function() use (&$called) {
          $called = TRUE;
        }
      );
      $pin->events()->emit('change');
      $this->assertTrue($called);
    }

    /*****************
     * Fixtures
     ****************/

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

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Board
     */
    private function getBoardFixture() {
      $board = $this
        ->getMockBuilder(Board::class)
        ->disableOriginalConstructor()
        ->getMock();
      $board
        ->expects($this->any())
        ->method('events')
        ->will($this->returnValue($this->getMockBuilder(Emitter::class)->getMock()));
      return $board;
    }

    /*****************
     * Data Provider
     *****************/

    public static function providePinProperties() {
      return array(
        array('board'),
        array('pin'),
        array('supports'),
        array('mode'),
        array('value'),
        array('digital'),
        array('analog')
      );
    }
  }
}
