<?php

namespace Carica\Firmata {

  use Carica\Io;
  use Carica\Io\Event;

  /**
   * This class represents an Arduino board running firmata.
   *
   * @property-read array $version
   * @property-read array $firmware
   * @property-read array $pins
   */
  class Board {

    use Event\Emitter\Aggregation;

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
    const ANALOG_MAPPING_QUERY = 0x69;
    const ANALOG_MAPPING_RESPONSE = 0x6A;
    const I2C_REQUEST = 0x76;
    const I2C_REPLY = 0x77;
    const I2C_CONFIG = 0x78;
    const STRING_DATA = 0x71;
    const PULSE_IN = 0x74;
    const SYSTEM_RESET = 0xFF;

    const PIN_STATE_UNKNOWN = 0xFF; // internal state to recognize unininitialized pins
    const PIN_STATE_INPUT = 0x00;
    const PIN_STATE_OUTPUT = 0x01;
    const PIN_STATE_ANALOG = 0x02;
    const PIN_STATE_PWM = 0x03;
    const PIN_STATE_SERVO = 0x04;

    const DIGITAL_LOW = 0;
    const DIGITAL_HIGH = 1;

    const I2C_MODE_WRITE = 0;
    const I2C_MODE_READ = 1;
    const I2C_MODE_CONTINOUS_READ = 2;
    const I2C_MODE_STOP_READING = 3;

    /**
     * @var array
     */
    private $_pins = NULL;

    /**
     * @var array
     */
    private $_channels = array();

    /**
     * @var Carica\Io\Stream
     */
    private $_stream = NULL;
    /**
     * @var Buffer
     */
    private $_buffer = NULL;

    /**
     * Firmata version information
     * @var Carica\Firmata\Version
     */
    private $_version = NULL;

    /**
     * Firmware version information
     * @var Carica\Firmata\Version
     */
    private $_firmware= NULL;

    /**
     * Map command responses to private event handlers
     * @var array(integer=>string)
     */
    private $_responseHandler = array(
      self::REPORT_VERSION => 'onReportVersion',
      self::ANALOG_MESSAGE => 'onAnalogMessage',
      self::DIGITAL_MESSAGE => 'onDigitalMessage',
      self::STRING_DATA => 'onStringData',
      self::PULSE_IN => 'onPulseIn',
      self::QUERY_FIRMWARE => 'onQueryFirmware',
      self::CAPABILITY_RESPONSE => 'onCapabilityResponse',
      self::PIN_STATE_RESPONSE => 'onPinStateResponse',
      self::ANALOG_MAPPING_RESPONSE => 'onAnalogMappingResponse',
      self::I2C_REPLY => 'onI2CReply'
    );

    /**
     * Create board and assign stream object
     *
     * @param Carica\Io\Stream $stream
     */
    public function __construct(Io\Stream $stream) {
      $this->_stream = $stream;
      $this->_pins = new \ArrayObject();
    }

    /**
     * Validate if the board ist still active (the port/stream contains a valid resource)
     *
     * @return boolean
     */
    public function isActive() {
      return is_resource($this->stream()->resource());
    }

    /**
     * Getter for the port/stream object
     *
     * @return Carica\Io\Stream
     */
    public function stream() {
      return $this->_stream;
    }

    /**
     * Buffer for recieved data
     *
     * @param Buffer $buffer
     */
    public function buffer(Buffer $buffer = NULL) {
      if (isset($buffer)) {
        $this->_buffer = $buffer;
      } elseif (NULL === $this->_buffer) {
        $this->_buffer = new Buffer();
      }
      return $this->_buffer;
    }

