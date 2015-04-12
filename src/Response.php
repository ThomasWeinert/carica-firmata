<?php

namespace Carica\Firmata {

  /**
   * An response send by the arduino board, this is an abstract superclass, all
   * responses have an command and data, the command is handles here.
   *
   * @property integer $command
   */
  abstract class Response {

    /**
     * @var int
     */
    private $_command = 0x00;

    /**
     * @var array
     */
    private $_bytes = [];

    /**
     * @param string $command
     */
    public function __construct($command, $bytes) {
      $this->_command = $command;
      $this->_bytes = $bytes;
    }

    /**
     * @return int
     */
    public function getCommand() {
      return $this->_command;
    }

    /**
     * @return int
     */
    public function getRawData() {
      return $this->_bytes;
    }

    /**
     * @param string $name
     * @return int
     * @throws \LogicException
     */
    public function __get($name) {
      switch ($name) {
      case 'command' :
        return $this->getCommand();
      case 'rawData' :
        return $this->getRawData();
      }
      throw new \LogicException(
        sprintf('Unknown property %s::$%s', get_class($this), $name)
      );
    }

    /**
     * Join groups of to 7 bit bytes into 8 bit bytes.
     *
     * @param array $bytes
     * @return string
     */
    public static function decodeBytes($bytes) {
      $length = count($bytes);
      $result = '';
      for ($i = 0; $i < $length - 1; $i += 2) {
        $result .= pack('C', ($bytes[$i] & 0x7F) | (($bytes[$i + 1] & 0x7F) << 7));
      }
      return $result;
    }
  }
}
