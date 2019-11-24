<?php

namespace Carica\Firmata {

  use LogicException;

  /**
   * Immutable data object for version informations
   *
   * @property-read int major
   * @property-read int minor
   * @property-read int text
   */
  class Version {

    /**
     * @var string
     */
    private $_text;

    /**
     * @var int
     */
    private $_major;

    /**
     * @var int
     */
    private $_minor;

    /**
     * @param string $major
     * @param string $minor
     * @param string $text
     */
    public function __construct(string $major, string $minor, string $text = '') {
      $this->_major = (int)$major;
      $this->_minor = (int)$minor;
      $this->_text = trim($text);
    }

    /**
     * @return string
     */
    public function __toString() {
      $result = empty($this->_text) ? '' : $this->_text.' ';
      return $result.$this->_major.'.'.$this->_minor;
    }

    /**
     * @param string $name
     * @return bool
     * @throws LogicException
     */
    public function __isset($name) {
      switch ($name) {
      case 'text' :
      case 'major' :
      case 'minor' :
        return TRUE;
      }
      return FALSE;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws LogicException
     */
    public function __get($name) {
      switch ($name) {
      case 'text' :
      case 'major' :
      case 'minor' :
        return $this->{'_'.$name};
      }
      throw new LogicException(sprintf('Unknown property %s::$%s', get_class($this), $name));
    }

    /**
     * Block changes to the properties
     *
     * @param string $name
     * @param mixed $value
     * @throws LogicException
     */
    public function __set($name, $value) {
      throw new LogicException(sprintf('Object %s can not be changed.', get_class($this)));
    }

    /**
     * Block changes to the properties
     *
     * @param string $name
     * @throws LogicException
     */
    public function __unset($name) {
      throw new LogicException(sprintf('Object %s can not be changed.', get_class($this)));
    }
  }
}
