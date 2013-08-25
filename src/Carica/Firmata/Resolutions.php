<?php

namespace Carica\Firmata {

  class Resolutions implements \ArrayAccess, \Countable, \IteratorAggregate {

    private $_resolutions = array(
      Board::PIN_STATE_PWM => 255,
      Board::PIN_STATE_ANALOG => 1023,
      Board::PIN_STATE_SERVO => 360
    );

    public function offsetExists($offset) {
      return isset($this->_resolutions[$offset]);
    }

    public function offsetGet($offset) {
      if (isset($this->_resolutions[$offset])) {
        return $this->_resolutions[$offset];
      }
      return 1;
    }

    public function offsetSet($offset, $value) {
      if (isset($this->_resolutions[$offset])) {
        $this->_resolutions[$offset] = (int)$value;
      } else {
        throw new \UnexpectedValueException('Invalid value mode');
      }
    }

    public function offsetUnset($offset) {
      throw new \UnexpectedValueException('Value mode resultions can only be changed, not removed.');
    }

    public function count() {
      return count($this->_resolutions);
    }

    public function getIterator() {
      return new \ArrayIterator($this->_resolutions);
    }
  }
}