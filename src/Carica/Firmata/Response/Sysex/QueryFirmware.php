<?php

namespace Carica\Firmata\Response\Sysex {

  use Carica\Firmata;

  class QueryFirmware extends Firmata\Response\Sysex {

    private $_name = '';
    private $_major = 0;
    private $_minor = 0;

    public function __construct($command, array $bytes) {
      parent::__construct($command, $bytes);
      $this->_major = $bytes[1];
      $this->_minor = $bytes[2];
      $this->_name = trim(self::decodeBytes(array_slice($bytes, 3)));
    }

    public function __get($name) {
      switch ($name) {
      case 'name' :
        return $this->_name;
      case 'major' :
        return $this->_major;
      case 'minor' :
        return $this->_minor;
      }
      return parent::__get($name);
    }
  }
}