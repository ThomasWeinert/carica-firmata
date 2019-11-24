<?php

namespace Carica\Firmata {

  use LogicException;

  /**
   * An response send by the arduino board, this is an abstract superclass, all
   * responses have an command and data, the command is handles here.
   *
   * @property int $command
   */
  class Response {

    /**
     * @var int
     */
    private $_command;

    /**
     * @var array
     */
    private $_bytes;

    /**
     * @param string $command
     * @param array $bytes
     */
    public function __construct($command, array $bytes) {
      $this->_command = $command;
      $this->_bytes = $bytes;
    }

    /**
     * @return int
     */
    public function getCommand(): int {
      return $this->_command;
    }

    /**
     * @return array
     */
    public function getRawData(): array {
      return $this->_bytes;
    }

    /**
     * @param string $name
     * @return int
     * @throws LogicException
     */
    public function __isset($name) {
      return $name === 'command';
    }

    /**
     * @param string $name
     * @return int
     * @throws LogicException
     */
    public function __get($name) {
      if ($name === 'command') {
        return $this->getCommand();
      }
      throw new LogicException(
        sprintf('Unknown property %s::$%s', get_class($this), $name)
      );
    }

    public function __set($name, $value) {
      throw new LogicException(
        sprintf('Property %s::$%s can not be written', get_class($this), $name)
      );
    }

    public function __unset($name) {
      throw new LogicException(
        sprintf('Property %s::$%s can not be written', get_class($this), $name)
      );
    }

    /**
     * Join groups of to 7 bit bytes into 8 bit bytes.
     *
     * @param array $bytes
     * @return string
     */
    public static function decodeBytes($bytes): string {
      $length = count($bytes);
      $result = '';
      for ($i = 0; $i < $length - 1; $i += 2) {
        $result .= pack('C', ($bytes[$i] & 0x7F) | (($bytes[$i + 1] & 0x7F) << 7));
      }
      return $result;
    }
  }
}
