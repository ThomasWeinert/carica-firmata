<?php

namespace Carica\Firmata\Exception {

  use Carica\Firmata;

  class UnsupportedMode extends \Exception implements Firmata\Exception {

    private $_modes = array(
      Firmata\PIN_STATE_OUTPUT => 'digital output',
      Firmata\PIN_STATE_INPUT => 'digital input',
      Firmata\PIN_STATE_ANALOG => 'analog input',
      Firmata\PIN_STATE_PWM => 'pwm output'
    );

    public function __construct($pin, $mode) {
      parent::__construct(
        sprintf('Pin %d does not support mode "%s"', $pin, $this->_modes[$mode])
      );
    }
  }
}