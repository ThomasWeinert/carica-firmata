<?php

namespace Carica\Firmata\Request {

  include_once(__DIR__ . '/../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;

  class PulseInTest extends \PHPUnit_Framework_TestCase {

      /**
     * @covers Carica\Firmata\Request\PulseIn
     */
    public function testSend() {
      $expected = "\xF0\x74\x10\x01\x00\x00\x00\x00\x00\x00\x05\x00\x00\x00\x0f\x00\x42\x00\x40\x00\xF7";
      $request = new PulseIn(
        $this->getBoardWithStreamFixture($expected),
        16
      );
      $request->send();
    }

    public function getBoardWithStreamFixture($data) {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with($data);
      $board = $this
        ->getMockBuilder('Carica\\Firmata\\Board')
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