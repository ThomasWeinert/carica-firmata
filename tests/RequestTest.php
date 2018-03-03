<?php

namespace Carica\Firmata {

  use PHPUnit\Framework\MockObject\MockObject;

  include_once(__DIR__ . '/Bootstrap.php');

  class RequestTest extends \PHPUnit\Framework\TestCase {

    /**
     * @covers \Carica\Firmata\Request
     */
    public function testConstrutor() {
      /** @var MockObject|Board $board */
      $board = $this
        ->getMockBuilder(Board::class)
        ->disableOriginalConstructor()
        ->getMock();
      $request = new Request_TestProxy($board);
      $this->assertSame($board, $request->board());
    }

    /**
     * @covers \Carica\Firmata\Request
     */
    public function testEncodeBytes() {
      $this->assertSame(
        "\x53\x00\x61\x00\x6D\x00\x70\x00\x6C\x00\x65\x00",
        Request::encodeBytes('Sample')
      );
    }
  }

  class Request_TestProxy extends Request {

    public function send() {

    }

  }
}
