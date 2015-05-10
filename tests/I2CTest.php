<?php

namespace Carica\Firmata {

  use Carica\Io;

  include_once(__DIR__ . '/Bootstrap.php');

  class I2CTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\I2C
     */
    public function testConfigure() {
      $expected = [
        0xF0, 0x78, 0x00, 0x00, 0xF7
      ];
      $i2c = new I2C(
        $this->getBoardWithStreamFixture($expected)
      );
      $i2c->configure();
    }

    /**
     * @covers Carica\Firmata\I2C
     */
    public function testRead() {
      $i2c = new I2C(
        $this->getBoardWithStreamFixture(
          [0xF0, 0x78, 0x00, 0x00, 0xF7],
          "\xF0\x76\x02\x08\x37\x00\xF7"
        )
      );
      $defer = $i2c->read(0x02, 7);
      $this->assertInstanceOf('Carica\Io\Deferred', $defer);
    }

    public function getBoardWithStreamFixture($data, $secondData = NULL) {
      $emitter = new \Carica\Io\Event\Emitter;
      $stream = $this->getMock('Carica\\Io\\Stream');
      if (func_num_args() > 1) {
        $assertion = $stream
          ->expects($this->any())
          ->method('write');
        $arguments = [];
        foreach (func_get_args() as $bytes) {
          $arguments[] = [$bytes];
        }
        call_user_func_array([$assertion, 'withConsecutive'], $arguments);
      } else {
        $stream
          ->expects($this->once())
          ->method('write')
          ->with($data);
      }
      $board = $this
        ->getMockBuilder('Carica\\Firmata\\Board')
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