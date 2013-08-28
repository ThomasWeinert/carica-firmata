<?php

namespace Carica\Firmata\Rest {

  use Carica\Io\Network\Http;
  use Carica\Firmata;

  class Pin {

    private $_board;

    private $_modeStrings = array(
      Firmata\Board::PIN_MODE_INPUT => 'input',
      Firmata\Board::PIN_MODE_OUTPUT => 'output',
      Firmata\Board::PIN_MODE_ANALOG => 'analog',
      Firmata\Board::PIN_MODE_PWM => 'pwm',
      Firmata\Board::PIN_MODE_SERVO => 'servo',
      Firmata\Board::PIN_MODE_SHIFT => 'shift',
      Firmata\Board::PIN_MODE_I2C => 'i2c'
    );

    private $_modeMap = NULL;

    public function __construct(Firmata\Board $board) {
      $this->_modeMap = array_flip($this->_modeStrings);
      $this->_board = $board;
    }

    public function __invoke() {
      return call_user_func_array(array($this, 'handle'), func_get_args());
    }

    public function handle(Http\Request $request, array $parameters) {
      $response = $request->createResponse();
      $response->content = new Http\Response\Content\Xml;
      $dom = $response->content->document;
      $dom->appendChild($boardNode = $dom->createElement('board'));
      if ($this->_board->isActive()) {
        $boardNode->setAttribute('active', 'yes');
        $boardNode->setAttribute('firmata', (string)$this->_board->version);
        $pinId = isset($parameters['pin']) ? (int)$parameters['pin'] : -1;
        if (isset($this->_board->pins[$pinId])) {
          $pin = $this->_board->pins[$pinId];
          if (isset($request->query['mode'])) {
            $this->setPinMode($pin, $request->query['mode']);
          }
          if (isset($request->query['digital'])) {
            $pin->digital = $request->query['digital'] == 'yes' ? TRUE : FALSE;
          } elseif (isset($request->query['analog'])) {
            $pin->analog = (int)$request->query['analog'];
          } elseif (isset($request->query['value'])) {
            $pin->value = (int)$request->query['value'];
          }
          $this->appendPin($boardNode, $pinId);
        }
      } else {
        $boardNode->setAttribute('active', 'no');
      }
      return $response;
    }

    private function setPinMode(Firmata\Pin $pin, $modeString) {
      if (isset($this->_modeMap[$modeString])) {
        try {
          $pin->mode = $this->_modeMap[$modeString];
        } catch (Firmata\Exception\UnsupportedMode $e) {
        }
      }
    }

    public function appendPin(\DOMElement $parent, $pinId) {
      if (isset($this->_board->pins[$pinId])) {
        $dom = $parent->ownerDocument;
        $pin = $this->_board->pins[$pinId];
        $parent->appendChild($pinNode = $dom->createElement('pin'));
        $pinNode->setAttribute('number', $pin->pin);
        $modes = array();
        foreach ($pin->supports as $mode => $resolution) {
          $modes[] = $this->getModeString($mode);
        }
        $pinNode->setAttribute('supports', implode(' ', $modes));
        $pinNode->setAttribute('mode', $this->getModeString($pin->mode));
        switch ($pin->mode) {
        case Firmata\Board::PIN_MODE_INPUT :
        case Firmata\Board::PIN_MODE_OUTPUT :
          $pinNode->setAttribute('digital', $pin->digital ? 'yes' : 'no');
          break;
        case Firmata\Board::PIN_MODE_ANALOG :
        case Firmata\Board::PIN_MODE_PWM :
        case Firmata\Board::PIN_MODE_SERVO :
          $pinNode->setAttribute('analog', round($pin->analog, 4));
          break;
        }
        $pinNode->setAttribute('value', $pin->value);
      }
    }

    private function getModeString($mode) {
      return isset($this->_modeStrings[$mode]) ? $this->_modeStrings[$mode] : 'unknown';
    }
  }
}