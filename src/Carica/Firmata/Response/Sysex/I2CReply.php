<?php

namespace Carica\Firmata\Response\Sysex {

  use Carica\Firmata;

  class I2CReply extends Firmata\Response\Sysex {

    private $_slaveAddress = 0;
    private $_register = 0;
    private $_data = '';

    public function __construct($command, array $bytes) {
      parent::__construct($command, $bytes);
      $this->_slaveAddress = self::decodeBytes(array_slice($bytes, 1, 2));
      $this->_register = self::decodeBytes(array_slice($bytes, 3, 2));
      $this->_data = self::decodeBytes(array_slice($bytes, 3));
    }

    public function __get($name) {
      switch ($name) {
      case 'slaveAddress' :
        return $this->_slaveAddress;
      case 'register' :
        return $this->_duration;
      case 'data' :
        return $this->_duration;
      }
      parent::__get($name);
    }
  }
}