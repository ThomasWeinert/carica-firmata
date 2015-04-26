<?php

namespace Carica\Firmata\I2C {

  use Carica\Firmata;

  class Request extends Firmata\Request {

    /**
     * @var int
     */
    private $_slaveAddress = 0;

    /**
     * @var string
     */
    private $_data = '';

    /**
     * @var string
     */
    private $_mode;

    /**
     * @param Firmata\Board $board
     * @param int $slaveAddress
     * @param string $data
     */
    public function __construct(
      Firmata\Board $board,
      $slaveAddress,
      $mode,
      $data
    ) {
      parent::__construct($board);
      $this->_slaveAddress = (int)$slaveAddress;
      $this->setData($data);
    }

    /**
     * @param string $data
     */
    private function setData($data) {
      if (is_array($data)) {
        array_unshift($data, 'C*');
        $this->_data = call_user_func_array('pack', $data);
      } else {
        $this->_data = (string)$data;
      }
    }

    /**
     * @return mixed
     */
    public function send() {
      $data = pack(
        'CCCC',
        Firmata\Board::START_SYSEX,
        Firmata\I2C::REQUEST,
        $this->_slaveAddress,
        $this->_mode << 3
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