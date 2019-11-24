<?php

namespace Carica\Firmata\I2C {

  include_once(__DIR__.'/../Bootstrap.php');

  use Carica\Firmata\Board as FirmataBoard;
  use Carica\Io;
  use Carica\Firmata;
  use PHPUnit\Framework\MockObject\MockObject;
  use PHPUnit\Framework\TestCase;

  class RequestTest extends TestCase {

    /**
     * @covers \Carica\Firmata\I2C\Request
     */
    public function testSendReadRequest(): void {
      $expected = "\xF0\x76\x02\x08\x37\x00\xF7";
      $request = new Request(
        $this->getBoardWithStreamFixture($expected),
        2,
        Firmata\I2C::MODE_READ,
        7
      );
      $request->send();
    }

    /**
     * @covers \Carica\Firmata\I2C\Request
     */
    public function testSendWriteRequest(): void {
      $expected = "\xF0\x76\x03\x00\x48\x00\x61\x00\x6C\x00\x6C\x00\x6F\x00\xF7";
      $request = new Request(
        $this->getBoardWithStreamFixture($expected),
        3,
        Firmata\I2C::MODE_WRITE,
        'Hallo'
      );
      $request->send();
    }

    /**
     * @covers \Carica\Firmata\I2C\Request
     */
    public function testSendWriteRequestWithArray(): void {
      $expected = "\xF0\x76\x03\x00\x7f\x01\x00\x00\x70\x01\xF7";
      $request = new Request(
        $this->getBoardWithStreamFixture($expected),
        3,
        Firmata\I2C::MODE_WRITE,
        [0xFF, 0x00, 0xF0]
      );
      $request->send();
    }

    /**
     * @covers \Carica\Firmata\I2C\Request
     */
    public function testSendStartReadingRequest(): void {
      $expected = "\xF0\x76\x02\x10\x37\x00\xF7";
      $request = new Request(
        $this->getBoardWithStreamFixture($expected),
        2,
        Firmata\I2C::MODE_CONTINOUS_READ,
        7
      );
      $request->send();
    }

    /**
     * @covers \Carica\Firmata\I2C\Request
     */
    public function testStopStartReadingRequest(): void {
      $expected = "\xF0\x76\x02\x18\xF7";
      $request = new Request(
        $this->getBoardWithStreamFixture($expected),
        2,
        Firmata\I2C::MODE_STOP_READING
      );
      $request->send();
    }

    /**
     * @param $data
     * @return MockObject|FirmataBoard
     */
    public function getBoardWithStreamFixture($data) {
      $stream = $this->getMockBuilder(Io\Stream::class)->getMock();
      $stream
        ->expects($this->once())
        ->method('write')
        ->with($data);
      $board = $this
        ->getMockBuilder(FirmataBoard::class)
        ->disableOriginalConstructor()
        ->getMock();
      $board
        ->method('stream')
        ->willReturn($stream);
      return $board;
    }
  }
}
