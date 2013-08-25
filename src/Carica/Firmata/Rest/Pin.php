<?php

namespace Carica\Firmata\Rest {

  use Carica\Io\Network\Http;
  use Carica\Firmata;

  class Pin {

    private $_board;

    private $_modeStrings = array(
      Firmata\Board::PIN_STATE_INPUT => 'input',
      Firmata\Board::PIN_STATE_OUTPUT => 'output',
      Firmata\Board::PIN_STATE_ANALOG => 'analog',
      Firmata\Board::PIN_STATE_PWM => 'pwm',
      Firmata\Board::PIN_STATE_SERVO => 'servo'
    );

    public function __construct(Firmata\Board $board) {
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
        $pinId = (int)$parameters['pin'];
        if (isset($this->_board->pins[$pinId])) {
          $pin = $this->_board->pins[$pinId];
          if (isset($request->query['mode'])) {
            $this->setPinMode($pin, $request->query['mode']);
          }
          if (isset($request->query['digital'])) {
            $pin->digital = $request->query['digital'] == 'yes' ? TRUE : FALSE;
          }
          if (isset($request->query['analog'])) {
            $pin->analog = (int)$request->query['analog'];
          }
          $this->appendPin($boardNode, $pinId);
        }
      } else {
        $boardNode->setAttribute('active', 'no');
      }
      return $response;
    }

    private function setPinMode(Firmata\Pin $pin, $modeString) {
      if ($mode = array_search($modeString, $this->_modeStrings)) {
        try {
          $pin->mode= $mode;
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
        foreach ($pin->supports as $mode) {
          $modes[] = $this->getModeString($mode);
        }
        $pinNode->setAttribute('supports', implode(' ', $modes));
        $pinNode->setAttribute('mode', $this->getModeString($pin->mode));
        switch ($pin->mode) {
        case self::PIN_STATE_INPUT :
        case self::PIN_STATE_OUTPUT :
          $pinNode->setAttribute('digital', $pin->digital ? 'yes' : 'no');
          break;
        case self::PIN_STATE_ANALOG :
        case self::PIN_STATE_PWM :
        case self::PIN_STATE_SERVO :
          $pinNode->setAttribute('analog', $pin->analog);
          break;
        }
      }
    }

    private function getModeString($mode) {
      return isset($this->_modeStrings[$mode]) ? $this->_modeStrings[$mode] : 'unknown';
    }
  }
}