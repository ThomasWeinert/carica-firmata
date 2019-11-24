<?php

namespace Carica\Firmata {

  use Carica\Io;
  use LogicException;

  /**
   * Represents a single pin on the board.
   *
   * @property-read Board $board
   * @property-read int $pin pin index
   * @property-read array $supports array of pin modes and maimum values
   * @property-read int $maximum Maximum value of the current mode
   * @property int $mode Get/set the pin mode
   * @property int $value Get/set the pin value using an analog int value
   * @property float $analog Get/set the pin value using a float between 0 and 1
   * @property bool $digital Get/set the pin value using an boolean value
   */
  class Pin
    implements
      Io\Event\HasEmitter,
      Io\Device\Pin {

    use Io\Event\Emitter\Aggregation;

    public const EVENT_CHANGE = 'change';
    public const EVENT_CHANGE_VALUE = 'change-value';
    public const EVENT_CHANGE_MODE = 'change-mode';

    /**
     * @var Board
     */
    private $_board;
    /**
     * @var int
     */
    private $_pin;
    /**
     * Array of supported modes and resolutions
     *
     * @var array(int => int)
     */
    private $_supports;
    /**
     * @var int
     */
    private $_mode;
    /**
     * @var int
     */
    private $_value = 0;

    /**
     * Was the mode sent at least once to sync it with the board.
     * @var boolean
     */
    private $_modeInitialized = FALSE;
    /**
     * Was the value sent at least once to sync it with the board.
     * @var boolean
     */
    private $_valueInitialized = FALSE;

    /**
     * Create a pin object for the specified board and pin id. Provide informations
     * about the supported modes.
     *
     * @param Board $board
     * @param int $pin
     * @param array $supports Array
     */
    public function __construct(Board $board, int $pin, array $supports) {
      $this->_board = $board;
      $this->_pin = (int)$pin;
      $this->_supports = $supports;
      $modes = array_keys($supports);
      $this->_mode = $modes[0] ?? self::MODE_UNKNOWN;
      $this->attachEvents();
    }

    private function attachEvents(): void {
      $that = $this;
      if ($events = $this->board->events()) {
        $events->on(
          Board::EVENT_PIN_STATE.'-'.$this->_pin,
          function ($mode, $value) {
            $this->onUpdatePinState($mode, $value);
          }
        );
        $events->on(
          Board::EVENT_ANALOG_READ.'-'.$this->_pin,
          static function ($value) use ($that) {
            $that->onUpdateValue($value);
          }
        );
        $events->on(
          Board::EVENT_DIGITAL_READ.'-'.$this->_pin,
          static function ($value) use ($that) {
            $that->onUpdateValue($value);
          }
        );
      }
    }

    /**
     * Callback function for pin state updates from the board.
     *
     * @param int $mode
     * @param int $value
     */
    private function onUpdatePinState(int $mode, int $value): void {
      $this->_modeInitialized = TRUE;
      $this->_valueInitialized = TRUE;
      if ($this->_mode !== $mode || $this->_value !== $value) {
        if ($this->_mode !== $mode) {
          $this->_mode = $mode;
          $this->emitEvent(self::EVENT_CHANGE_MODE, $this);
        }
        if ($this->_value !== $value) {
          $this->_value = $value;
          $this->emitEvent(self::EVENT_CHANGE_VALUE, $this);
        }
        $this->emitEvent(self::EVENT_CHANGE, $this);
      }
    }

    /**
     * Callback function for pin value changes sent from the board.
     *
     * @param int $value
     */
    private function onUpdateValue(int $value) {
      $this->_valueInitialized = TRUE;
      if ($this->_value !== $value) {
        $this->_value = $value;
        $this->emitEvent(self::EVENT_CHANGE_VALUE, $this);
        $this->emitEvent(self::EVENT_CHANGE, $this);
      }
    }

    /**
     * Define usable properties
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name): bool {
      switch ($name) {
      case 'board' :
      case 'pin' :
      case 'supports' :
      case 'mode' :
      case 'value' :
        return isset($this->{'_'.$name});
      case 'digital' :
      case 'analog' :
        return isset($this->_value);
      }
      return FALSE;
    }

    /**
     * Getter mapping for the object properties
     *
     * @param string $name
     * @throws LogicException
     * @return mixed
     */
    public function __get($name) {
      switch ($name) {
      case 'board' :
        return $this->_board;
      case 'pin' :
        return $this->_pin;
      case 'supports' :
        return $this->_supports;
      case 'value' :
        return $this->_value;
      case 'mode' :
        return $this->getMode();
      case 'maximum' :
        return $this->getMaximum();
      case 'digital' :
        return $this->getDigital();
      case 'analog' :
        return $this->getAnalog();
      }
      throw new LogicException(sprintf('Unknown property %s::$%s', get_class($this), $name));
    }

    /**
     * Setter for the writeable properties
     *
     * @param string $name
     * @param mixed $value
     * @throws LogicException
     * @throws Exception\UnsupportedMode
     */
    public function __set($name, $value) {
      switch ($name) {
      case 'mode' :
        $this->setMode($value);
        return;
      case 'value' :
        $this->setValue($value);
        return;
      case 'digital' :
        $this->setDigital($value);
        return;
      case 'analog' :
        $this->setAnalog($value);
        return;
      }
      throw new LogicException(
        sprintf('Property %s::$%s can not be written', get_class($this), $name)
      );
    }

    public function getMode(): int {
      return $this->_mode;
    }

    /**
     * Setter method for the mode property.
     *
     * @param int $mode
     *
     * @throws Exception\UnsupportedMode
     */
    public function setMode(int $mode): void {
      if (!array_key_exists($mode, $this->_supports)) {
        throw new Exception\UnsupportedMode($this->_pin, $mode);
      }
      if ($this->_modeInitialized && $this->_mode === $mode) {
        return;
      }
      $this->_mode = $mode;
      $this->_modeInitialized = TRUE;
      $this->_board->pinMode($this->_pin, $mode);
      $this->emitEvent(self::EVENT_CHANGE_MODE, $this);
      $this->emitEvent(self::EVENT_CHANGE, $this);
    }

    /**
     * Return the current state (low/high) of the pin as boolean
     * @return bool
     */
    public function getDigital(): bool {
      return ($this->_value === Board::DIGITAL_HIGH);
    }

    /**
     * Setter method for the digital property. Allows to change the value between low and high
     * using boolean values
     *
     * @param bool $isActive
     */
    public function setDigital(bool $isActive): void {
      $value = $isActive ? Board::DIGITAL_HIGH : Board::DIGITAL_LOW;
      if ($this->_valueInitialized && $this->_value === $value) {
        return;
      }
      $this->_value = $value;
      $this->_valueInitialized = TRUE;
      $this->_board->digitalWrite($this->_pin, $value);
      $this->emitEvent(self::EVENT_CHANGE_VALUE, $this);
      $this->emitEvent(self::EVENT_CHANGE, $this);
    }

    /**
     * Getter method for the anlog value
     * @return float between 0 and 1
     */
    public function getAnalog(): float {
      return ($this->maximum > 0) ? $this->_value / $this->maximum : 0;
    }

    /**
     * Setter method for the analog property. Allows to set change the value on the pin.
     *
     * @param float $percent between 0 and 1
     */
    public function setAnalog(float $percent): void {
      $resolution = $this->maximum;
      $value = round($percent * $resolution);
      if ($value < 0) {
        $value = 0;
      } elseif ($value > $resolution) {
        $value = $resolution;
      }
      $this->setValue($value);
    }

    /**
     * @param int $value
     */
    public function setValue(int $value) {
      if ($this->_valueInitialized && $this->_value === $value) {
        return;
      }
      $this->_value = $value;
      $this->_valueInitialized = TRUE;
      $this->_board->analogWrite($this->_pin, $value);
      $this->emitEvent(self::EVENT_CHANGE_VALUE, $this);
      $this->emitEvent(self::EVENT_CHANGE, $this);
    }

    /**
     * Return the maximum value of the current mode
     *
     * @return int
     */
    public function getMaximum(): int {
      return $this->_supports[$this->_mode];
    }

    /**
     * Does the pin support the given mode
     *
     * @param int $mode
     * @return boolean
     */
    public function supports(int $mode): bool {
      return array_key_exists($mode, $this->_supports);
    }

    /**
     * @param callable $callback
     */
    public function onChange(callable $callback): void {
      $this->events()->on(self::EVENT_CHANGE, $callback);
    }
  }
}
