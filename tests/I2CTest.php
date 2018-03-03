<?php

namespace Carica\Firmata {

  use Carica\Io;

  include_once(__DIR__ . '/Bootstrap.php');

  class I2CTest extends \PHPUnit\Framework\TestCase {

    /**
     * @covers \Carica\Firmata\I2C
     */
    public function testConfigure() {
      $expected = [
        0xF0, 0x78, 0x00, 0x00, 0xF7
      ];
      $i2c = new I2C(
        $this->getBoardWithStreamFixture($expected), 0x02
      );
      $i2c->configure();
    }

    /**
     * @covers \Carica\Firmata\I2C
     */
    public function testRead() {
      $i2c = new I2C(
        $this->getBoardWithStreamFixture(
          [0xF0, 0x78, 0x00, 0x00, 0xF7],
          "\xF0\x76\x02\x08\x37\x00\xF7"
        ),
        0x02
      );
      $defer = $i2c->read(7);
      $this->assertInstanceOf(Io\Deferred::class, $defer);
    }

    /**
     * @param array $data
     * @return \PHPUnit\Framework\MockObject\MockObject|Board
     */
    public function getBoardWithStreamFixture(...$data) {
      $emitter = new Io\Event\Emitter;
      $stream = $this->getMockBuilder(Io\Stream::class)->getMock();
      if (\count($data) > 1) {
        $assertion = $stream
          ->expects($this->any())
          ->method('write');
        $arguments = [];
        foreach ($data as $bytes) {
          $arguments[] = [$bytes];
        }
        $assertion->withConsecutive(...$arguments);
      } else {
        $stream
          ->expects($this->once())
          ->method('write')
          ->with($data[0]);
      }
      $board = $this
        ->getMockBuilder(Board::class)
        ->disableOriginalConstructor()
        ->getMock();
      $board
        ->expects($this->any())
        ->method('stream')
        ->will($this->returnValue($stream));
      $board
        ->expects($this->any())
        ->method('events')
        ->will($this->returnValue($emitter));
      return $board;
    }

  }
}
