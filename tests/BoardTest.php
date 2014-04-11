<?php

namespace Carica\Firmata {

  include_once(__DIR__ . '/Bootstrap.php');

  class BoardTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Board::__construct
     * @covers Carica\Firmata\Board::stream
     */
    public function testConstructor() {
      $board = new Board($stream = $this->getMock('Carica\\Io\\Stream'));
      $this->assertSame($stream, $board->stream());
    }

    /**
     * @covers Carica\Firmata\Board::isActive
     */
    public function testIsActiveExpectingFalse() {
      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $this->assertFalse($board->isActive());
    }

    /**
     * @covers Carica\Firmata\Board::buffer
     */
    public function testBufferGetAfterSet() {
      $buffer = $this->getMock('Carica\\Firmata\\Buffer');
      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $board->buffer($buffer);
      $this->assertSame($buffer, $board->buffer());
    }

    /**
     * @covers Carica\Firmata\Board::buffer
     */
    public function testBufferGetImplicitCreate() {
      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $this->assertInstanceOf('Carica\\Firmata\\Buffer', $board->buffer());
    }

    /**
     * @covers Carica\Firmata\Board::__get
     * @covers Carica\Firmata\Board::pins
     */
    public function testGetPropertyPinsImplicitCreate() {
      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $this->assertInstanceOf('Carica\\Firmata\\Pins', $board->pins);
    }

    /**
     * @covers Carica\Firmata\Board::__get
     * @covers Carica\Firmata\Board::__set
     * @covers Carica\Firmata\Board::pins
     */
    public function testGetPropertyPinsAfterSet() {
      $pins = $this
        ->getMockBuilder('Carica\\Firmata\\Pins')
        ->disableOriginalConstructor()
        ->getMock();
      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $board->pins = $pins;
      $this->assertSame($pins, $board->pins);
    }

    /**
     * @covers Carica\Firmata\Board::__get
     */
    public function testGetPropertyVersion() {
      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $this->assertInstanceOf('Carica\\Firmata\\Version', $board->version);
    }

    /**
     * @covers Carica\Firmata\Board::__get
     */
    public function testGetPropertyFirmware() {
      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $this->assertInstanceOf('Carica\\Firmata\\Version', $board->firmware);
    }

    /**
     * @covers Carica\Firmata\Board::__get
     */
    public function testGetPropertyWithUnknownPropertyName() {
      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $this->setExpectedException('LogicException');
      $dummy = $board->INVALID_PROPERTY;
    }

    /**
     * @covers Carica\Firmata\Board::__set
     */
    public function testSetPropertyVersionExpectingException() {
      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $this->setExpectedException('LogicException');
      $board->version = 'trigger';
    }

    /**
     * @covers Carica\Firmata\Board::__set
     */
    public function testSetPropertyFirmwareExpectingException() {
      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $this->setExpectedException('LogicException');
      $board->firmware = 'trigger';
    }

    /**
     * @covers Carica\Firmata\Board::__set
     */
    public function testSetPropertyWithUnknownPropertyName() {
      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $this->setExpectedException('LogicException');
      $board->INVALID_PROPERTY = 'trigger';
    }

    /**
     * @covers Carica\Firmata\Board::activate
     */
    public function testActivateStreamOpenReturnsFalse() {
      $events = $this->getMock('Carica\\Io\\Event\\Emitter');
      $events
        ->expects($this->exactly(3))
        ->method('on')
        ->with($this->logicalOr('error', 'read-data', 'response'));

      $buffer = $this->getMock('Carica\\Firmata\\Buffer');
      $buffer
        ->expects($this->any())
        ->method('events')
        ->will($this->returnValue($events));

      $stream = $this->getMock('Carica\\Io\\Stream\\Tcp');
      $stream
        ->expects($this->any())
        ->method('events')
        ->will($this->returnValue($events));
      $stream
        ->expects($this->once())
        ->method('open')
        ->will($this->returnValue(FALSE));

      $board = new Board($stream);
      $board->buffer($buffer);

      $promise = $board->activate(function(){});
      $this->assertInstanceOf('Carica\\Io\\Deferred\\Promise', $promise);
    }

