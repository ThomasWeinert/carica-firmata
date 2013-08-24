<?php

namespace Carica\Firmata\Response\Sysex {

  use Carica\Firmata;

  class AnalogMappingResponse extends Firmata\Response\Sysex {

    private $_pins = array();
    private $_channels = array();

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