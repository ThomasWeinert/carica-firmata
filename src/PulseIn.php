<?php
namespace Carica\Firmata {

  use Carica\Io\Deferred;
  use Carica\Io\Deferred\PromiseLike;
  use Carica\Io\Event\Emitter as EventEmitter;

  class PulseIn {

    use EventEmitter\Aggregation;

    public const COMMAND = 0x74;

    public const EVENT_PULSE_IN = 'pulse-in';

    /**
     * @var Board
     */
    private $_board;

    /**
     * @param Board $board
     */
    public function __construct(Board $board) {
      $this->_board = $board;
      $this->_board->events()->on(
        Board::EVENT_RESPONSE,
        function(Response $response) {
          if ($response->command === self::COMMAND) {
            $bytes = $response->getRawData();
            $pin = unpack('C', Response::decodeBytes([$bytes[1], $bytes[2]]));
            $pin = $pin[1];
            $duration = unpack('N', Response::decodeBytes(array_slice($bytes, 3)));
            $duration = $duration[1];
            $this->events()->emit(self::EVENT_PULSE_IN.'-'.$pin, $duration);
            $this->events()->emit(self::EVENT_PULSE_IN, $pin, $duration);
          }
        }
      );
    }

    /**
     * @param int $pin
     * @param int $value
     * @param int $pulseLength
     * @param int $timeout
     * @return PromiseLike
     */
    public function __invoke(
      int $pin, int $value = Board::DIGITAL_HIGH, int $pulseLength = 5, int $timeout = 1000000
    ): PromiseLike {
      $defer = new Deferred();
      $this->events()->once(
        self::EVENT_PULSE_IN.'-'.$pin,
        static function ($duration) use ($defer) {
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
