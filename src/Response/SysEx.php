<?php

namespace Carica\Firmata\Response {

  use Carica\Firmata;

  class SysEx
    extends Firmata\Response
    implements \IteratorAggregate, \Countable, \ArrayAccess {

    private $_bytes = array();

    /**
     * @param string $command
     * @param array $bytes
     */
    public function __construct($command, array $bytes) {
      parent::__construct($command, $bytes);
      $this->_bytes = $bytes;
    }

    /**
     * Get an iterator for the data bytes of the message
     *
     * @return \ArrayIterator
     */
    public function getIterator() {
      return new \ArrayIterator($this->_bytes);
    }

    /**
     * Return count of data bytes
     *
     * @return \ArrayIterator
     */
    public function count() {
      return count($this->_bytes);
    }

    public function offsetExists($offset) {
      return array_key_exists($offset, $this->_bytes);
    }

    public function offsetGet($offset) {
      return $this->_bytes[$offset];
    }

    public function offsetSet($offset, $value) {
      throw new \BadFunctionCallException('Object data can not by changed.');
    }

    public function offsetUnset($offset) {
      throw new \BadFunctionCallException('Object data can not by changed.');
    }
  }
}