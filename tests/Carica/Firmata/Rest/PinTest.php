<?php

namespace Carica\Firmata\Rest {

  include_once(__DIR__.'/../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;

  class PinTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Rest\Pin
     */
    public function testWithInactiveBoard() {
      $board = $this
        ->getMockBuilder('Carica\Firmata\Board')
        ->disableOriginalConstructor()
        ->getMock();
      $board
        ->expects($this->once())
        ->method('isActive')
        ->will($this->returnValue(FALSE));

      $handler = new Pin($board);
      $response = $handler($this->getRequestFixture(), array());

      $this->assertXmlStringEqualsXmlString(
        '<?xml version="1.0" encoding="utf-8"?><board active="no"/>',
        $response->content->document->saveXml()
      );
    }

    /**
     * @covers Carica\Firmata\Rest\Pin
     */
    public function testWithActiveBoard() {
      $handler = new Pin($this->getBoardFixture());
      $response = $handler($this->getRequestFixture(), array());

      $this->assertXmlStringEqualsXmlString(
        '<?xml version="1.0" encoding="utf-8"?><board active="yes" firmata="21.42"/>',
        $response->content->document->saveXml()
      );
    }

    private function getBoardFixture(array $pins = array()) {
      $board = $this
        ->getMockBuilder('Carica\Firmata\Board')
        ->disableOriginalConstructor()
        ->getMock();
      $board
        ->expects($this->once())
        ->method('isActive')
        ->will($this->returnValue(TRUE));
      $board
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('version', '21.42'),
              array('pins', $pins)
            )
          )
        );
      return $board;
    }

    private function getPinFixture($data = array()) {
      $pin = $this
        ->getMockBuilder('Carica\Firmata\Pin')
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('pin', isset($data['pin']) ? 0 : $data['pin']),
              array('digital', isset($data['digital']) ? 0 : $data['digital']),
              array('analog', isset($data['analog']) ? 0 : $data['analog']),
              array('value', isset($data['value']) ? 0 : $data['value']),
            )
          )
        );
      return $pin;
    }

    private function getRequestFixture() {
      $connection = $this
        ->getMockBuilder('Carica\Io\Network\Http\Connection')
        ->disableOriginalConstructor()
        ->getMock();
      $request = $this
        ->getMockBuilder('Carica\Io\Network\Http\Request')
        ->disableOriginalConstructor()
        ->getMock();
      $request
        ->expects($this->once())
        ->method('createResponse')
        ->will($this->returnValue(new Io\Network\Http\Response($connection)));
      return $request;
    }
  }
}