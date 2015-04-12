<?php

namespace Carica\Firmata\Response\Midi {

  use Carica\Firmata;

  /**
   * Class ReportVersion
   *
   * @property integer $major
   * @property integer $minor
   */
  class ReportVersion extends Firmata\Response {

    /**
     * @var int
     */
    private $_major = 0;

    /**
     * @var int
     */
    private $_minor = 0;

    /**
     * @param array $bytes
     */
    public function __construct(array $bytes) {
      parent::__construct(Firmata\Board::REPORT_VERSION, $bytes);
      $this->_major = $bytes[1];
      $this->_minor = $bytes[2];
    }

    /**
     * @param string $name
     * @return int
     */
    public function __get($name) {
      switch ($name) {
      case 'major' :
        return $this->_major;
      case 'minor' :
        return $this->_minor;
      }
      return parent::__get($name);
    }
  }
}