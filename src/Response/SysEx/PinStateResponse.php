<?php

namespace Carica\Firmata\Response\SysEx {

  use Carica\Firmata;

  /**
   * @property-read integer $pin
   * @property-read integer $mode
   * @property-read integer $value
   */
  class PinStateResponse extends Firmata\Response {

    /**
     * @var int
     */
    private $_pin = 0;

    /**
     * @var int
     */
    private $_mode = 0;

    /**
     * @var int
     */
    private $_value = 0;

    /**
     * @param array $bytes
     */
    public function __construct(array $bytes) {
      parent::__construct(Firmata\Board::PIN_STATE_RESPONSE, $bytes);
      $length = count($bytes);
      $this->_pin = $bytes[0];
      $this->_mode = $bytes[1];
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