<?php

namespace Carica\Firmata {

  use Carica\Io;
  use Carica\Io\Deferred\Promise;
  use Carica\Io\Event;

  /**
   * This class represents an Arduino board running firmata.
   *
   * @property-read Version $version
   * @property-read Version $firmware
   * @property Pins|Pin[] $pins
   */
  class Board
    implements Event\HasEmitter {

    use Event\Emitter\Aggregation;
    use Event\Loop\Aggregation;

    public const PIN_MODE = 0xF4;
    public const REPORT_DIGITAL = 0xD0;
    public const REPORT_ANALOG = 0xC0;
    public const DIGITAL_MESSAGE = 0x90;
    public const START_SYSEX = 0xF0;
    public const END_SYSEX = 0xF7;
    public const QUERY_FIRMWARE = 0x79;
    public const REPORT_VERSION = 0xF9;
    public const ANALOG_MESSAGE = 0xE0;
    public const CAPABILITY_QUERY = 0x6B;
    public const CAPABILITY_RESPONSE = 0x6C;
    public const PIN_STATE_QUERY = 0x6D;
    public const PIN_STATE_RESPONSE = 0x6E;
    public const EXTENDED_ANALOG = 0x6F;
    public const ANALOG_MAPPING_QUERY = 0x69;
    public const ANALOG_MAPPING_RESPONSE = 0x6A;
    public const STRING_DATA = 0x71;
    public const SYSTEM_RESET = 0xFF;

    public const DIGITAL_LOW = 0;
    public const DIGITAL_HIGH = 1;

    // States for the activation steps
    public const ACTIVATION_STARTED = 'activation_started';
    public const FETCHING_VERSION = 'fetch_version';
    public const FETCHING_FIRMWARE = 'fetch_firmware';
    public const FETCHING_CAPABILITIES = 'fetch_capabilities';
    public const FETCHING_ANALOG_MAPPING = 'fetch_analog_mapping';
    public const ACTIVATION_FINISHED = 'activation_finished';

    public const EVENT_REACTIVATE = 'reactivate';
    public const EVENT_RESPONSE = 'response';
    public const EVENT_REPORTVERSION = 'reportversion';
    public const EVENT_ANALOG_READ = 'analog-read';
    public const EVENT_DIGITAL_READ = 'digital-read';
    public const EVENT_RECIEVE_STRING = 'string';
    public const EVENT_QUERYFIRMWARE = 'queryfirmware';
    public const EVENT_CAPABILITY_QUERY = 'capability-query';
    public const EVENT_PIN_STATE = 'pin-state';
    public const EVENT_ANALOG_MAPPING_QUERY = 'analog-mapping-query';

    /**
     * @var \Carica\Firmata\Pins
     */
    private $_pins;

    /**
     * @var Io\Stream
     */
    private $_stream;

    /**
     * @var array Data Buffer
     */
    private $_buffer = [];

    /**
     * @var bool Set to true after here was a version byte on the buffer
     */
    private $_bufferVersionReceived = FALSE;

    /**
     * Firmata version information
     *
     * @var Version
     */
    private $_version;

    /**
     * Firmware version information
     *
     * @var Version
     */
    private $_firmware;

    /**
     * @var mixed
     */
    private $_heartBeat;
    /**
     * @var int
     */
    private $_heartBeatInterval = 5000;
    /**
     * @var int
     */
    private $_heartBeatMissed = 0;
    /**
     * @var int
     */
    private $_activationTry = 0;
    /**
     * @var bool
     */
    private $_isActivated = FALSE;


    /**
     * Create board and assign stream object
     *
     * @param Io\Stream $stream
     */
    public function __construct(Io\Stream $stream) {
      $this->_stream = $stream;
      $this->_stream->events()->on(
        'read-data',
        function ($data) {
          if (\count($this->_buffer) === 0) {
            $data = ltrim($data, pack('C', 0));
          }
          $bytes = \array_slice(unpack('C*', "\0".$data), 1);
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
    public function isActive(): bool {
      return $this->_isActivated && $this->stream()->isOpen();
    }

    /**
     * Getter for the port/stream object
     *
     * @return Io\Stream
     */
    public function stream(): Io\Stream {
      return $this->_stream;
    }

    /**
     * Pin list subobject
     *
     * @param Pins $pins
     * @return Pins
     */
    public function pins(Pins $pins = NULL): Pins {
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
     * @return Promise
     */
    public function activate(Callable $callback = NULL, $withHeartBeat = TRUE): Promise {
      $this->_isActivated = FALSE;
      $this->_activationTry = 1;
      $activation = new Io\Deferred();
      if (isset($callback)) {
        $activation->always($callback);
      }
      $this->_stream->events()->once(
        'error',
        function ($message) use ($activation) {
          $this->deactivate();
          $activation->reject($message);
        }
      );
      $this->connect($activation);
      if (!($this->_heartBeat)) {
        $this->_heartBeat = $this->loop()->setInterval(
          function () use ($withHeartBeat, &$activation) {
            if (!$this->_heartBeat) {
              return;
            }
            ++$this->_heartBeatMissed;
            if ($activation->state() === Io\Deferred::STATE_PENDING) {
              $activation->notify(self::ACTIVATION_STARTED, ++$this->_activationTry);
              $this->connect($activation);
            } elseif ($withHeartBeat && $this->_heartBeatMissed > 3) {
              $this->stream()->close();
              $activation = new Io\Deferred();
              $activation
                ->done(
                  function() {
                    $this->_activationTry = 1;
                    $this->events()->emit(self::EVENT_REACTIVATE);
                  }
                );
              $this->connect($activation);
            }
          },
          $this->_heartBeatInterval
        );
      }
      return $activation->promise();
    }

    public function deactivate(): void {
      $this->_isActivated = FALSE;
      if ($this->_heartBeat) {
        $this->loop()->remove($this->_heartBeat);
        $this->_heartBeat = NULL;
      }
      if ($this->stream()->isOpen()) {
        $this->stream()->close();
      }
    }

    private function connect(Io\Deferred $defer): void {
      if (!$this->stream()->isOpen()) {
        $this->stream()->open();
      }
      $board = $this;
      $defer->notify(self::FETCHING_VERSION);
      $board->reportVersion(
        function () use ($defer) {
          $defer->notify(self::FETCHING_FIRMWARE);
          $this->queryFirmware(
            function () use ($defer) {
              $defer->notify(self::FETCHING_CAPABILITIES);
              $this->queryCapabilities(
                function () use ($defer) {
                  $defer->notify(self::FETCHING_ANALOG_MAPPING);
                  $this->queryAnalogMapping(
                    function () use ($defer) {
                      $this->_isActivated = TRUE;
                      $defer->notify(self::ACTIVATION_FINISHED, $this->_activationTry);
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

    /**
     * Provide some properties
     *
     * @param string $name
     * @return mixed
     * @throws \LogicException
     */
    public function __get($name) {
      switch ($name) {
      case 'version' :
        return $this->_version ?? new Version(0, 0);
      case 'firmware' :
        return $this->_firmware ?? new Version(0, 0);
      case 'pins' :
        return $this->pins();
      }
      throw new \LogicException(sprintf('Unknown property %s::$%s', __CLASS__, $name));
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name) {
      switch ($name) {
      case 'version' :
      case 'firmware' :
      case 'pins' :
        return TRUE;
      }
      return FALSE;
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
    private function handleData($byte): void {
      if (!$this->_bufferVersionReceived) {
        if ($byte !== self::REPORT_VERSION) {
          return;
        }
        $this->_bufferVersionReceived = TRUE;
      }
      $byteCount = count($this->_buffer);
      if ($byte === 0 && $byteCount === 0) {
        return;
      }
      $this->_buffer[] = $byte;
      ++$byteCount;
      if ($byteCount > 0) {
        $first = reset($this->_buffer);
        $last = end($this->_buffer);
        if (
          $first === self::START_SYSEX &&
          $last === self::END_SYSEX
        ) {
          if ($byteCount > 2) {
            $this->handleExtendedMessage(
              $this->_buffer[1], array_slice($this->_buffer, 2, -1)
            );
          }
          $this->_buffer = [];
        } elseif ($byteCount === 3 && $first !== self::START_SYSEX) {
          $command = ($first < 240) ? ($first & 0xF0) : $first;
          $this->handleMessage($command, $this->_buffer);
          $this->_buffer = [];
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
    private function handleMessage($command, $rawData): void {
      $this->_heartBeatMissed = 0;
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
        $this->events()->emit(self::EVENT_RESPONSE, new Response($command, $rawData));
        return;
      }
    }

    /**
     * @param Response\Midi\ReportVersion $response
     */
    private function handleVersionMessage(Response\Midi\ReportVersion $response): void {
      $this->_version = new Version($response->major, $response->minor);
      for ($i = 0; $i < 16; $i++) {
        $this->stream()->write([self::REPORT_DIGITAL | $i, 1]);
        $this->stream()->write([self::REPORT_ANALOG | $i, 1]);
      }
      $this->events()->emit(self::EVENT_REPORTVERSION);
    }

    /**
     * Got an analog message, change pin value and emit events
     *
     * @param Response\Midi\Message $response
     */
    private function handleAnalogMessage(Response\Midi\Message $response): void {
      if (0 <= ($pinNumber = $this->pins->getPinByChannel($response->port))) {
        $this->events()->emit(self::EVENT_ANALOG_READ.'-'.$pinNumber, $response->value);
        $this->events()->emit(self::EVENT_ANALOG_READ, ['pin' => $pinNumber, 'value' => $response->value]);
      }
    }

    /**
     * Got a digital message, change pin value and emit events
     *
     * @param Response\Midi\Message $response
     */
    private function handleDigitalMessage(Response\Midi\Message $response): void {
      $firstPin = 8 * $response->port;
      for ($i = 0; $i < 8; $i++) {
        $pinNumber = $firstPin + $i;
        if (isset($this->pins[$pinNumber])) {
          /** @var Pin $pin */
          $pin = $this->pins[$pinNumber];
          if ($pin->getMode() === Pin::MODE_INPUT) {
            $value = ($response->value >> ($i & 0x07)) & 0x01;
          } else {
            $value = $pin->getDigital();
          }
          $this->events()->emit(self::EVENT_DIGITAL_READ.'-'.$pinNumber, $value);
          $this->events()->emit(self::EVENT_DIGITAL_READ, ['pin' => $pinNumber, 'value' => $value]);
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
    private function handleExtendedMessage($command, $rawData): void {
      switch ($command) {
      case self::STRING_DATA :
        $this->events()->emit(self::EVENT_RECIEVE_STRING, Response::decodeBytes($rawData));
        return;
      case self::QUERY_FIRMWARE :
        $response = new Response\SysEx\QueryFirmware($rawData);
        $this->_firmware = new Version($response->major, $response->minor, $response->name);
        $this->events()->emit(self::EVENT_QUERYFIRMWARE);
        return;
      case self::CAPABILITY_RESPONSE :
        $response = new Response\SysEx\CapabilityResponse($rawData);
        $this->pins(new Pins($this, $response->pins));
        $this->events()->emit(self::EVENT_CAPABILITY_QUERY);
        return;
      case self::PIN_STATE_RESPONSE :
        $response = new Response\SysEx\PinStateResponse($rawData);
        $this->events()->emit(self::EVENT_PIN_STATE.'-'.$response->pin, $response->mode, $response->value);
        $this->events()->emit(self::EVENT_PIN_STATE, $response->pin, $response->mode, $response->value);
        return;
      case self::ANALOG_MAPPING_RESPONSE :
        $response = new Response\SysEx\AnalogMappingResponse($rawData);
        $this->pins->setAnalogMapping($response->channels);
        $this->events()->emit(self::EVENT_ANALOG_MAPPING_QUERY);
        return;
      default :
        $this->events()->emit(self::EVENT_RESPONSE, new Response($command, $rawData));
        return;
      }
    }

    /**
     * Reset board
     */
    public function reset(): void {
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
        function () {
          static $counter = 0;
          if (!$this->_bufferVersionReceived && ++$counter < 30) {
            $this->stream()->write([self::REPORT_VERSION]);
          }
        },
        1000
      );
      $this->events()->once(
        self::EVENT_REPORTVERSION,
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
      $this->events()->once(self::EVENT_QUERYFIRMWARE, $callback);
      $this->stream()->write([self::START_SYSEX, self::QUERY_FIRMWARE, self::END_SYSEX]);
    }

    /**
     * Query pin capabilities and execute callback after they are recieved
     *
     * @param callable $callback
     */
    public function queryCapabilities(Callable $callback): void {
      $this->events()->once(self::EVENT_CAPABILITY_QUERY, $callback);
      $this->stream()->write([self::START_SYSEX, self::CAPABILITY_QUERY, self::END_SYSEX]);
    }

    /**
     * Request the analog mapping data and execute callback after it is recieved
     *
     * @param callable $callback
     */
    public function queryAnalogMapping(Callable $callback): void {
      $this->events()->once(self::EVENT_ANALOG_MAPPING_QUERY, $callback);
      $this->stream()->write([self::START_SYSEX, self::ANALOG_MAPPING_QUERY, self::END_SYSEX]);
    }

    /**
     * Query pin status (mode and value), and execute callback after it recieved
     *
     * @param integer $pin 0-16
     * @param callable $callback
     */
    public function queryPinState($pin, Callable $callback): void {
      $this->events()->once('pin-state-'.$pin, $callback);
      $this->stream()->write([self::START_SYSEX, self::PIN_STATE_QUERY, $pin, self::END_SYSEX]);
    }

    /**
     * Query the status of each pin, this will update all pin objects
     */
    public function queryAllPinStates(): void {
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
    public function analogRead($pin, Callable $callback): void {
      $this->events()->on('analog-read-'.$pin, $callback);
    }

    /**
     * Add a callback for diagital read events on a pin
     *
     * @param integer $pin 0-16
     * @param callable $callback
     */
    public function digitalRead($pin, Callable $callback): void {
      $this->events()->on('digital-read-'.$pin, $callback);
    }


    /**
     * Write an analog value for a pin
     *
     * @param integer $pin
     * @param integer $value
     */
    public function analogWrite($pin, $value): void {
      $this->pins[$pin]->setValue($value);
      if ($pin > 15 || $value > 255) {
        $bytes = [self::START_SYSEX, self::EXTENDED_ANALOG, $pin];
        do {
          $bytes[] = $value & 0x7F;
          $value >>= 7;
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
      $this->stream()->write([self::PIN_MODE, $pin, $this->mapPinModeFirmataMode($mode)]);
    }


    /**
     * Add a callback function to be notified if the pin mode or value changes
     *
     * @param callable $callback
     */
    public function onChange(callable $callback) {
      $this->events()->on('change', $callback);
    }

    private function mapPinModeFirmataMode($pinMode) {
      $map = [
        Pin::MODE_INPUT => 0x00,
        Pin::MODE_OUTPUT => 0x01,
        Pin::MODE_ANALOG => 0x02,
        Pin::MODE_PWM => 0x03,
        Pin::MODE_SERVO => 0x04,
        Pin::MODE_SHIFT => 0x05,
        Pin::MODE_I2C => 0x06
      ];
      return (isset($map[$pinMode])) ? $map[$pinMode] : FALSE;
    }
  }
}
