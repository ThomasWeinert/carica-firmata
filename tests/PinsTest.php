<?php

namespace Carica\Firmata {

  use Carica\Firmata\Exception\NonExistingPin;
  use LogicException;
  use PHPUnit\Framework\MockObject\MockObject;
  use PHPUnit\Framework\TestCase;

  include_once(__DIR__.'/Bootstrap.php');

  class PinsTest extends TestCase {

    /**
     * @covers \Carica\Firmata\Pins::__construct
     */
    public function testConstructor(): void {
      $pins = new Pins(
        $this->getBoardFixture(),
        [42 => [Pin::MODE_OUTPUT]]
      );
      $this->assertCount(1, $pins);
    }

    /**
     * @covers \Carica\Firmata\Pins::setAnalogMapping
     * @covers \Carica\Firmata\Pins::getPinByChannel
     */
    public function testAnalogMappingWithValidChannel(): void {
      $pins = new Pins(
        $this->getBoardFixture(),
        [42 => [Pin::MODE_ANALOG]]
      );
      $pins->setAnalogMapping([21 => 42]);
      $this->assertEquals(42, $pins->getPinByChannel(21));
    }

    /**
     * @covers \Carica\Firmata\Pins::setAnalogMapping
     * @covers \Carica\Firmata\Pins::getPinByChannel
     */
    public function testAnalogMappingWithInvalidChannelExpectingNegativeOne(): void {
      $pins = new Pins(
        $this->getBoardFixture(),
        [42 => [Pin::MODE_ANALOG]]
      );
      $pins->setAnalogMapping([21 => 42]);
      $this->assertEquals(-1, $pins->getPinByChannel(23));
    }

    /**
     * @covers \Carica\Firmata\Pins::getIterator
     */
    public function testIterator(): void {
      $pins = new Pins(
        $board = $this->getBoardFixture(),
        [42 => [Pin::MODE_OUTPUT]]
      );
      $this->assertEquals(
        [42 => new Pin($board, 42, [Pin::MODE_OUTPUT])],
        iterator_to_array($pins)
      );
    }

    /**
     * @covers \Carica\Firmata\Pins::count
     */
    public function testCountableExpectingZero(): void {
      $pins = new Pins(
        $board = $this->getBoardFixture(),
        []
      );
      $this->assertCount(0, $pins);
    }

    /**
     * @covers \Carica\Firmata\Pins::count
     */
    public function testCountableExpectingTwo(): void {
      $pins = new Pins(
        $board = $this->getBoardFixture(),
        [
          21 => [Pin::MODE_OUTPUT],
          42 => [Pin::MODE_OUTPUT]
        ]
      );
      $this->assertCount(2, $pins);
    }

    /**
     * @covers \Carica\Firmata\Pins::offsetExists
     */
    public function testArrayAccessOffsetExistsExpectingTrue(): void {
      $pins = new Pins(
        $board = $this->getBoardFixture(),
        [42 => [Pin::MODE_OUTPUT]]
      );
      $this->assertTrue(isset($pins[42]));
    }

    /**
     * @covers \Carica\Firmata\Pins::offsetExists
     */
    public function testArrayAccessOffsetExistsExpectingFalse(): void {
      $pins = new Pins(
        $board = $this->getBoardFixture(),
        [42 => [Pin::MODE_OUTPUT]]
      );
      $this->assertFalse(isset($pins[23]));
    }

    /**
     * @covers \Carica\Firmata\Pins::offsetGet
     */
    public function testArrayAccessOffsetGet(): void {
      $pins = new Pins(
        $board = $this->getBoardFixture(),
        [42 => [Pin::MODE_OUTPUT]]
      );
      $this->assertInstanceOf(Pin::class, $pins[42]);
    }

    /**
     * @covers \Carica\Firmata\Pins::offsetGet
     */
    public function testArrayAccessOffsetGetWithInvalidOffsetExpectingException(): void {
      $pins = new Pins(
        $this->getBoardFixture(), []
      );
      $this->expectException(
        NonExistingPin::class
      );
      $pins[42];
    }

    /**
     * @covers \Carica\Firmata\Pins::offsetSet
     */
    public function testArrayAccessOffsetSetExpectingException(): void {
      $pins = new Pins(
        $this->getBoardFixture(), []
      );
      $this->expectException(
        LogicException::class
      );
      /** @noinspection OnlyWritesOnParameterInspection */
      $pins[] = '';
    }

    /**
     * @covers \Carica\Firmata\Pins::offsetUnset
     */
    public function testArrayAccessOffsetUnsetExpectingException(): void {
      $pins = new Pins(
        $this->getBoardFixture(), []
      );
      $this->expectException(
        LogicException::class
      );
      unset($pins[42]);
    }

    /*****************
     * Fixtures
     *****************/

    /**
     * @return MockObject|Board
     */
    private function getBoardFixture() {
      $board = $this
        ->getMockBuilder(Board::class)
        ->disableOriginalConstructor()
        ->getMock();
      return $board;
    }
  }
}
