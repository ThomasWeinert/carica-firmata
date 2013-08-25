<?php

namespace Carica\Firmata {

  include_once(__DIR__.'/Bootstrap.php');

  class ResolutionsTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Resolutions
     */
    function testOffsetExistsExpectingTrue() {
      $resolutions = new Resolutions();
      $this->assertTrue(isset($resolutions[Board::PIN_STATE_ANALOG]));
    }

    /**
     * @covers Carica\Firmata\Resolutions
     */
    function testOffsetExistsExpectingFalse() {
      $resolutions = new Resolutions();
      $this->assertFalse(isset($resolutions['INVALID']));
    }

    /**
     * @covers Carica\Firmata\Resolutions
     */
    function testOffsetGet() {
      $resolutions = new Resolutions();
      $this->assertEquals(1023, $resolutions[Board::PIN_STATE_ANALOG]);
    }

    /**
     * @covers Carica\Firmata\Resolutions
     */
    function testOffsetGetWithInvalidModeExpectingOne() {
      $resolutions = new Resolutions();
      $this->assertEquals(1, $resolutions['INVALID']);
    }

    /**
     * @covers Carica\Firmata\Resolutions
     */
    function testOffsetGetAfterSet() {
      $resolutions = new Resolutions();
      $resolutions[Board::PIN_STATE_PWM] = 512;
      $this->assertEquals(512, $resolutions[Board::PIN_STATE_PWM]);
    }

    /**
     * @covers Carica\Firmata\Resolutions
     */
    function testOffsetSetWithInvalidModeExpectingException() {
      $resolutions = new Resolutions();
      $this->setExpectedException('UnexpectedValueException');
      $resolutions['INVALID'] = 512;
    }

    /**
     * @covers Carica\Firmata\Resolutions
     */
    function testOffsetUnsetExpectingException() {
      $resolutions = new Resolutions();
      $this->setExpectedException('UnexpectedValueException');
      unset($resolutions[Board::PIN_STATE_PWM]);
    }

    /**
     * @covers Carica\Firmata\Resolutions
     */
    function testCount() {
      $resolutions = new Resolutions();
      $this->assertCount(3, $resolutions);
    }

    /**
     * @covers Carica\Firmata\Resolutions
     */
    function testGetIterator() {
      $resolutions = new Resolutions();
      $this->assertEquals(
        array(
          Board::PIN_STATE_ANALOG => 1023,
          Board::PIN_STATE_PWM => 255,
          Board::PIN_STATE_SERVO => 360
        ),
        iterator_to_array($resolutions)
      );
    }
  }

}