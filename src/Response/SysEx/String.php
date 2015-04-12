<?php

namespace Carica\Firmata\Response\SysEx {

  use Carica\Firmata;

  /**
   * Class String
   *
   * @property string $text
   */
  class String extends Firmata\Response {

    /**
     * @var string
     */
    private $_text = '';

    /**
     * @param string $command
     * @param array $bytes
     */
    public function __construct($command, array $bytes) {
      parent::__construct($command, $bytes);
      $this->_text = self::decodeBytes(array_slice($bytes, 1));
    }

    /**
     * @param string $name
     * @return int|string
     */
    public function __get($name) {
      switch ($name) {
      case 'text' :
        return $this->_text;
      }
      return parent::__get($name);
    }
  }
}