<?php

namespace Carica\Firmata {

  include_once(__DIR__ . '/Bootstrap.php');

  class PinsTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Pins::__construct
     */
    public function testConstructor() {
      $pins = new Pins(
        $this->getBoardFixture(),
        array(42 => array(Pin::MODE_OUTPUT))
      );
      $this->assertCount(1, $pins);
    }

    /**
     * @covers Carica\Firmata\Pins::setAnalogMapping
     * @covers Carica\Firmata\Pins::getPinByChannel
     */
    public function testAnalogMappingWithValidChannel() {
      $pins = new Pins(
        $this->getBoardFixture(),
        array(42 => array(Pin::MODE_ANALOG))
      );
      $pins->setAnalogMapping(array(21 => 42));
      $this->assertEquals(42, $pins->getPinByChannel(21));
    }

    /**
     * @covers Carica\Firmata\Pins::setAnalogMapping
     * @covers Carica\Firmata\Pins::getPinByChannel
     */
    public function testAnalogMappingWithInvalidChannelExpectingNegativeOne() {
      $pins = new Pins(
        $this->getBoardFixture(),
        array(42 => array(Pin::MODE_ANALOG))
      );
      $pins->setAnalogMapping(array(21 => 42));
      $this->assertEquals(-1, $pins->getPinByChannel(23));
    }

    /**
     * @covers Carica\Firmata\Pins::getIterator
     */
    public function testIterator() {
      $pins = new Pins(
        $board = $this->getBoardFixture(),
        array(42 => array(Pin::MODE_OUTPUT))
      );
      $this->assertEquals(
        array(42 => new Pin($board, 42, array(Pin::MODE_OUTPUT))),
        iterator_to_array($pins)
      );
    }

    /**
     * @covers Carica\Firmata\Pins::count
     */
    public function testCountableExpectingZero() {
      $pins = new Pins(
        $board = $this->getBoardFixture(),
        []
      );
      $this->assertCount(0, $pins);
    }

    /**
     * @covers Carica\Firmata\Pins::count
     */
    public function testCountableExpectingTwo() {
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
     * @covers Carica\Firmata\Pins::offsetExists
     */
    public function testArrayAccessOffsetExistsExpectingTrue() {
      $pins = new Pins(
        $board = $this->getBoardFixture(),
        array(42 => array(Pin::MODE_OUTPUT))
      );
      $this->assertTrue(isset($pins[42]));
    }

    /**
     * @covers Carica\Firmata\Pins::offsetExists
     */
    public function testArrayAccessOffsetExistsExpectingFalse() {
      $pins = new Pins(
        $board = $this->getBoardFixture(),
        array(42 => array(Pin::MODE_OUTPUT))
      );
      $this->assertFalse(isset($pins[23]));
    }

    /**
     * @covers Carica\Firmata\Pins::offsetGet
     */
    public function testArrayAccessOffsetGet() {
      $pins = new Pins(
        $board = $this->getBoardFixture(),
        array(42 => array(Pin::MODE_OUTPUT))
      );
      $this->assertInstanceOf('Carica\\Firmata\\Pin', $pins[42]);
    }

    /**
     * @covers Carica\Firmata\Pins::offsetGet
     */
    public function testArrayAccessOffsetGetWithInvalidOffsetExpectingException() {
      $pins = new Pins(
        $this->getBoardFixture(), array()
      );
      $this->setExpectedException(
        'Carica\\Firmata\\Exception\\NonExistingPin'
      );
      $pins[42];
    }

    /**
     * @covers Carica\Firmata\Pins::offsetSet
     */
    public function testArrayAccessOffsetSetExpectingException() {
      $pins = new Pins(
        $this->getBoardFixture(), array()
      );
      $this->setExpectedException(
        'LogicException'
      );
      $pins[] = '';
    }

    /**
     * @covers Carica\Firmata\Pins::offsetUnset
     */
    public function testArrayAccessOffsetUnsetExpectingException() {
      $pins = new Pins(
        $this->getBoardFixture(), array()
      );
      $this->setExpectedException(
        'LogicException'
      );
      unset($pins[42]);
    }

    /*****************
     * Fixtures
     *****************/

    private function getBoardFixture() {
      $board = $this
        ->getMockBuilder('Carica\\Firmata\\Board')
        ->disableOriginalConstructor()
        ->getMock();
      return $board;
    }
  }
}