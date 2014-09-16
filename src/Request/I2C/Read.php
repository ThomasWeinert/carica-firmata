<?php

namespace Carica\Firmata\Request\I2C {

  use Carica\Firmata;

  class Read extends Firmata\Request {

    /**
     * @var int
     */
    private $_slaveAddress = 0;

    /**
     * @var int
     */
    private $_length = 0;

    /**
     * @param Firmata\Board $board
     * @param int $slaveAddress
     * @param int $length
     */
    public function __construct(
      Firmata\Board $board,
      $slaveAddress,
      $length
    ) {
      parent::__construct($board);
      $this->_slaveAddress = (int)$slaveAddress;
      $this->_length = (int)$length;
    }

    /**
     * @return void
     */
    public function send() {
      $this
        ->board()
        ->stream()
        ->write(
          array(
            Firmata\Board::START_SYSEX,
            Firmata\Board::I2C_REQUEST,
            $this->_slaveAddress,
            Firmata\Board::I2C_MODE_READ << 3,
            $this->_length & 0x7F,
            ($this->_length >> 7) & 0x7F,
            Firmata\Board::END_SYSEX
          )
      );
    }
  }
}