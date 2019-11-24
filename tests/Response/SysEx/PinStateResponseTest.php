<?php

namespace Carica\Firmata\Response\SysEx {

  include_once(__DIR__ . '/../../Bootstrap.php');

  use Carica\Firmata;
  use PHPUnit\Framework\TestCase;

  class PinStateResponseTest extends TestCase {

    /**
     * @covers       \Carica\Firmata\Response\SysEx\PinStateResponse
     * @dataProvider providePinStateExamples
     * @param $pin
     * @param $mode
     * @param $value
     * @param $bytes
     */
    public function testConstructor(int $pin, int $mode, int $value, array $bytes): void {
      $response = new PinStateResponse($bytes);
      $this->assertEquals($pin, $response->pin);
      $this->assertEquals($mode, $response->mode);
      $this->assertEquals($value, $response->value);
      $this->assertEquals(Firmata\Board::PIN_STATE_RESPONSE, $response->command);
    }

    public static function providePinStateExamples() {
      return array(
        array(
          9,
          Firmata\Pin::MODE_INPUT,
          1,
          [0x09, 0x00, 0x01]
        ),
        array(
          4,
          Firmata\Pin::MODE_ANALOG,
          127,
          [0x04, 0x02, 0x7f]
        ),
        array(
          4,
          Firmata\Pin::MODE_ANALOG,
          1000,
          [0x04, 0x02, 0x68, 0x07]
        ),
        array(
          4,
          Firmata\Pin::MODE_ANALOG,
          100000,
          [0x04, 0x02, 0x20, 0x0D, 0x06]
        )
      );
    }
  }
}
