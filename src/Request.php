<?php

namespace Carica\Firmata {

  abstract class Request {

    /**
     * @var Board
     */
    private $_board = NULL;

    /**
     * @param Board $board
     */
    public function __construct(Board $board) {
      $this->_board = $board;
    }

    /**
     * @return Board
     */
    public function board() {
      return $this->_board;
    }

    /**
     * @return mixed
     */
    abstract public function send();

    /**
     * Split a string with 8 bit bytes into 2 7bit bytes.
     *
     * @param string $data
     * @return string
     */
    public static function encodeBytes($data) {
      $bytes = array_slice(unpack("C*", "\0".$data), 1);
      $result = '';
      foreach ($bytes as $byte) {
        $result .= pack('CC', $byte & 0x7F, ($byte >> 7) & 0x7F);
      }
      return $result;
    }
  }
}
