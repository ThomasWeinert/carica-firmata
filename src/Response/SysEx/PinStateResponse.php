<?php

namespace Carica\Firmata\Response\SysEx {

  use Carica\Firmata;

  /**
   * @property-read integer $pin
   * @property-read integer $mode
   * @property-read integer $value
   */
  class PinStateResponse extends Firmata\Response\SysEx {

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
     * @param string $command
     * @param array $bytes
     */
    public function __construct($command, array $bytes) {
      parent::__construct($command, $bytes);
      $length = count($bytes);
      $this->_pin = $bytes[1];
      $this->_mode = $bytes[2];
      $this->_value = $bytes[3];
      for ($i = 4, $shift = 7; $i < $length; ++$i, $shift *= 2) {
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