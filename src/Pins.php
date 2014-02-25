<?php

namespace Carica\Firmata {

  /**
   * Pins provides an encapsulation for the list of Pin object, allowing to
   * access to the pins using array and iterator syntax
   */
  class Pins implements \ArrayAccess, \Countable, \IteratorAggregate {

    /**
     * List of Pin objects
     * @var array(integer=>Pin)
     */
    private $_pins = array();

    /**
     * Mapping: analog channel to pin index
     * @var array(integer=>integer)
     */
    private $_channels = array();

    /**
     * @param Board $board
     * @param array $pinCapabilities
     */
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

    /**
     * @param integer $offset
     *
     * @return bool
     */
    public function offsetExists($offset) {
      return array_key_exists((int)$offset, $this->_pins);
    }

    /**
     * @param integer $offset
     *
     * @return Pin
     * @throws Exception\NonExistingPin
     */
    public function offsetGet($offset) {
      if ($this->offsetExists($offset)) {
        return $this->_pins[(int)$offset];
      }
      throw new Exception\NonExistingPin($offset);
    }

    /**
     * @param integer $offset
     * @param Pin $value
     *
     * @throws \LogicException
     */
    public function offsetSet($offset, $value) {
      throw new \LogicException('Pins are not replaceable.');
    }

    /**
     * @param integer $offset
     *
     * @throws \LogicException
     */
    public function offsetUnset($offset) {
      throw new \LogicException('Pins are not removeable.');
    }

    /**
     * @return int
     */
    public function count() {
      return count($this->_pins);
    }

    /**
     * @return \Iterator
     */
    public function getIterator(){
      return new \ArrayIterator($this->_pins);
    }
  }
}