    /**
     * Activate the board, assign the needed callbacks
     *
     * @param Callable|NULL $callback
     * @return Carica\Io\Deferred\Promise
     */
    public function activate(Callable $callback = NULL) {
      $defer = new \Carica\Io\Deferred();
      if (isset($callback)) {
        $defer->always($callback);
      }
      $this->stream()->events()->on(
        'error',
        function($message) use ($defer) {
          $defer->reject($message);
        }
      );
      $this->stream()->events()->on('read-data', array($this->buffer(), 'addData'));
      $this->buffer()->events()->on('response', array($this, 'onResponse'));
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
     * Provide some read only properties
     *
     * @param string $name
     * @throws LogicException
     * @return mixed
     */
    public function __get($name) {
      switch ($name) {
      case 'version' :
        return isset($this->_version) ? $this->_version : new Version(0,0);
      case 'firmware' :
        return isset($this->_firmware) ? $this->_firmware : new Version(0,0);
      case 'pins' :
        return $this->_pins;
      }
      throw new \LogicException(sprintf('Unknown property %s::$%s', __CLASS__, $name));
    }

    /**
     * Callback for the buffer, received a response from the board. Call a more specific
     * private event handler based on the $_responseHandler mapping array
     *
     * @param Carica\Firmata\Response $response
     */
    public function onResponse(Response $response) {
      if (isset($this->_responseHandler[$response->getCommand()])) {
        $callback = array($this, $this->_responseHandler[$response->getCommand()]);
        return $callback($response);
      }
    }

    /**
     * A version was reported, store it and request value reading
     *
     * @param Carica\Firmata\Response\Midi\ReportVersion $response
     */
    private function onReportVersion(Response\Midi\ReportVersion $response) {
      $this->_version = new Version($response->major, $response->minor);
      for ($i = 0; $i < 16; $i++) {
        $this->stream()->write([self::REPORT_DIGITAL | $i, 1]);
        $this->stream()->write([self::REPORT_ANALOG | $i, 1]);
      }
      $this->events()->emit('reportversion');
    }

    /**
     * Firmware was reported, store it and emit event
     *
     * @param Response\Sysex\QueryFirmware $response
     */
    private function onQueryFirmware(Response\Sysex\QueryFirmware $response) {
      $this->_firmware = new Version($response->major, $response->minor, $response->name);
      $this->events()->emit('queryfirmware');
    }


    /**
     * Capabilities for all pins were reported, store pin status and emit event
     *
     * @param Response\Sysex\CapabilityResponse $response
     */
    private function onCapabilityResponse(Response\Sysex\CapabilityResponse $response) {
      $this->_pins = new Pins($this, $response->pins);
      $this->events()->emit('capability-query');
    }

    /**
     * Analog mapping data was reported, store it and report event
     *
     * @param Response\Sysex\AnalogMappingResponse $response
     */
    private function onAnalogMappingResponse(Response\Sysex\AnalogMappingResponse $response) {
      $this->_channels = $response->channels;
      $this->events()->emit('analog-mapping-query');
    }


    /**
     * Got an analog message, change pin value and emit events
     *
     * @param Response\Midi\AnalogMessage $response
     */
    private function onAnalogMessage(Response\Midi\Message $response) {
      if (isset($this->_channels[$response->port]) &&
          isset($this->_pins[$this->_channels[$response->port]])) {
        $pin = $this->_channels[$response->port];
        $this->events()->emit('analog-read-'.$pin, $response->value);
        $this->events()->emit('analog-read', ['pin' => $pin, 'value' => $response->value]);
      }
    }

    /**
     * Got a digital message, change pin value and emit events
     *
     * @param Response\Midi\DigitalMessage $response
     */
    private function onDigitalMessage(Response\Midi\Message $response) {
      for ($i = 0; $i < 8; $i++) {
        if (isset($this->_pins[8 * $response->port + $i])) {
          $pinNumber = 8 * $response->port + $i;
          $pin = $this->_pins[$pinNumber];
          if ($pin->mode == PIN_STATE_INPUT) {
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
     * Firmata send some string data (error message most likely) emit an
     * event for it.
     *
     * @param Response\SysEx\String $response
     */
    private function onStringData(Response\SysEx\String $response) {
      $this->events()->emit('string', $response->text);
    }

    /**
     * Data returned from an i2c device emit the eent for it.
     *
     * @param Response\SysEx\I2CReply $response
     */
    private function onI2CReply(Response\SysEx\I2CReply $response) {
      $this->events()->emit('I2C-reply-'.$response->slaveAddress, $response->data);
    }

    /**
     * An (sonar) pulse was sent and recived, emit an event with
     * the duration (in microseconds).
     *
     * @param Response\SysEx\PulseIn $response
     */
    private function onPulseIn(Response\SysEx\PulseIn $response) {
      $this->events()->emit('pulse-in-'.$response->pin, $response->duration);
      $this->events()->emit('pulse-in', $response->pin, $response->duration);
    }

    /**
     * Pin status was reported, store it and emit event
     *
     * @param Response\Sysex\PinStateResponse $response
     */
    private function onPinStateResponse(Response\Sysex\PinStateResponse $response) {
      $this->events()->emit('pin-state-'.$response->pin, $response->mode, $response->value);
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
     * @param callable $callback
     */
    public function reportVersion(Callable $callback) {
      $this->events()->once('reportversion', $callback);
      $this->stream()->write([self::REPORT_VERSION]);
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
     */
    public function analogRead($pin, $callback) {
      $this->events()->on('analog-read-'.$pin, $callback);
    }

    /**
     * Add a callback for diagital read events on a pin
     * @param integer $pin 0-16
     */
    public function digitalRead($pin, Callable $callback) {
      $this->events()->on('digital-read-'.$pin, $callback);
    }


    /**
     * Write an analog value for a pin
     *
     * @param integer $pin 0-16
     * @param integer $value 0-255
     */
    public function analogWrite($pin, $value) {
      $this->_pins[$pin]->setValue($value);
      $this->stream()->write(
        [self::ANALOG_MESSAGE | $pin, $value & 0x7F, ($value >> 7) & 0x7F]
      );
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
      $this->_pins[$pin]->setDigital($value == self::DIGITAL_HIGH);
      $port = floor($pin / 8);
      $portValue = 0;
      for ($i = 0; $i < 8; $i++) {
        $index = 8 * $port + $i;
        if (isset($this->_pins[$index]) && $this->_pins[$index]->digital) {
          $portValue |= (1 << $i);
        }
      }
      $this->stream()->write(
        [self::DIGITAL_MESSAGE | $port, $portValue & 0x7F, ($portValue >> 7) & 0x7F]
      );
    }

    /**
     * Set the mode of a pin:
     *   Carica\Firmata::PIN_STATE_INPUT,
     *   Carica\Firmata::PIN_STATE_OUTPUT,
     *   Carica\FirmataPIN_STATE_ANALOG,
     *   Carica\FirmataPIN_STATE_PWM,
     *   Carica\FirmataPIN_STATE_SERVO
     *
     * @param integer $pin 0-16
     * @param integer $mode
     */
    public function pinMode($pin, $mode) {
      $this->pins[$pin]->setMode($mode);
      $this->stream()->write([self::PIN_MODE, $pin, $mode]);
    }

    /**
     * Configure the i2c coomunication
     *
     * @param integer $delay
     */
    public function sendI2CConfig($delay = 0) {
      $this
        ->stream()
        ->write(
           array(
             self::START_SYSEX,
             self::I2C_CONFIG,
             delay >> 0xFF,
             (delay >> 8) & 0XFF,
             self::END_SYSEX
           )
        );
    }

    /**
     * Write some data to an i2c device
     *
     * @param integer $slaveAddress
     * @param string $data
     */
    public function sendI2CWriteRequest($slaveAddress, $data) {
      $request = new Request\I2C\Write($this, $slaveAddress, $data);
      $request->send();
    }

    /**
     * Request data from an i2c device and trigger callback if the
     * data is sent.
     *
     * @param integer $slaveAddress
     * @param integer $byteCount
     * @param callable $callback
     */
    public function sendI2CReadRequest($slaveAddress, $byteCount, Callable $callback) {
      $request = new Request\I2C\Read($this, $slaveAddress, $data);
      $request->send();
      $this->events()->once('I2C-reply-'.$slaveAddress, $callback);
    }

    /**
     * Send a pulse and execute the callback attach the callback so it will be executed
     * with the duration as an argument.
     *
     * @param integer $pin
     * @param Callable $callback
     * @param integer $value
     * @param integer $pulseLength
     * @param integer $timeout
     */
    public function pulseIn($pin, $callback, $value = self::DIGITAL_HIGH, $pulseLength = 5, $timeout = 1000000) {
      $this->events()->once('pulse-in-'.$pin, $callback);
      $request = new Request\PulseIn($this, $pin, $value, $pulseLength, $timeout);
      $request->send();
    }
  }
}
