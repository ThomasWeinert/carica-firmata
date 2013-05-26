<?php

namespace Carica\Firmata {

  class Pins implements \ArrayAccess, \Countable, \IteratorAggregate {

    private $_pins = array();

    public function __construct(Board $board, array $pinCapabilities) {
      foreach ($pinCapabilities as $pin => $supportedModes) {
        if (!empty($supportedModes)) {
          $this->_pins[$pin] = new Pin($board, $pin, $supportedModes);
        }
      }
    }

    public function offsetExists($offset) {
      return array_key_exists((int)$offset, $this->_pins);
    }

    public function offsetGet($offset) {
      if ($this->offsetExists($offset)) {
        return $this->_pins[(int)$offset];
      }
      throw new Exception\NonExistingPin($offset);
    }

    public function offsetSet($offset, $value) {
      throw new \LogicException('Pins are not replaceable.');
    }

    public function offsetUnset($offset) {
      throw new \LogicException('Pins are not removeable.');
    }

    public function count() {
      return count($this->_pins);
    }

    public function getIterator(){
      return new \ArrayIterator($this->_pins);
    }
  }
}