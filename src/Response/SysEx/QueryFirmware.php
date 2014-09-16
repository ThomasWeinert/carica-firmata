<?php

namespace Carica\Firmata\Response\SysEx {

  use Carica\Firmata;

  /**
   * Class QueryFirmware
   *
   * @property string $name
   * @property integer $major
   * @property integer $minor
   */
  class QueryFirmware extends Firmata\Response\SysEx {

    /**
     * @var string
     */
    private $_name = '';

    /**
     * @var int
     */
    private $_major = 0;

    /**
     * @var int
     */
    private $_minor = 0;

    /**
     * @param string $command
     * @param array $bytes
     */
    public function __construct($command, array $bytes) {
      parent::__construct($command, $bytes);
      $this->_major = $bytes[1];
      $this->_minor = $bytes[2];
      $this->_name = trim(self::decodeBytes(array_slice($bytes, 3)));
    }

    /**
     * @param string $name
     * @return int|string
     */
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