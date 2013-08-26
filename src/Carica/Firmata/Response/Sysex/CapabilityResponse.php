<?php

namespace Carica\Firmata\Response\Sysex {

  use Carica\Firmata;

  class CapabilityResponse extends Firmata\Response\Sysex {

    private $_supported = array(
      Firmata\Board::PIN_STATE_INPUT,
      Firmata\Board::PIN_STATE_OUTPUT,
      Firmata\Board::PIN_STATE_ANALOG,
      Firmata\Board::PIN_STATE_PWM,
      Firmata\Board::PIN_STATE_SERVO
    );

    private $_resolutions = array(
      1 => 1,
      8 => 255,
      10 => 1023,
      14 => 360
    );

    private $_pins = array();

    public function __construct($command, array $bytes) {
      parent::__construct($command, $bytes);
      $length = count($bytes);
      $pin = 0;
      $i = 1;
      while ($i < $length) {
        if ($bytes[$i] == 0x7F) {
          if (!isset($this->_pins[$pin])) {
            $this->_pins[$pin] = array();
          }
          ++$pin;
          ++$i;
          continue;
        } elseif (in_array($bytes[$i], $this->_supported) &&
                  isset($this->_resolutions[$bytes[$i + 1]])) {
          $this->_pins[$pin][$bytes[$i]] = $this->_resolutions[$bytes[$i + 1]];
        }
        $i += 2;
      }
    }

    public function __get($name) {
      switch ($name) {
      case 'pins' :
        return $this->_pins;
      }
      return parent::__get($name);
    }
  }
}