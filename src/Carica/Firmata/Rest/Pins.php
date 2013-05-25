<?php

namespace Carica\Firmata\Rest {

  use Carica\Io\Network\Http;
  use Carica\Firmata;

  class Pins {

    private $_board = NULL;
    private $_pinHandler = NULL;

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
        foreach ($this->_board->pins as $pin) {
          $this->pinHandler()->appendPin($boardNode, $pin->pin);
        }
      } else {
        $boardNode->setAttribute('active', 'no');
      }
      return $response;
    }

    public function pinHandler(Pin $handler = NULL) {
      if (isset($handler)) {
        $this->_pinHandler = $handler;
      } elseif (NULL === $this->_pinHandler) {
        $this->_pinHandler = new Pin($this->_board);
      }
      return $this->_pinHandler;
    }
  }
}