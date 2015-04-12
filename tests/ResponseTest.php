<?php

namespace Carica\Firmata {

  include_once(__DIR__ . '/Bootstrap.php');

  class ResponseTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Response
     */
    public function testReadPropertyCommand() {
      $response = new Response_TestProxy(0x42, [0x42, 0x00, 0x00]);
      $this->assertEquals(0x42, $response->command);
    }

    /**
     * @covers Carica\Firmata\Response
     */
    public function testReadInvalidPropertyExpectingException() {
      $response = new Response_TestProxy(0x42, [0x42, 0x00, 0x00]);
      $this->setExpectedException('LogicException');
      $dummy = $response->INVALID_PROPERTY;
    }

    /**
     * @covers Carica\Firmata\Response
     */
    public function testGetRawData() {
      $response = new Response_TestProxy(0x42, [0x42, 0x01, 0x02]);
      $this->assertEquals(
        [0x42, 0x01, 0x02],
        $response->getRawData()
      );
    }

    /**
     * @covers Carica\Firmata\Response
     */
    public function testDecodeBytes() {
      $this->assertSame(
        'Sample',
        Response::decodeBytes(
          [0x53, 0x00, 0x61, 0x00, 0x6D, 0x00, 0x70, 0x00, 0x6C, 0x00, 0x65, 0x00]
        )
      );
    }
  }

  class Response_TestProxy extends Response {

  }
}