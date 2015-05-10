<?php

namespace Carica\Firmata\I2C {

  use Carica\Firmata;

  class Request extends Firmata\Request {

    /**
     * @var int
     */
    private $_slaveAddress = 0;

    /**
     * @var string|NULL
     */
    private $_data = NULL;

    /**
     * @var string
     */
    private $_mode;

    /**
     * @param Firmata\Board $board
     * @param int $slaveAddress
     * @param string|NULL $data
     */
    public function __construct(
      Firmata\Board $board,
      $slaveAddress,
      $mode,
      $data = NULL
    ) {
      parent::__construct($board);
      $this->_slaveAddress = (int)$slaveAddress;
      $this->_mode = (int)$mode;
      $this->setData($data);
    }

    /**
     * @param string|NULL $data
     */
    private function setData($data) {
      if (NULL == $data) {
        $this->_data = NULL;
      } elseif (is_array($data)) {
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
      if (NULL !==$this->_data) {
        $data .= self::encodeBytes($this->_data);
      }
      $data .= pack('C', Firmata\Board::END_SYSEX);
      $this->board()->stream()->write($data);
    }
  }
}