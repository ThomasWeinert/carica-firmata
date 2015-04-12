<?php

namespace Carica\Firmata\Response\SysEx {

  use Carica\Firmata\Response\SysEx\CapabilityResponse;

  include_once(__DIR__ . '/../../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;

  class CapabilityResponseTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Response\SysEx\CapabilityResponse
     */
    public function testConstructor() {
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
        array(
          array(), // pin 0
          array(), // pin 1
          array( // pin 2
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_SERVO => 359
          ),
          array( // pin 3
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_PWM => 255,
            Firmata\Board::PIN_MODE_SERVO => 359
          ),
          array( // pin 4
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_SERVO => 359
          ),
          array( // pin 5
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_PWM => 255,
            Firmata\Board::PIN_MODE_SERVO => 359
          ),
          array( // pin 6
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_PWM => 255,
            Firmata\Board::PIN_MODE_SERVO => 359
          ),
          array( // pin 7
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_SERVO => 359
          ),
          array( // pin 8
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_SERVO => 359
          ),
          array( // pin 9
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_PWM => 255,
            Firmata\Board::PIN_MODE_SERVO => 359
          ),
          array( // pin 10
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_PWM => 255,
            Firmata\Board::PIN_MODE_SERVO => 359
          ),
          array( // pin 11
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_PWM => 255,
            Firmata\Board::PIN_MODE_SERVO => 359
          ),
          array( // pin 12
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_SERVO => 359
          ),
          array( // pin 13
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_SERVO => 359
          ),
          array( // pin 14 a0
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_ANALOG => 1023
          ),
          array( // pin 15 a1
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_ANALOG => 1023
          ),
          array( // pin 16 a2
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_ANALOG => 1023
          ),
          array( // pin 17 a3
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_ANALOG => 1023
          ),
          array( // pin 18 a4
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_ANALOG => 1023,
            Firmata\Board::PIN_MODE_I2C => 1
          ),
          array( // pin 19 a5
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_ANALOG => 1023,
            Firmata\Board::PIN_MODE_I2C => 1
          ),
          array( // pin 20 a6
            Firmata\Board::PIN_MODE_ANALOG => 1023
          ),
          array( // pin 21 a7
            Firmata\Board::PIN_MODE_ANALOG => 1023
          ),
        ),
        $response->pins
      );
      $this->assertEquals(
        Firmata\Board::CAPABILITY_RESPONSE,
        $response->command
      );
    }

    /**
     * @covers Carica\Firmata\Response\SysEx\CapabilityResponse
     */
    public function testConstructorWithAnalogDefault() {
      $response = new CapabilityResponse(
        $data = [
          0x7f, // pin 0
          0x7f, // pin 1
          0x00, 0x01, 0x01, 0x01, 0x02, 0x00, 0x7f, // pin with analog fallback
        ]
      );
      $this->assertEquals(
        array(
          array(), // pin 0
          array(), // pin 1
          array( // pin with analog fallback
            Firmata\Board::PIN_MODE_INPUT => 1,
            Firmata\Board::PIN_MODE_OUTPUT => 1,
            Firmata\Board::PIN_MODE_ANALOG => 1023
          )
        ),
        $response->pins
      );
    }
  }
}