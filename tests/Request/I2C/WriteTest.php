<?php

namespace Carica\Firmata\Request\I2C {

  include_once(__DIR__ . '/../../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;

  class WriteTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Request\I2C\Write
     */
    public function testSend() {
      $expected = "\xF0\x76\x03\x00\x48\x00\x61\x00\x6C\x00\x6C\x00\x6F\x00\xF7";
      $request = new Write(
        $this->getBoardWithStreamFixture($expected),
        3,
        'Hallo'
      );
      $request->send();
    }

    /**
     * @covers Carica\Firmata\Request\I2C\Write
     */
    public function testSendWithArray() {
      $expected = "\xF0\x76\x03\x00\x7f\x01\x00\x00\x70\x01\xF7";
      $request = new Write(
        $this->getBoardWithStreamFixture($expected),
        3,
        [0xFF, 0x00, 0xF0]
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