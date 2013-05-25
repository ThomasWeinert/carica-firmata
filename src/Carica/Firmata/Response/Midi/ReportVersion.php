<?php

namespace Carica\Firmata\Response\Midi {

  use Carica\Firmata;

  class ReportVersion extends Firmata\Response\Midi {

    private $_major = 0;
    private $_minor = 0;

    public function __construct($command, array $bytes) {
      parent::__construct($command, $bytes);
      $this->_major = $bytes[1];
      $this->_minor = $bytes[2];
    }

    public function __get($name) {
      switch ($name) {
      case 'major' :
        return $this->_major;
      case 'minor' :
        return $this->_minor;
      }
      parent::__get($name);
    }
  }
}