    /**
     * @covers Carica\Firmata\Board::activate
     */
    public function testActivateStreamErrorRejectsPromise() {
      $events = new \Carica\Io\Event\Emitter();

      $stream = $this->getMock('Carica\\Io\\Stream\\Tcp');
      $stream
        ->expects($this->any())
        ->method('events')
        ->will($this->returnValue($events));
      $stream
        ->expects($this->once())
        ->method('open')
        ->will($this->returnValue(FALSE));

      $board = new Board($stream);

      $result = '';
      $promise = $board->activate();
      $promise->fail(
        function ($message) use (&$result) {
          $result = $message;
        }
      );
      $events->emit('error', 'STREAM_ERROR');
      $this->assertEquals('STREAM_ERROR', $result);
    }

    /**
     * @covers Carica\Firmata\Board
     */
    public function testActivateWithSimpleStartUp() {
      $events = $this->getMock('Carica\\Io\\Event\\Emitter');
      $events
        ->expects($this->exactly(2))
        ->method('on')
        ->with($this->logicalOr('error', 'read-data'));

      $stream = $this->getMock('Carica\\Io\\Stream\\Tcp');
      $stream
        ->expects($this->any())
        ->method('events')
        ->will($this->returnValue($events));
      $stream
        ->expects($this->once())
        ->method('open')
        ->will($this->returnValue(TRUE));

      $board = new Board($stream);
      $promise = $board->activate();
      $board->buffer()->addData(
        "\xF9\x03\x02". // Version
        "\xF0\x79". // Firmware
        "\x02\x03".  // Firmware version
        "\x53\x00\x61\x00\x6D\x00\x70\x00\x6C\x00\x65\x00". // Firmware string
        "\xF7".
        "\xF0\x6C". // Capabilities Response
        "\x00\x01\x01\x01\x03\x08\x04\x0e\x7f". // pin 0
        "\x00\x01\x01\x01\x02\x0a\x06\x01\x7f". // pin 1
        "\xF7".
        "\xF0\x6A". // Analog Mapping Response
        "\x7F\x00".
        "\xF7"
      );
      $this->assertInstanceOf('Carica\\Io\\Deferred\\Promise', $promise);
      $this->assertEquals(\Carica\Io\Deferred::STATE_RESOLVED, $promise->state());
      $this->assertEquals('3.2', (string)$board->version);
      $this->assertEquals('Sample 2.3', (string)$board->firmware);
      $this->assertCount(2, $board->pins);
    }

