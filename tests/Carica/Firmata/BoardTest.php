<?php

namespace Carica\Firmata {

  include_once(__DIR__.'/Bootstrap.php');

  class BoardTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Board::__construct
     * @covers Carica\Firmata\Board::stream
     */
    public function testConstructor() {
      $board = new Board($stream = $this->getMock('Carica\Io\Stream'));
      $this->assertSame($stream, $board->stream());
    }

    /**
     * @covers Carica\Firmata\Board::isActive
     */
    public function testIsActiveExpectingFalse() {
      $board = new Board($this->getMock('Carica\Io\Stream'));
      $this->assertFalse($board->isActive());
    }

    /**
     * @covers Carica\Firmata\Board::buffer
     */
    public function testBufferGetAfterSet() {
      $buffer = $this->getMock('Carica\Firmata\Buffer');
      $board = new Board($this->getMock('Carica\Io\Stream'));
      $board->buffer($buffer);
      $this->assertSame($buffer, $board->buffer());
    }

    /**
     * @covers Carica\Firmata\Board::buffer
     */
    public function testBufferGetImplicitCreate() {
      $board = new Board($this->getMock('Carica\Io\Stream'));
      $this->assertInstanceOf('Carica\Firmata\Buffer', $board->buffer());
    }
  }
}