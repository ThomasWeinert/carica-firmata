<?php

namespace Carica\Firmata {

  use ArrayAccess;
  use ArrayIterator;
  use Countable;
  use IteratorAggregate;
  use LogicException;
  use Traversable;

  /**
   * Pins provides an encapsulation for the list of Pin object, allowing to
   * access to the pins using array and iterator syntax
   */
  class Pins implements ArrayAccess, Countable, IteratorAggregate {

    /**
     * List of Pin objects
     *
     * @var array(int=>Pin)
     */
    private $_pins = [];

    /**
     * Mapping: analog channel to pin index
     *
     * @var array(int=>int)
     */
    private $_channels = [];

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
     *
     * @param array $channels
     */
    public function setAnalogMapping(array $channels) {
      $this->_channels = $channels;
    }

    /**
     * Get the pin index for an analog pin channel.
     *
     * @param int $channel
     * @return int
     */
    public function getPinByChannel(int $channel): int {
      if (isset($this->_channels[$channel])) {
        return (int)$this->_channels[$channel];
      }
      return -1;
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset): bool {
      return array_key_exists((int)$offset, $this->_pins);
    }

    /**
     * @param int $offset
     *
     * @return Pin
     * @throws Exception\NonExistingPin
     */
    public function offsetGet($offset): Pin {
      if ($this->offsetExists($offset)) {
        return $this->_pins[(int)$offset];
      }
      throw new Exception\NonExistingPin($offset);
    }

    /**
     * @param int $offset
     * @param Pin $value
     *
     * @throws LogicException
     */
    public function offsetSet($offset, $value) {
      throw new LogicException('Pins are not replaceable.');
    }

    /**
     * @param int $offset
     * @throws LogicException
     */
    public function offsetUnset($offset) {
      throw new LogicException('Pins are not removeable.');
    }

    /**
     * @return int
     */
    public function count(): int {
      return count($this->_pins);
    }

    /**
     * @return Traversable
     */
    public function getIterator(): Traversable {
      return new ArrayIterator($this->_pins);
    }
  }
}
