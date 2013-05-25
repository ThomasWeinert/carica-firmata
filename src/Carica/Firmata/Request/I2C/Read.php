<?php

namespace Carica\Firmata\Request\I2C {

  use Carica\Firmata;

  class Read extends Firmata\Request {

    private $_slaveAdress = 0;
    private $_length = '';

    public function __construct(
      Firmata\Board $board,
      $slaveAddress,
      $length
    ) {
      parent::__construct($board);
      $this->_slaveAddress = (int)$slaveAddress;
      $this->_length = (int)$length;
    }

    public function send() {
      $this
        ->board()
        ->stream()
        ->write(
          array(
            FIRMATA\COMMAND_START_SYSEX,
            FIRMATA\COMMAND_I2C_REQUEST,
            $this->_slaveAddress,
            FIRMATA\I2C_MODE_READ << 3,
            $this->_length & 0x7F,
            ($this->_length >> 7) & 0x7F,
            FIRMATA\COMMAND_END_SYSEX
          )
      );
    }
  }
}