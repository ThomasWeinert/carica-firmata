<?php

namespace Carica\Firmata\I2C {

  use Carica\Firmata;

  class Request extends Firmata\Request {

    /**
     * @var int
     */
    private $_slaveAddress;

    /**
     * @var string|NULL
     */
    private $_data;

    /**
     * @var string
     */
    private $_mode;

    /**
     * @param Firmata\Board $board
     * @param int $slaveAddress
     * @param int $mode
     * @param string|array|NULL $data
     */
    public function __construct(
      Firmata\Board $board,
      int $slaveAddress,
      int $mode,
      $data = NULL
    ) {
      parent::__construct($board);
      $this->_slaveAddress = $slaveAddress;
      $this->_mode = $mode;
      $this->setData($data);
    }

    /**
     * @param string|array|NULL $data
     */
    private function setData($data): void {
      if (NULL === $data) {
        $this->_data = NULL;
      } elseif (is_array($data)) {
        $this->_data = pack('C*', ...$data);
      } else {
        $this->_data = (string)$data;
      }
    }

    public function send(): void {
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
