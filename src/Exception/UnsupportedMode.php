<?php

namespace Carica\Firmata\Exception {

  use Carica\Firmata;

  class UnsupportedMode extends \Exception implements Firmata\Exception {

    /**
     * @var array
     */
    private $_modes = array(
      Firmata\Board::PIN_MODE_OUTPUT => 'digital output',
      Firmata\Board::PIN_MODE_INPUT => 'digital input',
      Firmata\Board::PIN_MODE_ANALOG => 'analog input',
      Firmata\Board::PIN_MODE_PWM => 'pwm output'
    );

    /**
     * @param int $pin
     * @param int $mode
     */
    public function __construct($pin, $mode) {
      parent::__construct(
        sprintf('Pin %d does not support mode "%s"', $pin, $this->_modes[$mode])
      );
    }
  }
}