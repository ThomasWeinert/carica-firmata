<?php

namespace Carica\Firmata\Rest {

  use Carica\Io\Network\Http;
  use Carica\Firmata;

  class Pin {

    /**
     * @var Firmata\Board
     */
    private $_board;

    /**
     * @var array
     */
    private $_modeStrings = array(
      Firmata\Pin::MODE_INPUT => 'input',
      Firmata\Pin::MODE_OUTPUT => 'output',
      Firmata\Pin::MODE_ANALOG => 'analog',
      Firmata\Pin::MODE_PWM => 'pwm',
      Firmata\Pin::MODE_SERVO => 'servo',
      Firmata\Pin::MODE_SHIFT => 'shift',
      Firmata\Pin::MODE_I2C => 'i2c'
    );

    /**
     * @var array
     */
    private $_modeMap = NULL;

    /**
     * @param Firmata\Board $board
     */
    public function __construct(Firmata\Board $board) {
      $this->_modeMap = array_flip($this->_modeStrings);
      $this->_board = $board;
    }

    /**
     * @return Http\Response
     */
    public function __invoke(...$arguments) {
      return $this->handle(...$arguments);
    }

    /**
     * @param Http\Request $request
     * @param array $parameters
     * @return Http\Response
     */
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
            $pin->analog = (float)$request->query['analog'];
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

    /**
     * @param Firmata\Pin $pin
     * @param string $modeString
     */
    private function setPinMode(Firmata\Pin $pin, $modeString) {
      if (isset($this->_modeMap[$modeString])) {
        try {
          $pin->mode = $this->_modeMap[$modeString];
        } catch (Firmata\Exception\UnsupportedMode $e) {
        }
      }
    }

    /**
     * @param \DOMElement $parent
     * @param int $pinId
     */
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
        case Firmata\Pin::MODE_INPUT :
        case Firmata\Pin::MODE_OUTPUT :
          $pinNode->setAttribute('digital', $pin->digital ? 'yes' : 'no');
          break;
        case Firmata\Pin::MODE_ANALOG :
        case Firmata\Pin::MODE_PWM :
        case Firmata\Pin::MODE_SERVO :
          $pinNode->setAttribute('analog', round($pin->analog, 4));
          break;
        }
        $pinNode->setAttribute('value', $pin->value);
      }
    }

    /**
     * @param string $mode
     * @return string
     */
    private function getModeString($mode) {
      return isset($this->_modeStrings[$mode]) ? $this->_modeStrings[$mode] : 'unknown';
    }
  }
}
