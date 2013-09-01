<?php

namespace Carica\Firmata {

  class Pins implements \ArrayAccess, \Countable, \IteratorAggregate {

    private $_pins = array();
    private $_channels = array();

    public function __construct(Board $board, array $pinCapabilities) {
      foreach ($pinCapabilities as $pin => $supportedModes) {
        if (!empty($supportedModes)) {
          $this->_pins[$pin] = new Pin($board, $pin, $supportedModes);
        }
      }
    }

    /**
     * Set the analog pin mapping, the array contains
     * the nalog channels and the pin index
     * @param array $channels
     */
    public function setAnalogMapping(array $channels) {
      $this->_channels = $channels;
    }

    /**
     * Get the pin index for an analog pin channel.
     * @param integer $channel
     * @return integer
     */
    public function getPinByChannel($channel) {
      if (isset($this->_channels[$channel])) {
        return (int)$this->_channels[$channel];
      } else {
        return -1;
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