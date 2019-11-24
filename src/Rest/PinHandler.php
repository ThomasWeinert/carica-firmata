<?php

namespace Carica\Firmata\Rest {

  use Carica\Firmata;
  use Carica\Firmata\Board as FirmataBoard;
  use Carica\Firmata\Exception\UnsupportedMode;
  use Carica\Io\Network\HTTP\Request as HTTPRequest;
  use Carica\Io\Network\HTTP\Response as HTTPResponse;
  use DOMElement;

  class PinHandler {

    /**
     * @var FirmataBoard
     */
    private $_board;

    /**
     * @var array
     */
    private $_modeStrings = [
      Firmata\Pin::MODE_INPUT => 'input',
      Firmata\Pin::MODE_OUTPUT => 'output',
      Firmata\Pin::MODE_ANALOG => 'analog',
      Firmata\Pin::MODE_PWM => 'pwm',
      Firmata\Pin::MODE_SERVO => 'servo',
      Firmata\Pin::MODE_SHIFT => 'shift',
      Firmata\Pin::MODE_I2C => 'i2c'
    ];

    /**
     * @var array
     */
    private $_modeMap;

    /**
     * @param FirmataBoard $board
     */
    public function __construct(FirmataBoard $board) {
      $this->_modeMap = array_flip($this->_modeStrings);
      $this->_board = $board;
    }

    /**
     * @param HTTPRequest $request
     * @param array $parameters
     * @return HTTPResponse
     */
    public function __invoke(HTTPRequest $request, array $parameters): HTTPResponse {
      $response = $request->createResponse();
      $response->content = new HTTPResponse\Content\XML();
      $dom = $response->content->document;
      $dom->appendChild($boardNode = $dom->createElement('board'));
      if ($this->_board->isActive()) {
        $boardNode->setAttribute('active', 'yes');
        $boardNode->setAttribute('firmata', (string)$this->_board->version);
        $pinId = isset($parameters['pin']) ? (int)$parameters['pin'] : -1;
        $pins = $this->_board->pins;
        if (isset($pins[$pinId])) {
          $pin = $pins[$pinId];
          $query = $request->query;
          if (isset($query['mode'])) {
            $this->setPinMode($pin, $query['mode']);
          }
          if (isset($query['digital'])) {
            $pin->digital = $query['digital'] === 'yes';
          } elseif (isset($query['analog'])) {
            $pin->analog = (float)$query['analog'];
          } elseif (isset($query['value'])) {
            $pin->value = (int)$query['value'];
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
    private function setPinMode(Firmata\Pin $pin, string $modeString): void {
      if (isset($this->_modeMap[$modeString])) {
        try {
          $pin->setMode($this->_modeMap[$modeString]);
        } catch (UnsupportedMode $e) {
        }
      }
    }

    /**
     * @param DOMElement $parent
     * @param int $pinId
     */
    public function appendPin(DOMElement $parent, int $pinId): void {
      $pins = $this->_board->pins;
      if (isset($pins[$pinId])) {
        $dom = $parent->ownerDocument;
        $pin = $pins[$pinId];
        $parent->appendChild($pinNode = $dom->createElement('pin'));
        $pinNode->setAttribute('number', $pin->pin);
        $modes = [];
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
    private function getModeString(string $mode): string {
      return $this->_modeStrings[$mode] ?? 'unknown';
    }
  }
}
