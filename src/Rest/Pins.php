<?php

namespace Carica\Firmata\Rest {

  use Carica\Firmata\Board as FirmataBoard;
  use Carica\Firmata\Rest\PinHandler as RestPinHandler;
  use Carica\Io\Network\HTTP\Request as HTTPRequest;
  use Carica\Io\Network\HTTP\Response as HTTPResponse;

  class Pins {

    /**
     * @var FirmataBoard
     */
    private $_board;

    /**
     * @var RestPinHandler
     */
    private $_pinHandler;

    /**
     * @param FirmataBoard $board
     */
    public function __construct(FirmataBoard $board) {
      $this->_board = $board;
    }

    /**
     * Make the class Callable
     *
     * @param HTTPRequest $request
     * @return HTTPResponse
     */
    public function __invoke(HTTPRequest $request): HTTPResponse {
      $response = $request->createResponse();
      $response->content = new HTTPResponse\Content\XML();
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
     * @param RestPinHandler $handler
     * @return RestPinHandler
     */
    public function pinHandler(RestPinHandler $handler = NULL): RestPinHandler {
      if (isset($handler)) {
        $this->_pinHandler = $handler;
      } elseif (NULL === $this->_pinHandler) {
        $this->_pinHandler = new RestPinHandler($this->_board);
      }
      return $this->_pinHandler;
    }
  }
}
