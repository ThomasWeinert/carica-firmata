<?php

namespace Carica\Firmata\Response\Sysex {

  use Carica\Firmata;

  class PinStateResponse extends Firmata\Response\Sysex {

    private $_supported = array(
      self::PIN_STATE_INPUT,
      self::PIN_STATE_OUTPUT,
      self::PIN_STATE_ANALOG,
      self::PIN_STATE_PWM,
      self::PIN_STATE_SERVO
    );

    private $_pin = 0;
    private $_mode = 0;
    private $_value = 0;

    public function __construct($command, array $bytes) {
      parent::__construct($command, $bytes);
      $length = count($bytes);
      $this->_pin = $bytes[1];
      $this->_mode = $bytes[2];
      $this->_value = $bytes[3];
      for ($i = 4, $shift = 7; $i < 6, $i < $length; $i++, $shift *= 2) {
        $this->_value |= ($bytes[4] << $shift);
      }
    }

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