<?php

namespace Carica\Firmata\Response\SysEx {

  include_once(__DIR__.'/../../Bootstrap.php');

  use Carica\Firmata;
  use PHPUnit\Framework\TestCase;

  class CapabilityResponseTest extends TestCase {

    /**
     * @covers \Carica\Firmata\Response\SysEx\CapabilityResponse
     */
    public function testConstructor(): void {
      $response = new CapabilityResponse(
        $data = [
          0x7f, // pin 0
          0x7f, // pin 1
          0x00, 0x01, 0x01, 0x01, 0x04, 0x0e, 0x7f, // pin 2
          0x00, 0x01, 0x01, 0x01, 0x03, 0x08, 0x04, 0x0e, 0x7f, // pin 3
          0x00, 0x01, 0x01, 0x01, 0x04, 0x0e, 0x7f, // pin 4
          0x00, 0x01, 0x01, 0x01, 0x03, 0x08, 0x04, 0x0e, 0x7f, // pin 5
          0x00, 0x01, 0x01, 0x01, 0x03, 0x08, 0x04, 0x0e, 0x7f, // pin 6
          0x00, 0x01, 0x01, 0x01, 0x04, 0x0e, 0x7f, // pin 7
          0x00, 0x01, 0x01, 0x01, 0x04, 0x0e, 0x7f, // pin 8
          0x00, 0x01, 0x01, 0x01, 0x03, 0x08, 0x04, 0x0e, 0x7f, //pin 9
          0x00, 0x01, 0x01, 0x01, 0x03, 0x08, 0x04, 0x0e, 0x7f, // pin 10
          0x00, 0x01, 0x01, 0x01, 0x03, 0x08, 0x04, 0x0e, 0x7f, // pin 11
          0x00, 0x01, 0x01, 0x01, 0x04, 0x0e, 0x7f, // pin 12
          0x00, 0x01, 0x01, 0x01, 0x04, 0x0e, 0x7f, // pin 13
          0x00, 0x01, 0x01, 0x01, 0x02, 0x0a, 0x7f, // pin 14
          0x00, 0x01, 0x01, 0x01, 0x02, 0x0a, 0x7f, // pin 15
          0x00, 0x01, 0x01, 0x01, 0x02, 0x0a, 0x7f, // pin 16
          0x00, 0x01, 0x01, 0x01, 0x02, 0x0a, 0x7f, // pin 17
          0x00, 0x01, 0x01, 0x01, 0x02, 0x0a, 0x06, 0x01, 0x7f, // pin 18
          0x00, 0x01, 0x01, 0x01, 0x02, 0x0a, 0x06, 0x01, 0x7f, //pin 19
          0x02, 0x0a, 0x7f, // pin 20
          0x02, 0x0a, 0x7f // pin 21
        ]
      );
      $this->assertEquals(
        [
          [], // pin 0
          [], // pin 1
          [ // pin 2
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_SERVO => 359
          ],
          [ // pin 3
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_PWM => 255,
            Firmata\Pin::MODE_SERVO => 359
          ],
          [ // pin 4
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_SERVO => 359
          ],
          [ // pin 5
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_PWM => 255,
            Firmata\Pin::MODE_SERVO => 359
          ],
          [ // pin 6
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_PWM => 255,
            Firmata\Pin::MODE_SERVO => 359
          ],
          [ // pin 7
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_SERVO => 359
          ],
          [ // pin 8
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_SERVO => 359
          ],
          [ // pin 9
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_PWM => 255,
            Firmata\Pin::MODE_SERVO => 359
          ],
          [ // pin 10
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_PWM => 255,
            Firmata\Pin::MODE_SERVO => 359
          ],
          [ // pin 11
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_PWM => 255,
            Firmata\Pin::MODE_SERVO => 359
          ],
          [ // pin 12
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_SERVO => 359
          ],
          [ // pin 13
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_SERVO => 359
          ],
          [ // pin 14 a0
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_ANALOG => 1023
          ],
          [ // pin 15 a1
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_ANALOG => 1023
          ],
          [ // pin 16 a2
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_ANALOG => 1023
          ],
          [ // pin 17 a3
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_ANALOG => 1023
          ],
          [ // pin 18 a4
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_ANALOG => 1023,
            Firmata\Pin::MODE_I2C => 1
          ],
          [ // pin 19 a5
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_ANALOG => 1023,
            Firmata\Pin::MODE_I2C => 1
          ],
          [ // pin 20 a6
            Firmata\Pin::MODE_ANALOG => 1023
          ],
          [ // pin 21 a7
            Firmata\Pin::MODE_ANALOG => 1023
          ],
        ],
        $response->pins
      );
      $this->assertEquals(
        Firmata\Board::CAPABILITY_RESPONSE,
        $response->command
      );
    }

    /**
     * @covers \Carica\Firmata\Response\SysEx\CapabilityResponse
     */
    public function testConstructorWithAnalogDefault(): void {
      $response = new CapabilityResponse(
        $data = [
          0x7f, // pin 0
          0x7f, // pin 1
          0x00, 0x01, 0x01, 0x01, 0x02, 0x00, 0x7f, // pin with analog fallback
        ]
      );
      $this->assertEquals(
        [
          [], // pin 0
          [], // pin 1
          [ // pin with analog fallback
            Firmata\Pin::MODE_INPUT => 1,
            Firmata\Pin::MODE_OUTPUT => 1,
            Firmata\Pin::MODE_ANALOG => 1023
          ]
        ],
        $response->pins
      );
    }
  }
}
