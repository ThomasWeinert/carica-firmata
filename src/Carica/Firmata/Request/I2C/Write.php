<?php

namespace Carica\Firmata\Request\I2C {

  use Carica\Firmata;

  class Write extends Firmata\Request {

    private $_slaveAddress = 0;
    private $_data = '';

    public function __construct(
      Firmata\Board $board,
      $slaveAddress,
      $data
    ) {
      parent::__construct($board);
      $this->_slaveAddress = (int)$slaveAddress;
      $this->setData($data);
    }

    public function setData($data) {
      if (is_array($data)) {
        array_unshift($data, 'C*');
        $this->_data = call_user_func_array('pack', $data);
      } else {
        $this->_data = (string)$data;
      }
    }

    public function send() {
      $data = pack(
        'CCCC',
        Firmata\Board::START_SYSEX,
        Firmata\Board::I2C_REQUEST,
        $this->_slaveAddress,
        Firmata\Board::I2C_MODE_WRITE << 3
      );
      $data .= self::encodeBytes($this->_data);
      $data .= pack(
        'C',
        Firmata\Board::END_SYSEX
      );
      $this->board()->stream()->write($data);
    }
  }
}