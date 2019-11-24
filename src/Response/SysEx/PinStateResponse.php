<?php

namespace Carica\Firmata\Response\SysEx {

  use Carica\Firmata;

  /**
   * @property-read int $pin
   * @property-read int $mode
   * @property-read int $value
   */
  class PinStateResponse extends Firmata\Response {

    /**
     * @var int
     */
    private $_pin;

    /**
     * @var int
     */
    private $_mode;

    /**
     * @var int
     */
    private $_value;

    private $_modes = [
      0x01 => Firmata\Pin::MODE_OUTPUT,
      0x00 => Firmata\Pin::MODE_INPUT,
      0x02 => Firmata\Pin::MODE_ANALOG,
      0x03 => Firmata\Pin::MODE_PWM,
      0x04 => Firmata\Pin::MODE_SERVO,
      0x05 => Firmata\Pin::MODE_SHIFT,
      0x06 => Firmata\Pin::MODE_I2C
    ];

    /**
     * @param array $bytes
     */
    public function __construct(array $bytes) {
      parent::__construct(Firmata\Board::PIN_STATE_RESPONSE, $bytes);
      $length = count($bytes);
      $this->_pin = $bytes[0];
      $this->_mode = $this->_modes[$bytes[1]] ?? FALSE;
      $this->_value = $bytes[2];
      for ($i = 3, $shift = 7; $i < $length; ++$i, $shift *= 2) {
        $this->_value |= ($bytes[$i] << $shift);
      }
    }

    /**
     * @param string $name
     * @return int
     */
    public function __get($name) {
      switch ($name) {
      case 'pin' :
        return $this->_pin;
      case 'mode' :
        return $this->_mode;
      case 'value' :
        return $this->_value;
      }
      return parent::__get($name);
    }
  }
}
