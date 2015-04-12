<?php

namespace Carica\Firmata\I2C {

  use Carica\Firmata;

  /**
   * @property-read integer $slaveAddress
   * @property-read integer $register
   * @property-read string $data
   */
  class Reply extends Firmata\Response {

    /**
     * @var int
     */
    private $_slaveAddress = 0;

    /**
     * @var int
     */
    private $_register = 0;

    /**
     * @var string
     */
    private $_data = '';

    /**
     * @param string $command
     * @param array $bytes
     */
    public function __construct($command, array $bytes) {
      parent::__construct($command, $bytes);
      $this->_slaveAddress = $bytes[1] | ($bytes[2] << 7);
      $this->_register = $bytes[3] | ($bytes[4] << 7);
      $this->_data = self::decodeBytes(array_slice($bytes, 5));
    }

    /**
     * @param string $name
     * @return int|string
     */
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