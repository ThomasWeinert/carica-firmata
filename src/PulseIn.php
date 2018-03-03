<?php
namespace Carica\Firmata {

  use Carica\Io\Event;

  class PulseIn {

    use Event\Emitter\Aggregation;

    const COMMAND = 0x74;

    /**
     * @var Board
     */
    private $_board = NULL;

    public function __construct(Board $board) {
      $this->_board = $board;
      $this->_board->events()->on(
        'response',
        function(Response $response) {
          if ($response->command == self::COMMAND) {
            $bytes = $response->getRawData();
            $pin = unpack('C', Response::decodeBytes([$bytes[1], $bytes[2]]));
            $pin = $pin[1];
            $duration = unpack('N', Response::decodeBytes(array_slice($bytes, 3)));
            $duration = $duration[1];
            $this->events()->emit('pulse-in-'.$pin, $duration);
            $this->events()->emit('pulse-in', $pin, $duration);
          }
        }
      );
    }

    public function __invoke(...$arguments) {
      return $this->trigger(...$arguments);
    }

    public function trigger(
      $pin, $value = Board::DIGITAL_HIGH, $pulseLength = 5, $timeout = 1000000
    ) {
      $defer = new \Carica\Io\Deferred();
      $this->events()->once(
        'pulse-in-'.$pin,
        function ($duration) use ($defer) {
          $defer->resolve($duration);
        }
      );
      $data = pack(
        'CCCC',
        Board::START_SYSEX,
        self::COMMAND,
        $pin,
        $value
      );
      $data .= Request::encodeBytes(
        pack(
         'NN',
         $pulseLength,
         $timeout
        )
      );
      $data .= pack(
        'C',
        Board::END_SYSEX
      );
      $this->_board->stream()->write($data);
      return $defer->promise();
    }
  }
}
