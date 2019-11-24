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
  class AnalogMappingResponse extends Firmata\Response {

    /**
     * @var array
     */
    private $_pins = [];

    /**
     * @var array
     */
    private $_channels = [];

    /**
     * @param array $bytes
     */
    public function __construct(array $bytes) {
      parent::__construct(Firmata\Board::ANALOG_MAPPING_RESPONSE, $bytes);
      $length = count($bytes);
      for ($i = 0, $pin = 0; $i < $length; ++$i, ++$pin) {
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
