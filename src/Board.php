<?php

namespace Carica\Firmata {

  use Carica\Io;
  use Carica\Io\Event;

  /**
   * This class represents an Arduino board running firmata.
   *
   * @property-read Version $version
   * @property-read Version $firmware
   * @property-read Pins $pins
   */
  class Board
    implements Event\HasEmitter {

    use Event\Emitter\Aggregation;
    use Event\Loop\Aggregation;

    const PIN_MODE = 0xF4;
    const REPORT_DIGITAL = 0xD0;
    const REPORT_ANALOG = 0xC0;
    const DIGITAL_MESSAGE = 0x90;
    const START_SYSEX = 0xF0;
    const END_SYSEX = 0xF7;
    const QUERY_FIRMWARE = 0x79;
    const REPORT_VERSION = 0xF9;
    const ANALOG_MESSAGE = 0xE0;
    const CAPABILITY_QUERY = 0x6B;
    const CAPABILITY_RESPONSE = 0x6C;
    const PIN_STATE_QUERY = 0x6D;
    const PIN_STATE_RESPONSE = 0x6E;
    const EXTENDED_ANALOG = 0x6F;
    const ANALOG_MAPPING_QUERY = 0x69;
    const ANALOG_MAPPING_RESPONSE = 0x6A;
    const STRING_DATA = 0x71;
    const SYSTEM_RESET = 0xFF;

    const PIN_MODE_UNKNOWN = 0xFF; // internal state to recognize unininitialized pins
    const PIN_MODE_INPUT = 0x00;
    const PIN_MODE_OUTPUT = 0x01;
    const PIN_MODE_ANALOG = 0x02;
    const PIN_MODE_PWM = 0x03;
    const PIN_MODE_SERVO = 0x04;
    const PIN_MODE_SHIFT = 0x05;
    const PIN_MODE_I2C = 0x06;

    const DIGITAL_LOW = 0;
    const DIGITAL_HIGH = 1;

    /**
     * @var \Carica\Firmata\Pins
     */
    private $_pins = NULL;

    /**
     * @var \Carica\Io\Stream
     */
    private $_stream = NULL;

    /**
     * @var array Data Buffer
     */
    private $_buffer = [];

    /**
     * @var bool Set to true after here was a version byte on the buffer
     */
    private $_bufferVersionReceived = false;

    /**
     * Firmata version information
     * @var Version
     */
    private $_version = NULL;

    /**
     * Firmware version information
     * @var Version
     */
    private $_firmware= NULL;


    /**
     * Create board and assign stream object
     *
     * @param Io\Stream $stream
     */
    public function __construct(Io\Stream $stream) {
      $this->_stream = $stream;
      $this->_stream->events()->on(
        'read-data',
        function($data) {
          if (count($this->_buffer) == 0) {
            $data = ltrim($data, pack('C', 0));
          }
          $bytes = array_slice(unpack("C*", "\0".$data), 1);
          foreach ($bytes as $byte) {
            $this->handleData($byte);
          }
        }
      );
    }

    /**
     * Validate if the board ist still active (the port/stream contains a valid resource)
     *
     * @return boolean
     */
    public function isActive() {
      return $this->stream()->isOpen();
    }

    /**
     * Getter for the port/stream object
     *
     * @return Io\Stream
     */
    public function stream() {
      return $this->_stream;
    }

    /**
     * Pin list subobject
     *
     * @param Pins $pins
     * @return Pins
     */
    public function pins(Pins $pins = NULL) {
      if (isset($pins)) {
        $this->_pins = $pins;
      } elseif (NULL === $this->_pins) {
        // create an empty pins objects
        $this->_pins = new Pins($this, []);
      }
      return $this->_pins;
    }

    /**
     * Activate the board, assign the needed callbacks
     *
     * @param Callable|NULL $callback
     * @return Io\Deferred\Promise
     */
    public function activate(Callable $callback = NULL) {
      $defer = new Io\Deferred();
      if (isset($callback)) {
        $defer->always($callback);
      }
      $this->_stream->events()->once(
        'error',
        function($message) use ($defer) {
          $defer->reject($message);
        }
      );
      if ($this->stream()->open()) {
        $board = $this;
        $board->reportVersion(
          function() use ($board, $defer) {
            $board->queryFirmware(
              function() use ($board, $defer) {
                $board->queryCapabilities(
                  function() use ($board, $defer) {
                    $board->queryAnalogMapping(
                      function() use ($defer) {
                        $defer->resolve();
                      }
                    );
                  }
                );
              }
            );
          }
        );
      }
      return $defer->promise();
    }

    /**
     * Provide some properties
     *
     * @param string $name
     * @throws \LogicException
     * @return mixed
     */
    public function __get($name) {
      switch ($name) {
      case 'version' :
        return isset($this->_version) ? $this->_version : new Version(0,0);
      case 'firmware' :
        return isset($this->_firmware) ? $this->_firmware : new Version(0,0);
      case 'pins' :
        return $this->pins();
      }
      throw new \LogicException(sprintf('Unknown property %s::$%s', __CLASS__, $name));
    }

    /**
     * Provide properties
     *
     * @param string $name
     * @param mixed $value
     *
     * @throws \LogicException
     */
    public function __set($name, $value) {
      switch ($name) {
      case 'version' :
      case 'firmware' :
        throw new \LogicException(
          sprintf('Property %s::$%s is not writeable.', __CLASS__, $name)
        );
      case 'pins' :
        $this->pins($value);
        return;
      }
      throw new \LogicException(sprintf('Unknown property %s::$%s', __CLASS__, $name));
    }

    /**
     * @param int $byte
     */
    private function handleData($byte) {
      if (!$this->_bufferVersionReceived) {
        if ($byte !== Board::REPORT_VERSION) {
          return;
        } else {
          $this->_bufferVersionReceived = TRUE;
        }
      }
      $byteCount = count($this->_buffer);
      if ($byte == 0 && $byteCount == 0) {
        return;
      } else {
        $this->_buffer[] = $byte;
        ++$byteCount;
      }
      if ($byteCount > 0) {
        $first = reset($this->_buffer);
        $last = end($this->_buffer);
        if ($first === Board::START_SYSEX &&
            $last === Board::END_SYSEX) {
          if ($byteCount > 2) {
            $this->handleExtendedMessage(
              $this->_buffer[1], array_slice($this->_buffer, 2, -1)
            );
          }
          $this->_buffer = array();
        } elseif ($byteCount == 3 && $first !== Board::START_SYSEX) {
          $command = ($first < 240) ? ($first & 0xF0) : $first;
          $this->handleMessage($command, $this->_buffer);
          $this->_buffer = array();
        }
      }
    }

    /**
     * Callback for the buffer, received a message from the board.
     *
     * @param int $command
     * @param array $rawData
     *
     */
    private function handleMessage($command, $rawData) {
      switch ($command) {
      case self::REPORT_VERSION :
        $this->handleVersionMessage(
          new Response\Midi\ReportVersion($rawData)
        );
        return;
      case self::ANALOG_MESSAGE :
        $this->handleAnalogMessage(
          new Response\Midi\Message($command, $rawData)
        );
        return;
      case self::DIGITAL_MESSAGE :
        $this->handleDigitalMessage(
          new Response\Midi\Message($command, $rawData)
        );
        return;
      default :
        $this->events()->emit('response', new Response($command, $rawData));
        return;
      }
    }

    /**
     * @param Response\Midi\ReportVersion $response
     */
    private function handleVersionMessage(Response\Midi\ReportVersion $response) {
      $this->_version = new Version($response->major, $response->minor);
      for ($i = 0; $i < 16; $i++) {
        $this->stream()->write([self::REPORT_DIGITAL | $i, 1]);
        $this->stream()->write([self::REPORT_ANALOG | $i, 1]);
      }
      $this->events()->emit('reportversion');
    }

    /**
     * Got an analog message, change pin value and emit events
     *
     * @param Response\Midi\Message $response
     */
    private function handleAnalogMessage(Response\Midi\Message $response) {
      if (0 <= ($pinNumber = $this->pins->getPinByChannel($response->port))) {
        $this->events()->emit('analog-read-'.$pinNumber, $response->value);
        $this->events()->emit('analog-read', ['pin' => $pinNumber, 'value' => $response->value]);
      }
    }

    /**
     * Got a digital message, change pin value and emit events
     *
     * @param Response\Midi\Message $response
     */
    private function handleDigitalMessage(Response\Midi\Message $response) {
      $firstPin = 8 * $response->port;
      for ($i = 0; $i < 8; $i++) {
        $pinNumber = $firstPin + $i;
        if (isset($this->pins[$pinNumber])) {
          $pin = $this->pins[$pinNumber];
          if ($pin->mode == self::PIN_MODE_INPUT) {
            $value = ($response->value >> ($i & 0x07)) & 0x01;
          } else {
            $value = $pin->value;
          }
          $this->events()->emit('digital-read-'.$pinNumber, $value);
          $this->events()->emit('digital-read', ['pin' => $pinNumber, 'value' => $value]);
        }
      }
    }

    /**
     * Callback for the buffer, received an extended (SysEx) message from the board.
     *
     * @param int $command
     * @param array $rawData
     *
     */
    private function handleExtendedMessage($command, $rawData) {
      switch ($command) {
      case self::STRING_DATA :
        $this->events()->emit('string', Response::decodeBytes($rawData));
        return;
      case self::QUERY_FIRMWARE :
        $response = new Response\SysEx\QueryFirmware($rawData);
        $this->_firmware = new Version($response->major, $response->minor, $response->name);
        $this->events()->emit('queryfirmware');
        return;
      case self::CAPABILITY_RESPONSE :
        $response = new Response\SysEx\CapabilityResponse($rawData);
        $this->pins(new Pins($this, $response->pins));
        $this->events()->emit('capability-query');
        return;
      case self::PIN_STATE_RESPONSE :
        $response = new Response\SysEx\PinStateResponse($rawData);
        $this->events()->emit('pin-state-'.$response->pin, $response->mode, $response->value);
        $this->events()->emit('pin-state', $response->pin, $response->mode, $response->value);
        return;
      case self::ANALOG_MAPPING_RESPONSE :
        $response = new Response\SysEx\AnalogMappingResponse($rawData);
        $this->pins->setAnalogMapping($response->channels);
        $this->events()->emit('analog-mapping-query');
        return;
      default :
        $this->events()->emit('response', new Response($command, $rawData));
        return;
      }
    }

    /**
     * Reset board
     */
    public function reset() {
      $this->stream()->write([self::SYSTEM_RESET]);
    }

    /**
     * Request version from board and execute callback after it is recieved.
     *
     * On quite a few board combinations, the board may well miss the version command on the inital
     * connection. In these instances we need to retry.
     *
     * @param callable $callback
     */
    public function reportVersion(Callable $callback) {
      $this->stream()->write([self::REPORT_VERSION]);
      $interval = $this->loop()->setInterval(
        function() {
          static $counter = 0;
          if (!$this->_bufferVersionReceived && ++$counter < 30) {
            $this->stream()->write([self::REPORT_VERSION]);
          }
        },
        1000
      );
      $this->events()->once(
        'reportversion',
        function () use ($interval, $callback) {
          $this->loop()->remove($interval);
          if (isset($callback)) {
            $callback();
          }
        }
      );
    }

    /**
     * Request firmware from board and execute callback after it is recieved.
     *
     * @param callable $callback
     */
    public function queryFirmware(Callable $callback) {
      $this->events()->once('queryfirmware', $callback);
      $this->stream()->write([self::START_SYSEX, self::QUERY_FIRMWARE, self::END_SYSEX]);
    }

    /**
     * Query pin capabilities and execute callback after they are recieved
     *
     * @param callable $callback
     */
    public function queryCapabilities(Callable $callback) {
      $this->events()->once('capability-query', $callback);
      $this->stream()->write([self::START_SYSEX, self::CAPABILITY_QUERY, self::END_SYSEX]);
    }

    /**
     * Request the analog mapping data and execute callback after it is recieved
     *
     * @param callable $callback
     */
    public function queryAnalogMapping(Callable  $callback) {
      $this->events()->once('analog-mapping-query', $callback);
      $this->stream()->write([self::START_SYSEX, self::ANALOG_MAPPING_QUERY, self::END_SYSEX]);
    }

    /**
     * Query pin status (mode and value), and execute callback after it recieved
     *
     * @param integer $pin 0-16
     * @param callable $callback
     */
    public function queryPinState($pin, Callable $callback) {
      $this->events()->once('pin-state-'.$pin, $callback);
      $this->stream()->write([self::START_SYSEX, self::PIN_STATE_QUERY, $pin, self::END_SYSEX]);
    }

    /**
     * Query the status of each pin, this will update all pin objects
     */
    public function queryAllPinStates() {
      foreach ($this->pins as $index => $pin) {
        $this->stream()->write([self::START_SYSEX, self::PIN_STATE_QUERY, $index, self::END_SYSEX]);
      }
    }

    /**
     * Add a callback for analog read events on a pin
     *
     * @param integer $pin 0-16
     * @param Callable $callback
     */
    public function analogRead($pin, Callable $callback) {
      $this->events()->on('analog-read-'.$pin, $callback);
    }

    /**
     * Add a callback for diagital read events on a pin
     *
     * @param integer $pin 0-16
     * @param callable $callback
     */
    public function digitalRead($pin, Callable $callback) {
      $this->events()->on('digital-read-'.$pin, $callback);
    }


    /**
     * Write an analog value for a pin
     *
     * @param integer $pin
     * @param integer $value
     */
    public function analogWrite($pin, $value) {
      /** @noinspection PhpUndefinedMethodInspection */
      $this->pins[$pin]->setValue($value);
      if ($pin > 15 || $value > 255) {
        $bytes = [self::START_SYSEX, self::EXTENDED_ANALOG, $pin];
        do {
          $bytes[] = $value & 0x7F;
          $value = $value >> 7;
        } while ($value > 0);
        $bytes[] = self::END_SYSEX;
      } else {
        $bytes = [self::ANALOG_MESSAGE | $pin, $value & 0x7F, ($value >> 7) & 0x7F];

      }
      $this->stream()->write($bytes);
    }

    /**
     * Move a servo - an alias for analogWrite()
     *
     * @param integer $pin 0-16
     * @param integer $value 0-255
     */
    public function servoWrite($pin, $value) {
      $this->analogWrite($pin, $value);
    }

    /**
     * Write a digital value for a pin (on/off, self::DIGITAL_LOW/self::DIGITAL_HIGH)
     *
     * @param integer $pin 0-16
     * @param integer $value 0-1
     */
    public function digitalWrite($pin, $value) {
      /** @noinspection PhpUndefinedMethodInspection */
      $this->pins[$pin]->setDigital($value == self::DIGITAL_HIGH);
      $port = (int)floor($pin / 8);
      $portValue = 0;
      for ($i = 0; $i < 8; $i++) {
        $index = 8 * $port + $i;
        if (isset($this->pins[$index]) && $this->pins[$index]->digital) {
          $portValue |= (1 << $i);
        }
      }
      $this->stream()->write(
        [self::DIGITAL_MESSAGE | $port, $portValue & 0x7F, ($portValue >> 7) & 0x7F]
      );
    }

    /**
     * Set the mode of a pin:
     *   Carica\Firmata::PIN_MODE_INPUT,
     *   Carica\Firmata::PIN_MODE_OUTPUT,
     *   Carica\FirmataPIN_MODE_ANALOG,
     *   Carica\FirmataPIN_MODE_PWM,
     *   Carica\FirmataPIN_MODE_SERVO
     *
     * @param integer $pin 0-16
     * @param integer $mode
     */
    public function pinMode($pin, $mode) {
      /** @noinspection PhpUndefinedMethodInspection */
      $this->pins[$pin]->setMode($mode);
      $this->stream()->write([self::PIN_MODE, $pin, $mode]);
    }
  }
}