    /**
     * @covers Carica\Firmata\Board::onResponse
     */
    public function testOnResponseWithUnknownResponseExpectingException() {
      $response = $this
        ->getMockBuilder('Carica\\Firmata\\Response\\SysEx')
        ->disableOriginalConstructor()
        ->getMock();
      $response
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('command', 0x00)
            )
          )
        );
      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $this->setExpectedException(
        'UnexpectedValueException',
        'Unknown response command: 0x00'
      );
      $board->onResponse($response);
    }

    /**
     * @covers Carica\Firmata\Board::onResponse
     * @covers Carica\Firmata\Board::onQueryFirmware
     */
    public function testOnResponseWithQueryFirmware() {
      $response = $this
        ->getMockBuilder('Carica\\Firmata\\Response\\SysEx\\QueryFirmware')
        ->disableOriginalConstructor()
        ->getMock();
      $response
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('command', Board::QUERY_FIRMWARE),
              array('major', 42),
              array('minor', 21),
              array('name', 'TEST')
            )
          )
        );
      $events = $this->getMock('Carica\\Io\\Event\\Emitter');
      $events
        ->expects($this->once())
        ->method('emit')
        ->with('queryfirmware');

      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $board->events($events);
      $board->onResponse($response);
      $this->assertEquals('TEST 42.21', (string)$board->firmware);
    }

    /**
     * @covers Carica\Firmata\Board::onResponse
     * @covers Carica\Firmata\Board::onCapabilityResponse
     */
    public function testOnResponseWithCapabilityResponse() {
      $response = $this
        ->getMockBuilder('Carica\\Firmata\\Response\\SysEx\\CapabilityResponse')
        ->disableOriginalConstructor()
        ->getMock();
      $response
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('command', Board::CAPABILITY_RESPONSE),
              array('pins', array(1 => array(Board::PIN_MODE_PWM => 255)))
            )
          )
        );
      $events = $this->getMock('Carica\\Io\\Event\\Emitter');
      $events
        ->expects($this->once())
        ->method('emit')
        ->with('capability-query');

      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $board->events($events);
      $board->onResponse($response);
      $this->assertTrue(isset($board->pins[1]));
      $this->assertTrue($board->pins[1]->supports(Board::PIN_MODE_PWM));
    }

    /**
     * @covers Carica\Firmata\Board::onResponse
     * @covers Carica\Firmata\Board::onAnalogMappingResponse
     */
    public function testOnResponseWithAnalogMappingResponse() {
      $response = $this
        ->getMockBuilder('Carica\\Firmata\\Response\\SysEx\\AnalogMappingResponse')
        ->disableOriginalConstructor()
        ->getMock();
      $response
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('command', Board::ANALOG_MAPPING_RESPONSE),
              array('channels', array(0 => 14))
            )
          )
        );
      $events = $this->getMock('Carica\\Io\\Event\\Emitter');
      $events
        ->expects($this->once())
        ->method('emit')
        ->with('analog-mapping-query');

      $pins = $this
        ->getMockBuilder('Carica\\Firmata\\Pins')
        ->disableOriginalConstructor()
        ->getMock();
      $pins
        ->expects($this->once())
        ->method('setAnalogMapping')
        ->with([0 => 14]);

      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $board->events($events);
      $board->pins = $pins;

      $board->onResponse($response);
    }

    /**
     * @covers Carica\Firmata\Board::onResponse
     * @covers Carica\Firmata\Board::onAnalogMessage
     */
    public function testOnResponseWithAnalogMessage() {
      $response = $this
        ->getMockBuilder('Carica\\Firmata\\Response\\Midi\\Message')
        ->disableOriginalConstructor()
        ->getMock();
      $response
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('command', Board::ANALOG_MESSAGE),
              array('port', 21),
              array('value', 23)
            )
          )
        );
      $events = $this->getMock('Carica\\Io\\Event\\Emitter');
      $events
        ->expects($this->at(0))
        ->method('emit')
        ->with('analog-read-42', 23);
      $events
        ->expects($this->at(1))
        ->method('emit')
        ->with('analog-read', ['pin' => 42, 'value' => 23]);

      $pins = $this
        ->getMockBuilder('Carica\\Firmata\\Pins')
        ->disableOriginalConstructor()
        ->getMock();
      $pins
        ->expects($this->once())
        ->method('getPinByChannel')
        ->with(21)
        ->will($this->returnValue(42));

      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $board->events($events);
      $board->pins = $pins;

      $board->onResponse($response);
    }

    /**
     * @covers Carica\Firmata\Board::onResponse
     * @covers Carica\Firmata\Board::onDigitalMessage
     */
    public function testOnResponseWithDigitalMessage() {
      $response = $this
        ->getMockBuilder('Carica\\Firmata\\Response\\Midi\\Message')
        ->disableOriginalConstructor()
        ->getMock();
      $response
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('command', Board::DIGITAL_MESSAGE),
              array('port', 2),
              array('value', 0x80)
            )
          )
        );
      $pins = $this
        ->getMockBuilder('Carica\\Firmata\\Pins')
        ->disableOriginalConstructor()
        ->getMock();
      $pins
        ->expects($this->any())
        ->method('offsetExists')
        ->will(
          $this->returnCallback(
            function ($pin) {
              return in_array($pin, [17, 20]);
            }
          )
        );
      $pins
        ->expects($this->any())
        ->method('offsetGet')
        ->will(
          $this->returnValueMap(
            [
              [
                17,
                $this->getPinFixture(
                  ['mode' => Board::PIN_MODE_INPUT, 'value' => Board::DIGITAL_HIGH]
                )
              ],
              [
                20,
                $this->getPinFixture(
                  ['mode' => Board::PIN_MODE_OUTPUT, 'value' => Board::DIGITAL_HIGH]
                )
              ]
            ]
          )
        );

      $events = $this->getMock('Carica\\Io\\Event\\Emitter');
      $events
        ->expects($this->at(0))
        ->method('emit')
        ->with('digital-read-17', Board::DIGITAL_LOW);
      $events
        ->expects($this->at(1))
        ->method('emit')
        ->with('digital-read', ['pin' => 17, 'value' => Board::DIGITAL_LOW]);

      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $board->events($events);
      $board->pins = $pins;

      $board->onResponse($response);
    }

    /**
     * @covers Carica\Firmata\Board::onResponse
     * @covers Carica\Firmata\Board::onStringData
     */
    public function testOnResponseWithStringData() {
      $response = $this
        ->getMockBuilder('Carica\\Firmata\\Response\\SysEx\\String')
        ->disableOriginalConstructor()
        ->getMock();
      $response
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('command', Board::STRING_DATA),
              array('text', 'Hello World!')
            )
          )
        );
      $events = $this->getMock('Carica\\Io\\Event\\Emitter');
      $events
        ->expects($this->once())
        ->method('emit')
        ->with('string', 'Hello World!');

      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $board->events($events);
      $board->onResponse($response);
    }

    /**
     * @covers Carica\Firmata\Board::onResponse
     * @covers Carica\Firmata\Board::onPinStateResponse
     */
    public function testOnResponseWithPinStateResponse() {
      $response = $this
        ->getMockBuilder('Carica\\Firmata\\Response\\SysEx\\PinStateResponse')
        ->disableOriginalConstructor()
        ->getMock();
      $response
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('command', Board::PIN_STATE_RESPONSE),
              array('pin', 42),
              array('mode', Board::PIN_MODE_PWM),
              array('value', 23)
            )
          )
        );
      $events = $this->getMock('Carica\\Io\\Event\\Emitter');
      $events
        ->expects($this->once())
        ->method('emit')
        ->with('pin-state-42', Board::PIN_MODE_PWM, 23);

      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $board->events($events);
      $board->onResponse($response);
    }

    /**
     * @covers Carica\Firmata\Board::onResponse
     * @covers Carica\Firmata\Board::onI2CReply
     */
    public function testOnResponseWithI2CReply() {
      $response = $this
        ->getMockBuilder('Carica\\Firmata\\Response\\SysEx\\I2CReply')
        ->disableOriginalConstructor()
        ->getMock();
      $response
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('command', Board::I2C_REPLY),
              array('slaveAddress', 42),
              array('data', 'Hello World!')
            )
          )
        );
      $events = $this->getMock('Carica\\Io\\Event\\Emitter');
      $events
        ->expects($this->once())
        ->method('emit')
        ->with('I2C-reply-42', 'Hello World!');

      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $board->events($events);
      $board->onResponse($response);
    }

    /**
     * @covers Carica\Firmata\Board::onResponse
     * @covers Carica\Firmata\Board::onPulseIn
     */
    public function testOnResponseWithPulseIn() {
      $response = $this
        ->getMockBuilder('Carica\\Firmata\\Response\\SysEx\\PulseIn')
        ->disableOriginalConstructor()
        ->getMock();
      $response
        ->expects($this->any())
        ->method('__get')
        ->will(
          $this->returnValueMap(
            array(
              array('command', Board::PULSE_IN),
              array('pin', 42),
              array('duration', 23)
            )
          )
        );
      $events = $this->getMock('Carica\\Io\\Event\\Emitter');
      $events
        ->expects($this->at(0))
        ->method('emit')
        ->with('pulse-in-42', 23);
      $events
        ->expects($this->at(1))
        ->method('emit')
        ->with('pulse-in', 42, 23);

      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $board->events($events);
      $board->onResponse($response);
    }

    /**
     * @covers Carica\Firmata\Board::reset
     */
    public function testReset() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::SYSTEM_RESET]);

      $board = new Board($stream);
      $board->reset();
    }

    /**
     * @covers Carica\Firmata\Board::reportVersion
     */
    public function testReportVersion() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::REPORT_VERSION]);
      $board = new Board($stream);
      $board->reportVersion(function() {});
      $this->assertCount(
        1, $board->events()->listeners('response')
      );
      $this->assertCount(
        2, $board->events()->listeners('reportversion')
      );
    }

    /**
     * @covers Carica\Firmata\Board::queryFirmware
     */
    public function testQueryFirmware() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::QUERY_FIRMWARE, Board::END_SYSEX]);
      $board = new Board($stream);
      $board->queryFirmware(function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('queryfirmware')
      );
    }

    /**
     * @covers Carica\Firmata\Board::queryCapabilities
     */
    public function testQueryCapabilities() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::CAPABILITY_QUERY, Board::END_SYSEX]);
      $board = new Board($stream);
      $board->queryCapabilities(function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('capability-query')
      );
    }

    /**
     * @covers Carica\Firmata\Board::queryAnalogMapping
     */
    public function testQueryAnalogMapping() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::ANALOG_MAPPING_QUERY, Board::END_SYSEX]);
      $board = new Board($stream);
      $board->queryAnalogMapping(function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('analog-mapping-query')
      );
    }

    /**
     * @covers Carica\Firmata\Board::queryPinState
     */
    public function testQueryPinState() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::PIN_STATE_QUERY, 0x2A, Board::END_SYSEX]);
      $board = new Board($stream);
      $board->queryPinState(42, function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('pin-state-42')
      );
    }

    /**
     * @covers Carica\Firmata\Board::queryAllPinStates
     */
    public function testQueryAllPinStates() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::START_SYSEX, Board::PIN_STATE_QUERY, 0x2A, Board::END_SYSEX]);
      $board = new Board($stream);
      $board->pins = $this->getPinsFixture(
        [
          42 => $this->getPinFixture()
        ]
      );
      $board->queryAllPinStates();
    }

    /**
     * @covers Carica\Firmata\Board::analogRead
     */
    public function testAnalogRead() {
      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $board->analogRead(42, $callback = function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('analog-read-42')
      );
    }

    /**
     * @covers Carica\Firmata\Board::digitalRead
     */
    public function testDigitalRead() {
      $board = new Board($this->getMock('Carica\\Io\\Stream'));
      $board->digitalRead(42, $callback = function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('digital-read-42')
      );
    }

    /**
     * @covers Carica\Firmata\Board::analogWrite
     */
    public function testAnalogWrite() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([0xE3, 0x17, 0x00]);
      $pin = $this
        ->getMockBuilder('Carica\\Firmata\\Pin')
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('setValue')
        ->with(23);

      $board = new Board($stream);
      $board->pins = $this->getPinsFixture([3 => $pin]);
      $board->analogWrite(3, 23);
    }

    /**
     * @covers Carica\Firmata\Board::analogWrite
     */
    public function testAnalogWriteWithHighPinNumber() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with(
          [
            Board::START_SYSEX,
            Board::EXTENDED_ANALOG,
            0x2A,
            0x00,
            Board::END_SYSEX
          ]
        );
      $pin = $this
        ->getMockBuilder('Carica\\Firmata\\Pin')
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('setValue')
        ->with(0);

      $board = new Board($stream);
      $board->pins = $this->getPinsFixture([42 => $pin]);
      $board->analogWrite(42, 0);
    }

    /**
     * @covers Carica\Firmata\Board::analogWrite
     */
    public function testAnalogWriteWithLargeValue() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with(
          [
            Board::START_SYSEX,
            Board::EXTENDED_ANALOG,
            0x03,
            0x20,
            0x0D,
            0x06,
            Board::END_SYSEX
          ]
        );
      $pin = $this
        ->getMockBuilder('Carica\\Firmata\\Pin')
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('setValue')
        ->with(100000);

      $board = new Board($stream);
      $board->pins = $this->getPinsFixture([3 => $pin]);
      $board->analogWrite(3, 100000);
    }

    /**
     * @covers Carica\Firmata\Board::servoWrite
     */
    public function testServoWrite() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([0xE3, 0x17, 0x00]);
      $pin = $this
        ->getMockBuilder('Carica\\Firmata\\Pin')
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('setValue')
        ->with(23);

      $board = new Board($stream);
      $board->pins = $this->getPinsFixture([3 => $pin]);
      $board->servoWrite(3, 23);
    }

    /**
     * @covers Carica\Firmata\Board::digitalWrite
     */
    public function testDigitalWrite() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([0x91, 0x0B, 0x00]);
      $pin = $this->getPinFixture(
        ['pin' => 8, 'mode' => Board::PIN_MODE_OUTPUT, 'digital' => TRUE]
      );
      $pin
        ->expects($this->once())
        ->method('setDigital')
        ->with(Board::DIGITAL_HIGH);

      $board = new Board($stream);
      $board->pins = $this->getPinsFixture(
        [
          8 => $pin,
          9 => $this->getPinFixture(
            ['pin' => 9, 'mode' => Board::PIN_MODE_OUTPUT, 'digital' => TRUE]
          ),
          10 => $this->getPinFixture(
            ['pin' => 10, 'mode' => Board::PIN_MODE_OUTPUT, 'digital' => FALSE]
          ),
          11 => $this->getPinFixture(
            ['pin' => 11, 'mode' => Board::PIN_MODE_OUTPUT, 'digital' => TRUE]
          ),
          24 => $this->getPinFixture(
            ['pin' => 24, 'mode' => Board::PIN_MODE_OUTPUT, 'digital' => TRUE]
          )
        ]
      );
      $board->digitalWrite(8, Board::DIGITAL_HIGH);
    }

    /**
     * @covers Carica\Firmata\Board::pinMode
     */
    public function testPinMode() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with([Board::PIN_MODE, 0x2A, Board::PIN_MODE_OUTPUT]);
      $pin = $this
        ->getMockBuilder('Carica\\Firmata\\Pin')
        ->disableOriginalConstructor()
        ->getMock();
      $pin
        ->expects($this->once())
        ->method('setMode')
        ->with(Board::PIN_MODE_OUTPUT);

      $board = new Board($stream);
      $board->pins = $this->getPinsFixture([42 => $pin]);
      $board->pinMode(42, Board::PIN_MODE_OUTPUT);
    }

    /**
     * @covers Carica\Firmata\Board::sendI2CConfig
     */
    public function testSendI2CConfig() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with(
          [
             Board::START_SYSEX,
             Board::I2C_CONFIG,
             0x00,
             0x27,
             board::END_SYSEX
          ]
        );
      $board = new Board($stream);
      $board->sendI2CConfig(10000);
    }

    /**
     * @covers Carica\Firmata\Board::sendI2CWriteRequest
     */
    public function testSendI2CWriteRequest() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with("\xF0\x76\x03\x00\x48\x00\x61\x00\x6C\x00\x6C\x00\x6F\x00\xF7");
      $board = new Board($stream);
      $board->sendI2CWriteRequest(3, 'Hallo');
    }

    /**
     * @covers Carica\Firmata\Board::sendI2CReadRequest
     */
    public function testSendI2CReadRequest() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with(
          [
            Board::START_SYSEX,
            Board::I2C_REQUEST,
            0x02,
            0x08,
            0x07,
            0x00,
            Board::END_SYSEX
          ]
        );
      $board = new Board($stream);
      $board->sendI2CReadRequest(2, 7, function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('I2C-reply-2')
      );
    }

    /**
     * @covers Carica\Firmata\Board::pulseIn
     */
    public function testPulseIn() {
      $stream = $this->getMock('Carica\\Io\\Stream');
      $stream
        ->expects($this->once())
        ->method('write')
        ->with("\xF0\x74\x2A\x01\x00\x00\x00\x00\x00\x00\x05\x00\x00\x00\x0f\x00\x42\x00\x40\x00\xF7");
      $board = new Board($stream);
      $board->pulseIn(42, function() {});
      $this->assertCount(
         1,
         $board->events()->listeners('pulse-in-42')
      );
    }

    /****************************
     * Fixtures
     ***************************/

    private function getPinFixture($data = []) {
      $pin = $this
        ->getMockBuilder('Carica\\Firmata\\Pin')
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

    private function getPinsFixture(array $pins = []) {
      $result = $this
        ->getMockBuilder('Carica\\Firmata\\Pins')
        ->disableOriginalConstructor()
        ->getMock();
      $result
        ->expects($this->any())
        ->method('offsetExists')
        ->will(
          $this->returnCallback(
            function($pinNumber) use ($pins) { return isset($pins[$pinNumber]); }
          )
        );
      $result
        ->expects($this->any())
        ->method('offsetGet')
        ->will(
          $this->returnCallback(
            function($pinNumber) use ($pins) { return $pins[$pinNumber]; }
          )
        );
      $result
        ->expects($this->any())
        ->method('getIterator')
        ->will(
          $this->returnValue(
            new \ArrayIterator($pins)
          )
        );
      return $result;
    }
  }
}