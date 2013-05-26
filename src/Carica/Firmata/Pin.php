<?php

namespace Carica\Firmata {

  /**
   * Represents a single pin on the board.
   *
   * @property-read Carica\Firmata\Board $board
   * @property-read integer $pin
   * @property-read array $supports
   * @property-read integer $value
   * @property integer $analog Get/set the pin value using an analog integer value
   * @property boolean $digital Get/set the pin value using an boolean value
   */
  class Pin {

    /**
     * @var Carica\Firmata\Board
     */
    private $_board = NULL;
    /**
     * @var integer
     */
    private $_pin = 0;
    /**
     * @var array(integer)
     */
    private $_supports = array();
    /**
     * @var integer
     */
    private $_mode = PIN_STATE_UNKNOWN;
    /**
     * @var integer
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
     * @param integer $pin
     * @param array $supports
     */
    public function __construct(Board $board, $pin, array $supports) {
      $this->_board = $board;
      $this->_pin = (int)$pin;
      $this->_supports = $supports;
      $this->attachEvents();
    }

    private function attachEvents() {
      $that = $this;
      if ($events = $this->board->events()) {
        $events->on(
          'pin-state-'.$this->_pin,
          function ($mode, $value) use ($that) {
            $that->onUpdatePinState($mode, $value);
          }
        );
        $events->on(
          'analog-read-'.$this->_pin,
          function ($value) use ($that) {
            $that->onUpdateValue($value);
          }
        );
        $events->on(
          'digital-read-'.$this->_pin,
          function ($value) use ($that) {
            $that->onUpdateValue($value);
          }
        );
      }
    }

    /**
     * Callback function for pin state updates from the board.
     *
     * @param integer $mode
     * @param integer $value
     */
    private function onUpdatePinState($mode, $value) {
      $this->_mode = $mode;
      $this->_value = $value;
    }

    /**
     * Callback function for pin value changes sent from the board.
     *
     * @param integer $value
     */
    private function onUpdateValue($value) {
      $this->_value = $value;
    }

    /**
     * Define usable properties
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name) {
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
     * @throws \LogicException
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
      case 'mode' :
        return $this->_mode;
      case 'value' :
        return $this->_value;
      case 'digital' :
        return ($this->_value == DIGITAL_HIGH);
      case 'analog' :
        return $this->_value;
      }
      throw new \LogicException(sprintf('Unknown property %s::$%s', get_class($this), $name));
    }

    /**
     * Setter for the writeable properties
     *
     * @param string $name
     * @param mixed $value
     * @throws \LogicException
     */
    public function __set($name, $value) {
      switch ($name) {
      case 'mode' :
        $this->setMode($value);
        return;
      case 'digital' :
        $this->setDigital($value);
        return;
      case 'analog' :
        $this->setAnalog($value);
        return;
      }
      throw new \LogicException(
        sprintf('Property %s::$%s can not be written', get_class($this), $name)
      );
    }

    /**
     * Setter method for the mode property.
     *
     * @param integer $mode
     * @throws \OutOfBoundsException
     */
    public function setMode($mode) {
      $mode = (int)$mode;
      if (!in_array($mode, $this->_supports)) {
        throw new Exception\UnsupportedMode($this->_pin, $mode);
      }
      if ($this->_modeInitialized && $this->_mode == $mode) {
        return;
      }
      $this->_mode = $mode;
      $this->_modeInitialized = TRUE;
      $this->_board->pinMode($this->_pin, $mode);
    }

    /**
     * Setter method for the digital property. Allows to change the value between low and high
     * using boolean values
     *
     * @param boolean $isActive
     */
    public function setDigital($isActive) {
      $value = (boolean)$isActive ? DIGITAL_HIGH : DIGITAL_LOW;
      if ($this->_valueInitialized && $this->_value == $value) {
        return;
      }
      $this->_value = $value;
      $this->_valueInitialized = TRUE;
      $this->_board->digitalWrite($this->_pin, $value);
    }

    /**
     * Setter method for the analog property. Allows to set change the value on the pin.
     * @param unknown $value
     */
    public function setAnalog($value) {
      $value = (int)$value;
      if ($this->_valueInitialized && $this->_value == $value) {
        return;
      }
      $this->_value = $value;
      $this->_valueInitialized = TRUE;
      $this->_board->analogWrite($this->_pin, $value);
    }

    /**
     * Does the pin support the given mode
     *
     * @param integer $mode
     * @return boolean
     */
    public function supports($mode) {
      return in_array($mode, $this->_supports);
    }
  }
}
