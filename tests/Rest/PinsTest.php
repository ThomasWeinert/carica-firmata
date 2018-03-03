<?php

namespace Carica\Firmata\Rest {

  include_once(__DIR__ . '/../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;
  use PHPUnit\Framework\MockObject\MockObject;

  class PinsTest extends \PHPUnit\Framework\TestCase {

    /**
     * @covers \Carica\Firmata\Rest\Pins
     */
    public function testWithInactiveBoard() {
      /** @var MockObject|Firmata\Board $board */
      $board = $this
        ->getMockBuilder(Firmata\Board::class)
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
     * @covers \Carica\Firmata\Rest\Pins
     */
    public function testWithActiveBoard() {
      $pin = $this
        ->getMockBuilder(Firmata\Pin::class)
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

      /** @var MockObject|Firmata\Board $board */
      $board = $this
        ->getMockBuilder(Firmata\Board::class)
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
        ->getMockBuilder(Firmata\Rest\Pin::class)
        ->disableOriginalConstructor()
        ->getMock();
      $pinHandler
        ->expects($this->once())
        ->method('appendPin')
        ->with($this->isInstanceOf(\DOMElement::class), 23);

      $handler = new Pins($board);
      $handler->pinHandler($pinHandler);
      $response = $handler($this->getRequestFixture(), array());

      $this->assertXmlStringEqualsXmlString(
        '<?xml version="1.0" encoding="utf-8"?><board active="yes" firmata="42.21"/>',
        $response->content->document->saveXml()
      );
    }

    /**
     * @covers \Carica\Firmata\Rest\Pins
     */
    public function testPinHandlerGetAfterSet() {
      $pinHandler = $this
        ->getMockBuilder(Firmata\Rest\Pin::class)
        ->disableOriginalConstructor()
        ->getMock();
      /** @var MockObject|Firmata\Board $board */
      $board = $this
        ->getMockBuilder(Firmata\Board::class)
        ->disableOriginalConstructor()
        ->getMock();

      $handler = new Pins($board);
      $handler->pinHandler($pinHandler);
      $this->assertSame($pinHandler, $handler->pinHandler());
    }

    /**
     * @covers \Carica\Firmata\Rest\Pins
     */
    public function testPinHandlerGetImplicitCreate() {
      /** @var MockObject|Firmata\Board $board */
      $board = $this
        ->getMockBuilder(Firmata\Board::class)
        ->disableOriginalConstructor()
        ->getMock();

      $handler = new Pins($board);
      $this->assertInstanceOf(Firmata\Rest\Pin::class, $handler->pinHandler());
    }

    private function getRequestFixture() {
      /** @var MockObject|Io\Network\Http\Connection $connection */
      $connection = $this
        ->getMockBuilder(Io\Network\Http\Connection::class)
        ->disableOriginalConstructor()
        ->getMock();
      $request = $this
        ->getMockBuilder(Io\Network\Http\Request::class)
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
