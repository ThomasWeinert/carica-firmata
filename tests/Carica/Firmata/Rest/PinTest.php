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

    /**
     * @covers Carica\Firmata\Rest\Pin
     */
    public function testWithDigitalPin() {
      $pin = $this->getPinFixture(
        array(
          'pin' => 42,
          'mode' => Firmata\Board::PIN_MODE_OUTPUT,
          'digital' => true,
          'value' => 0x01,
          'supports' => array(Firmata\Board::PIN_MODE_OUTPUT => 1)
        )
      );
      $handler = new Pin($this->getBoardFixture(array(42 => $pin)));
      $response = $handler($this->getRequestFixture(), array('pin' => 42));

      $this->assertXmlStringEqualsXmlString(
        '<?xml version="1.0" encoding="utf-8"?>'.
        '<board active="yes" firmata="21.42">'.
          '<pin number="42" supports="output" mode="output" digital="yes" value="1"/>'.
        '</board>',
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
              array('pin', isset($data['pin']) ? $data['pin'] : 0),
              array('mode', isset($data['mode']) ? $data['mode'] : 0x00),
              array('digital', isset($data['digital']) ? $data['digital'] : FALSE),
              array('analog', isset($data['analog']) ? $data['analog'] : 0),
              array('value', isset($data['value']) ? $data['value'] : 0),
              array('supports', isset($data['supports']) ? $data['supports'] : array()),
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