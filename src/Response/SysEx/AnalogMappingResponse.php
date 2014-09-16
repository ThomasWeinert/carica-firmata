<?php

namespace Carica\Firmata\Response\SysEx {

  use Carica\Firmata;

  /**
   * Class AnalogMappingResponse, the reponse provides the mappings in both
   * ways. $hannels prives analog channel to pin index number, $pins the
   *  opposite direction.
   *
   * @property array $pins
   * @property array $channels
   */
  class AnalogMappingResponse extends Firmata\Response\SysEx {

    /**
     * @var array
     */
    private $_pins = array();

    /**
     * @var array
     */
    private $_channels = array();

    /**
     * @param string $command
     * @param array $bytes
     */
    public function __construct($command, array $bytes) {
      parent::__construct($command, $bytes);
      $length = count($bytes);
      for ($i = 1, $pin = 0; $i < $length; ++$i, ++$pin) {
        $channel = $bytes[$i];
        if ($channel !== 127) {
          $this->_channels[$channel] = $pin;
          $this->_pins[$pin] = $channel;
        }
      }
    }

    /**
     * @param string $name
     * @return array|int
     */
    public function __get($name) {
      switch ($name) {
      case 'channels' :
        return $this->_channels;
      case 'pins' :
        return $this->_pins;
      }
      return parent::__get($name);
    }
  }
}