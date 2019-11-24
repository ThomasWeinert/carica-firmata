<?php

namespace Carica\Firmata\Exception {

  use Carica\Firmata;

  class UnsupportedMode extends \Exception implements Firmata\Exception {

    /**
     * @var array
     */
    private $_modes = array(
      Firmata\Pin::MODE_OUTPUT => 'digital output',
      Firmata\Pin::MODE_INPUT => 'digital input',
      Firmata\Pin::MODE_ANALOG => 'analog input',
      Firmata\Pin::MODE_PWM => 'pwm output'
    );

    /**
     * @param int $pin
     * @param int $mode
     */
    public function __construct(int $pin, int $mode) {
      parent::__construct(
        sprintf('Pin %d does not support mode "%s"', $pin, $this->_modes[$mode])
      );
    }
  }
}
