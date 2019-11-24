<?php

namespace Carica\Firmata\Rest {

  include_once(__DIR__.'/../Bootstrap.php');

  use ArrayIterator;
  use Carica\Firmata\Board as FirmataBoard;
  use Carica\Io;
  use Carica\Firmata;
  use Carica\Io\Network\HTTP\Connection as HTTPConnection;
  use Carica\Io\Network\HTTP\Request as HTTPRequest;
  use Carica\Io\Network\HTTP\Response\Content\XML as XMLResponseContent;
  use DOMElement;
  use PHPUnit\Framework\MockObject\MockObject;
  use PHPUnit\Framework\TestCase;

  class PinsTest extends TestCase {

    /**
     * @covers \Carica\Firmata\Rest\Pins
     */
    public function testWithInactiveBoard(): void {
      /** @var MockObject|FirmataBoard $board */
      $board = $this
        ->getMockBuilder(FirmataBoard::class)
        ->disableOriginalConstructor()
        ->getMock();
      $board
        ->expects($this->once())
        ->method('isActive')
        ->willReturn(FALSE);

      $handler = new Pins($board);
      $response = $handler($this->getRequestFixture(), []);

      /** @var XMLResponseContent $content */
      $content = $response->content;
      $this->assertXmlStringEqualsXmlString(
        '<?xml version="1.0" encoding="utf-8"?><board active="no"/>',
        $content->document->saveXML()
      );
    }

    /**
     * @covers \Carica\Firmata\Rest\Pins
     */
    public function testWithActiveBoard(): void {
      $pin = $this
        ->getMockBuilder(Firmata\Pin::class)
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('__get')
        ->willReturnMap(
          [
            ['pin', 23]
          ]
        );

      /** @var MockObject|FirmataBoard $board */
      $board = $this
        ->getMockBuilder(FirmataBoard::class)
        ->disableOriginalConstructor()
        ->getMock();
      $board
        ->expects($this->once())
        ->method('isActive')
        ->willReturn(TRUE);
      $board
        ->method('__get')
        ->willReturnMap(
          [
            ['version', '42.21'],
            ['pins', new ArrayIterator([$pin])]
          ]
        );

      /** @var MockObject|PinHandler $pinHandler */
      $pinHandler = $this
        ->getMockBuilder(PinHandler::class)
        ->disableOriginalConstructor()
        ->getMock();
      $pinHandler
        ->expects($this->once())
        ->method('appendPin')
        ->with($this->isInstanceOf(DOMElement::class), 23);

      $handler = new Pins($board);
      $handler->pinHandler($pinHandler);
      $response = $handler($this->getRequestFixture(), []);

      /** @var XMLResponseContent $content */
      $content = $response->content;
      $this->assertXmlStringEqualsXmlString(
        '<?xml version="1.0" encoding="utf-8"?><board active="yes" firmata="42.21"/>',
        $content->document->saveXml()
      );
    }

    /**
     * @covers \Carica\Firmata\Rest\Pins
     */
    public function testPinHandlerGetAfterSet(): void {
      /** @var MockObject|PinHandler $pinHandler */
      $pinHandler = $this
        ->getMockBuilder(PinHandler::class)
        ->disableOriginalConstructor()
        ->getMock();
      /** @var MockObject|FirmataBoard $board */
      $board = $this
        ->getMockBuilder(FirmataBoard::class)
        ->disableOriginalConstructor()
        ->getMock();

      $handler = new Pins($board);
      $handler->pinHandler($pinHandler);
      $this->assertSame($pinHandler, $handler->pinHandler());
    }

    /**
     * @covers \Carica\Firmata\Rest\Pins
     */
    public function testPinHandlerGetImplicitCreate(): void {
      /** @var MockObject|FirmataBoard $board */
      $board = $this
        ->getMockBuilder(FirmataBoard::class)
        ->disableOriginalConstructor()
        ->getMock();

      $handler = new Pins($board);
      $this->assertNotNull($handler->pinHandler());
    }

    /**
     * @return MockObject|HTTPRequest
     */
    private function getRequestFixture(): MockObject {
      /** @var MockObject|HTTPConnection $connection */
      $connection = $this
        ->getMockBuilder(HTTPConnection::class)
        ->disableOriginalConstructor()
        ->getMock();
      $request = $this
        ->getMockBuilder(HTTPRequest::class)
        ->disableOriginalConstructor()
        ->getMock();
      $request
        ->expects($this->once())
        ->method('createResponse')
        ->willReturn(new Io\Network\Http\Response($connection));
      return $request;
    }
  }
}
