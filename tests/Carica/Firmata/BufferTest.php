<?php

namespace Carica\Firmata {

  include_once(__DIR__.'/Bootstrap.php');

  class BufferTest extends \PHPUnit_Framework_TestCase {

    public function setUp() {
      class_exists('Carica\Firmata\Board');
    }

    /**
     * @covers Carica\Firmata\Buffer
     * @dataProvider provideDataWithVersionAtEnd
     */
    public function testRecievesVersion($data) {
      $buffer = new Buffer();
      $buffer->addData($data);
      $this->assertAttributeSame(TRUE, '_versionReceived', $buffer);
    }

    public function testRecieveVersionData() {
      $buffer = new Buffer();
      $buffer->addData("\xF9\x00\x00");
      $response = $buffer->getLastResponse();
      $this->assertInstanceOf('Carica\Firmata\Response\Midi\ReportVersion', $response);
    }

    public function testRecieveSysExStringResponse() {
      $buffer = new Buffer();
      $buffer->addData("\xF9\x00\x00\xF0\x71F\x00o\x00o\x00\xF7");
      $response = $buffer->getLastResponse();
      $this->assertInstanceOf('Carica\Firmata\Response\SysEx\String', $response);
      $this->assertEquals('Foo', $response->text);
    }

    public function testRecieveSysExStringResponseInSeveralDataString() {
      $buffer = new Buffer();
      $buffer->addData("\xF9\x00\x00");
      $buffer->addData("\xF0\x71F\x00o");
      $buffer->addData("\x00o\x00\xF7");
      $response = $buffer->getLastResponse();
      $this->assertInstanceOf('Carica\Firmata\Response\SysEx\String', $response);
      $this->assertEquals('Foo', $response->text);
    }

    public function testIgnoreNullBytesBetweenResponses() {
      $buffer = new Buffer();
      $buffer->addData("\xF9\x00\x00\x00\x00\x00\x00\x00\x00");
      $buffer->addData("\xF0\x71F\x00o\x00o\x00\xF7");
      $response = $buffer->getLastResponse();
      $this->assertInstanceOf('Carica\Firmata\Response\SysEx\String', $response);
    }

    public function provideDataWithVersionAtEnd() {
      return array(
        'simply version byte' => array("\xF9"),
        'null bytes up front' => array("\x00\x00\xF9"),
        'garbage up front' => array("\x01\x02\x03\xF9")
      );
    }
  }
}