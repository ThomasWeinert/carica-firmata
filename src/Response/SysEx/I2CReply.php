<?php

namespace Carica\Firmata\Response\SysEx {

  use Carica\Firmata;

  /**
   * @property-read integer $slaveAddress
   * @property-read integer $register
   * @property-read string $data
   */
  class I2CReply extends Firmata\Response\SysEx {

    private $_slaveAddress = 0;
    private $_register = 0;
    private $_data = '';

    public function __construct($command, array $bytes) {
      parent::__construct($command, $bytes);
      $this->_slaveAddress = $bytes[1] | ($bytes[2] << 7);
      $this->_register = $bytes[3] | ($bytes[4] << 7);
      $this->_data = self::decodeBytes(array_slice($bytes, 5));
    }

    public function __get($name) {
      switch ($name) {
      case 'slaveAddress' :
        return $this->_slaveAddress;
      case 'register' :
        return $this->_register;
      case 'data' :
        return $this->_data;
      }
      return parent::__get($name);
    }
  }
}