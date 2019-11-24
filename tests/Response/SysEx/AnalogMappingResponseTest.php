<?php

namespace Carica\Firmata\Response\SysEx {

  include_once(__DIR__.'/../../Bootstrap.php');

  use Carica\Firmata;
  use PHPUnit\Framework\TestCase;

  class AnalogMappingResponseTest extends TestCase {

    /**
     * @covers \Carica\Firmata\Response\SysEx\AnalogMappingResponse
     */
    public function testConstructor(): void {
      $response = new AnalogMappingResponse(
        [
          // pin 0 to 13
          0x7f, 0x7f, 0x7f, 0x7f, 0x7f, 0x7f, 0x7f, 0x7f, 0x7f, 0x7f, 0x7f, 0x7f, 0x7f, 0x7f,
          // pin 14 (a0) to 21 (a7)
          0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07
        ]
      );
      $this->assertEquals(
        [
          0 => 14,
          1 => 15,
          2 => 16,
          3 => 17,
          4 => 18,
          5 => 19,
          6 => 20,
          7 => 21
        ],
        $response->channels
      );
      $this->assertEquals(
        [
          14 => 0,
          15 => 1,
          16 => 2,
          17 => 3,
          18 => 4,
          19 => 5,
          20 => 6,
          21 => 7
        ],
        $response->pins
      );
      $this->assertEquals(
        Firmata\Board::ANALOG_MAPPING_RESPONSE,
        $response->command
      );
    }
  }
}
