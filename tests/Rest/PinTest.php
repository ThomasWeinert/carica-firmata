<?php

namespace Carica\Firmata\Rest {

  include_once(__DIR__ . '/../Bootstrap.php');

  use Carica\Io;
  use Carica\Firmata;
  use PHPUnit\Framework\MockObject\MockObject;

  class PinTest extends \PHPUnit\Framework\TestCase {

    /**
     * @covers \Carica\Firmata\Rest\Pin
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

      $handler = new Pin($board);
      $response = $handler($this->getRequestFixture(), array());

      $this->assertXmlStringEqualsXmlString(
        '<?xml version="1.0" encoding="utf-8"?><board active="no"/>',
        $response->content->document->saveXml()
      );
    }

    /**
     * @covers \Carica\Firmata\Rest\Pin
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
     * @covers \Carica\Firmata\Rest\Pin
     */
    public function testWithDigitalPin() {
      $pin = $this->getPinFixture(
        array(
          'pin' => 42,
          'mode' => Firmata\Pin::MODE_OUTPUT,
          'digital' => true,
          'value' => 0x01,
          'supports' => array(Firmata\Pin::MODE_OUTPUT => 1)
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

    /**
     * @covers \Carica\Firmata\Rest\Pin
     */
    public function testWithAnalogPin() {
      $pin = $this->getPinFixture(
        array(
          'pin' => 42,
          'mode' => Firmata\Pin::MODE_ANALOG,
          'analog' => 23,
          'value' => 23,
          'supports' => array(Firmata\Pin::MODE_ANALOG => 1023)
        )
      );
      $handler = new Pin($this->getBoardFixture(array(42 => $pin)));
      $response = $handler($this->getRequestFixture(), array('pin' => 42));

      $this->assertXmlStringEqualsXmlString(
        '<?xml version="1.0" encoding="utf-8"?>'.
        '<board active="yes" firmata="21.42">'.
          '<pin number="42" supports="analog" mode="analog" analog="23" value="23"/>'.
        '</board>',
        $response->content->document->saveXml()
      );
    }

    /**
     * @covers \Carica\Firmata\Rest\Pin
     */
    public function testPinModeChange() {
      $request = $this->getRequestFixture(array('mode' => 'pwm'));
      $pin = $this->getPinFixture();
      $pin
        ->expects($this->once())
        ->method('__set')
        ->with('mode', Firmata\Pin::MODE_PWM);
      $handler = new Pin($this->getBoardFixture(array(0 => $pin)));
      $handler($request, array('pin' => 0));
    }

    /**
     * @covers \Carica\Firmata\Rest\Pin
     */
    public function testPinModeInvalidModeIsIgnored() {
      $request = $this->getRequestFixture(array('mode' => 'invalid_mode'));
      $pin = $this->getPinFixture();
      $pin
        ->expects($this->never())
        ->method('__set');
      $handler = new Pin($this->getBoardFixture(array(0 => $pin)));
      $handler($request, array('pin' => 0));
    }

    /**
     * @covers \Carica\Firmata\Rest\Pin
     */
    public function testPinDigitalChange() {
      $request = $this->getRequestFixture(array('digital' => 'yes'));
      $pin = $this->getPinFixture();
      $pin
        ->expects($this->once())
        ->method('__set')
        ->with('digital', Firmata\Board::DIGITAL_HIGH);
      $handler = new Pin($this->getBoardFixture(array(0 => $pin)));
      $handler($request, array('pin' => 0));
    }

    /**
     * @covers \Carica\Firmata\Rest\Pin
     */
    public function testPinAnalogChange() {
      $request = $this->getRequestFixture(array('analog' => '0.5'));
      $pin = $this->getPinFixture();
      $pin
        ->expects($this->once())
        ->method('__set')
        ->with('analog', $this->equalTo(0.5, 0.01));
      $handler = new Pin($this->getBoardFixture(array(0 => $pin)));
      $handler($request, array('pin' => 0));
    }

    /**
     * @covers \Carica\Firmata\Rest\Pin
     */
    public function testPinValueChange() {
      $request = $this->getRequestFixture(array('value' => '128'));
      $pin = $this->getPinFixture();
      $pin
        ->expects($this->once())
        ->method('__set')
        ->with('value', 128);
      $handler = new Pin($this->getBoardFixture(array(0 => $pin)));
      $handler($request, array('pin' => 0));
    }

    /************************
     * Fixtures
     ***********************/

    /**
     * @param array $pins
     * @return \PHPUnit\Framework\MockObject\MockObject|Firmata\Board
     */
    private function getBoardFixture(array $pins = array()) {
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
              array('version', '21.42'),
              array('pins', $pins)
            )
          )
        );
      return $board;
    }

    private function getPinFixture($data = array()) {
      $pin = $this
        ->getMockBuilder(Firmata\Pin::class)
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

    private function getRequestFixture($query = array()) {
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
        ->expects($this->any())
        ->method('createResponse')
        ->will($this->returnValue(new Io\Network\Http\Response($connection)));
      $request
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('query', $query)
            )
          )
        );
      return $request;
    }
  }
}
