<?php

namespace Carica\Firmata {

  abstract class Response {

    private $_command = 0x00;

    public function __construct($command, array $bytes) {
      $this->_command = $command;
    }

    public function getCommand() {
      return $this->_command;
    }

    public function __get($name) {
      switch ($name) {
      case 'command' :
        return $this->getCommand();
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
