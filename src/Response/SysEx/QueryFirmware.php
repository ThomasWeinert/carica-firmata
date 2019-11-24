<?php

namespace Carica\Firmata\Response\SysEx {

  use Carica\Firmata;

  /**
   * Class QueryFirmware
   *
   * @property string $name
   * @property int $major
   * @property int $minor
   */
  class QueryFirmware extends Firmata\Response {

    /**
     * @var string
     */
    private $_name;

    /**
     * @var int
     */
    private $_major;

    /**
     * @var int
     */
    private $_minor;

    /**
     * @param array $bytes
     */
    public function __construct(array $bytes) {
      parent::__construct(Firmata\Board::QUERY_FIRMWARE, $bytes);
      $this->_major = $bytes[0];
      $this->_minor = $bytes[1];
      $this->_name = trim(self::decodeBytes(array_slice($bytes, 2)));
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
