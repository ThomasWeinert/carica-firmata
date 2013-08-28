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