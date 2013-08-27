<?php

namespace Carica\Firmata\Response\SysEx {

  use Carica\Firmata\Response\Sysex\PinStateResponse;

  include_once(__DIR__.'/../../Bootstrap.php');

  use Carica\Firmata;

  class PinStateResponseTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Response\SysEx\PinStateResponse
     * @dataProvider providePinStateExamples
     */
    public function testConstructor($pin, $mode, $value, $bytes) {
      $response = new PinStateResponse(Firmata\Board::PIN_STATE_RESPONSE, $bytes);
      $this->assertEquals($pin, $response->pin);
      $this->assertEquals($mode, $response->mode);
      $this->assertEquals($value, $response->value);
      $this->assertEquals(Firmata\Board::PIN_STATE_RESPONSE, $response->command);
    }

    public static function providePinStateExamples() {
      return array(
        array(
          9,
          Firmata\Board::PIN_MODE_INPUT,
          1,
          [0x6E, 0x09, 0x00, 0x01]
        ),
        array(
          4,
          Firmata\Board::PIN_MODE_ANALOG,
          127,
          [0x6E, 0x04, 0x02, 0x7f]
        )
      );
    }
  }
}