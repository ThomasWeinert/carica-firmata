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

    private $_pins = array();

    public function __construct($command, array $bytes) {
      parent::__construct($command, $bytes);
      $length = count($bytes);
      $supported = 0;
      $byteIndex = 0;
      for ($i = 1; $i < $length; ++$i) {
        if ($bytes[$i] == 127) {
          $modes = array();
          foreach ($this->_supported as $mode) {
            if ($supported & (1 << $mode)) {
              $modes[] = $mode;
            }
          }
          $this->_pins[] = $modes;
          $supported = 0;
          $byteIndex = 0;
          continue;
        }
        if ($byteIndex === 0) {
          $supported |= (1 << $bytes[$i]);
        }
        $byteIndex ^= $byteIndex;
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