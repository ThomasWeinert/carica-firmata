<?php

namespace Carica\Firmata\Request\I2C {

  include_once(__DIR__.'/../../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;

  class ReadTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Request\I2C\Read
     */
    public function testSend() {
      $expected = [
        Firmata\Board::START_SYSEX,
        Firmata\Board::I2C_REQUEST,
        0x02,
        0x08,
        0x07,
        0x00,
        Firmata\Board::END_SYSEX
      ];
      $request = new Read(
        $this->getBoardWithStreamFixture($expected),
        2,
        7
      );
      $request->send();
    }

    public function getBoardWithStreamFixture($data) {
      $stream = $this->getMock('Carica\Io\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with($data);
      $board = $this
        ->getMockBuilder('Carica\Firmata\Board')
        ->disableOriginalConstructor()
        ->getMock();
      $board
        ->expects($this->any())
        ->method('stream')
        ->will($this->returnValue($stream));
      return $board;
    }
  }
}