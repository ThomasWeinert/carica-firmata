<?php

namespace Carica\Firmata\Rest {

  include_once(__DIR__.'/../Bootstrap.php');

  use Carica\Firmata;
  use Carica\Firmata\Board as FirmataBoard;
  use Carica\Io\Network\HTTP\Connection as HTTPConnection;
  use Carica\Io\Network\HTTP\Request as HTTPRequest;
  use Carica\Io\Network\HTTP\Response as HTTPResponse;
  use Carica\Io\Network\HTTP\Response\Content\XML as XMLResponseContent;
  use PHPUnit\Framework\MockObject\MockObject;
  use PHPUnit\Framework\TestCase;

  class PinTest extends TestCase {

    /**
     * @covers \Carica\Firmata\Rest\PinHandler
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

      $handler = new PinHandler($board);
      $response = $handler($this->getRequestFixture(), []);

      /** @var XMLResponseContent $content */
      $content = $response->content;
      $this->assertXmlStringEqualsXmlString(
        '<?xml version="1.0" encoding="utf-8"?><board active="no"/>',
        $content->document->saveXML()
      );
    }

    /**
     * @covers \Carica\Firmata\Rest\PinHandler
     */
    public function testWithActiveBoard(): void {
      $handler = new PinHandler($this->getBoardFixture());
      $response = $handler($this->getRequestFixture(), []);

      /** @var XMLResponseContent $content */
      $content = $response->content;
      $this->assertXmlStringEqualsXmlString(
        '<?xml version="1.0" encoding="utf-8"?><board active="yes" firmata="21.42"/>',
        $content->document->saveXML()
      );
    }

    /**
     * @covers \Carica\Firmata\Rest\PinHandler
     */
    public function testWithDigitalPin(): void {
      $pin = $this->getPinFixture(
        [
          'pin' => 42,
          'mode' => Firmata\Pin::MODE_OUTPUT,
          'digital' => TRUE,
          'value' => 0x01,
          'supports' => [Firmata\Pin::MODE_OUTPUT => 1]
        ]
      );
      $handler = new PinHandler($this->getBoardFixture([42 => $pin]));
      $response = $handler($this->getRequestFixture(), ['pin' => 42]);

      /** @var XMLResponseContent $content */
      $content = $response->content;
      $this->assertXmlStringEqualsXmlString(
        '<?xml version="1.0" encoding="utf-8"?>'.
        '<board active="yes" firmata="21.42">'.
        '<pin number="42" supports="output" mode="output" digital="yes" value="1"/>'.
        '</board>',
        $content->document->saveXML()
      );
    }

    /**
     * @covers \Carica\Firmata\Rest\PinHandler
     */
    public function testWithAnalogPin(): void {
      $pin = $this->getPinFixture(
        [
          'pin' => 42,
          'mode' => Firmata\Pin::MODE_ANALOG,
          'analog' => 23,
          'value' => 23,
          'supports' => [Firmata\Pin::MODE_ANALOG => 1023]
        ]
      );
      $handler = new PinHandler($this->getBoardFixture([42 => $pin]));
      $response = $handler($this->getRequestFixture(), ['pin' => 42]);

      /** @var XMLResponseContent $content */
      $content = $response->content;
      $this->assertXmlStringEqualsXmlString(
        '<?xml version="1.0" encoding="utf-8"?>'.
        '<board active="yes" firmata="21.42">'.
        '<pin number="42" supports="analog" mode="analog" analog="23" value="23"/>'.
        '</board>',
        $content->document->saveXML()
      );
    }

    /**
     * @covers \Carica\Firmata\Rest\PinHandler
     */
    public function testPinModeChange(): void {
      $request = $this->getRequestFixture(['mode' => 'pwm']);
      $pin = $this->getPinFixture();
      $pin
        ->expects($this->once())
        ->method('setMode')
        ->with(Firmata\Pin::MODE_PWM);
      $handler = new PinHandler($this->getBoardFixture([0 => $pin]));
      $handler($request, ['pin' => 0]);
    }

    /**
     * @covers \Carica\Firmata\Rest\PinHandler
     */
    public function testPinModeInvalidModeIsIgnored(): void {
      $request = $this->getRequestFixture(['mode' => 'invalid_mode']);
      $pin = $this->getPinFixture();
      $pin
        ->expects($this->never())
        ->method('__set');
      $handler = new PinHandler($this->getBoardFixture([0 => $pin]));
      $handler($request, ['pin' => 0]);
    }

    /**
     * @covers \Carica\Firmata\Rest\PinHandler
     */
    public function testPinDigitalChange(): void {
      $request = $this->getRequestFixture(['digital' => 'yes']);
      $pin = $this->getPinFixture();
      $pin
        ->expects($this->once())
        ->method('__set')
        ->with('digital', FirmataBoard::DIGITAL_HIGH);
      $handler = new PinHandler($this->getBoardFixture([0 => $pin]));
      $handler($request, ['pin' => 0]);
    }

    /**
     * @covers \Carica\Firmata\Rest\PinHandler
     */
    public function testPinAnalogChange(): void {
      $request = $this->getRequestFixture(['analog' => '0.5']);
      $pin = $this->getPinFixture();
      $pin
        ->expects($this->once())
        ->method('__set')
        ->with('analog', $this->equalTo(0.5, 0.01));
      $handler = new PinHandler($this->getBoardFixture([0 => $pin]));
      $handler($request, ['pin' => 0]);
    }

    /**
     * @covers \Carica\Firmata\Rest\PinHandler
     */
    public function testPinValueChange(): void {
      $request = $this->getRequestFixture(['value' => '128']);
      $pin = $this->getPinFixture();
      $pin
        ->expects($this->once())
        ->method('__set')
        ->with('value', 128);
      $handler = new PinHandler($this->getBoardFixture([0 => $pin]));
      $handler($request, ['pin' => 0]);
    }

    /************************
     * Fixtures
     ***********************/

    /**
     * @param array $pins
     * @return MockObject|FirmataBoard
     */
    private function getBoardFixture(array $pins = []): MockObject {
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
            ['version', '21.42'],
            ['pins', $pins]
          ]
        );
      return $board;
    }

    /**
     * @param array $data
     * @return MockObject|Firmata\Pin
     */
    private function getPinFixture($data = []): MockObject {
      $pin = $this
        ->getMockBuilder(Firmata\Pin::class)
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->method('__get')
        ->willReturnMap(
          [
            ['pin', $data['pin'] ?? 0],
            ['mode', $data['mode'] ?? 0x00],
            ['digital', $data['digital'] ?? FALSE],
            ['analog', $data['analog'] ?? 0],
            ['value', $data['value'] ?? 0],
            ['supports', $data['supports'] ?? []],
          ]
        );
      return $pin;
    }

    /**
     * @param array $query
     * @return MockObject|HTTPRequest
     */
    private function getRequestFixture(array $query = []): MockObject {
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
        ->method('createResponse')
        ->willReturn(new HTTPResponse($connection));
      $request
        ->method('__get')
        ->willReturnMap(
          [
            ['query', $query]
          ]
        );
      return $request;
    }
  }
}
