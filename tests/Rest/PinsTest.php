<?php

namespace Carica\Firmata\Rest {

  include_once(__DIR__ . '/../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;

  class PinsTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Rest\Pins
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

      $handler = new Pins($board);
      $response = $handler($this->getRequestFixture(), array());

      $this->assertXmlStringEqualsXmlString(
        '<?xml version="1.0" encoding="utf-8"?><board active="no"/>',
        $response->content->document->saveXml()
      );
    }

    /**
     * @covers Carica\Firmata\Rest\Pins
     */
    public function testWithActiveBoard() {
      $pin = $this
        ->getMockBuilder('Carica\Firmata\Pin')
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('pin', 23)
            )
          )
        );

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
              array('version', '42.21'),
              array('pins', new \ArrayIterator(array($pin)))
            )
          )
        );

      $pinHandler = $this
        ->getMockBuilder('Carica\Firmata\Rest\Pin')
        ->disableOriginalConstructor()
        ->getMock();
      $pinHandler
        ->expects($this->once())
        ->method('appendPin')
        ->with($this->isInstanceOf('DOMElement'), 23);

      $handler = new Pins($board);
      $handler->pinHandler($pinHandler);
      $response = $handler($this->getRequestFixture(), array());

      $this->assertXmlStringEqualsXmlString(
        '<?xml version="1.0" encoding="utf-8"?><board active="yes" firmata="42.21"/>',
        $response->content->document->saveXml()
      );
    }

    /**
     * @covers Carica\Firmata\Rest\Pins
     */
    public function testPinHandlerGetAfterSet() {
      $pinHandler = $this
        ->getMockBuilder('Carica\Firmata\Rest\Pin')
        ->disableOriginalConstructor()
        ->getMock();
      $board = $this
        ->getMockBuilder('Carica\Firmata\Board')
        ->disableOriginalConstructor()
        ->getMock();

      $handler = new Pins($board);
      $handler->pinHandler($pinHandler);
      $this->assertSame($pinHandler, $handler->pinHandler());
    }

    /**
     * @covers Carica\Firmata\Rest\Pins
     */
    public function testPinHandlerGetImplicitCreate() {
      $board = $this
        ->getMockBuilder('Carica\Firmata\Board')
        ->disableOriginalConstructor()
        ->getMock();

      $handler = new Pins($board);
      $this->assertInstanceOf('Carica\Firmata\Rest\Pin', $handler->pinHandler());
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