<?php

namespace Carica\Firmata\Rest {

  use Carica\Io\Network\Http;
  use Carica\Firmata;

  class Pins {

    /**
     * @var Firmata\Board
     */
    private $_board = NULL;

    /**
     * @var Firmata\Pin
     */
    private $_pinHandler = NULL;

    /**
     * @param Firmata\Board $board
     */
    public function __construct(Firmata\Board $board) {
      $this->_board = $board;
    }

    /**
     * Make the class Callable
     *
     * @param array $arguments
     * @return Http\Response
     */
    public function __invoke(...$arguments) {
      return $this->handle(...$arguments);
    }

    /**
     * Create a XML response for the all pins on the board.
     *
     * @param Http\Request $request
     *
     * @internal param array $parameters
     * @return Http\Response
     */
    public function handle(Http\Request $request) {
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

    /**
     * Getter/Setter for a single pin handler
     *
     * @param Pin $handler
     *
     * @return Pin
     */
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
