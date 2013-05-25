<?php

namespace Carica\Firmata {

  class Version {

    private $_text = '';
    private $_major = 0;
    private $_minor = 0;

    public function __construct($major, $minor, $text = '') {
      $this->_major = (int)$major;
      $this->_minor = (int)$minor;
      $this->_text = trim($text);
    }

    public function __toString() {
      $result = empty($this->_text) ? '' : $this->_text.' ';
      return $result.$this->_major.'.'.$this->_minor;
    }

    public function __get($name) {
      switch ($name) {
      case 'text' :
        return $this->_text;
      case 'major' :
        return $this->_major;
      case 'minor' :
        return $this->_minor;
      case 'version' :
        return $this->_version;
      }
      throw new \LogicException(sprintf('Unknown property %s::$%s', get_class($this), $name));
    }

    public function __set($name, $value) {
      throw new \LogicException(sprintf('Object %s can not be changed.', get_class($this)));
    }
  }
}
