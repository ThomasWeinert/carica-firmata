<?php
namespace Carica\Firmata {

  use Carica\Io\Event;

  class I2C {

    use Event\Emitter\Aggregation;

    const REQUEST = 0x76;
    const REPLY = 0x77;
    const CONFIG = 0x78;

    const MODE_WRITE = 0;
    const MODE_READ = 1;
    const MODE_CONTINOUS_READ = 2;
    const MODE_STOP_READING = 3;

    /**
     * @var Board
     */
    private $_board = NULL;

    /**
     * @var bool
     */
    private $_isInitialized = FALSE;

    public function __construct(Board $board) {
      $this->_board = $board;
      $this->_board->events->on(
        'response',
        function(Response $response) {
          if ($response->command == self::REPLY) {
            $reply = new I2C\Reply(self::REPLY, $response->getRawData());
            $this->events()->emit('reply-'.$reply->slaveAddress, $reply->data);
            $this->events()->emit('reply', $reply->slaveAddress, $reply->data);
          }
        }
      );
    }

     /**
     * Allow i2c read/write to make sure that config was called.
     */
    private function ensureConfiguration() {
      if (!$this->_isInitialized) {
        $this->configure();
        $this->_isInitialized = true;
      }
    }

    public function configure($delay = 0) {
      $this
        ->_board
        ->stream()
        ->write(
           array(
             Board::START_SYSEX,
             self::CONFIG,
             $delay >> 0xFF,
             ($delay >> 8) & 0xFF,
             Board::END_SYSEX
           )
        );
      $this->_isInitialized = true;
    }

    /**
     * Write some data to an i2c device
     *
     * @param integer $slaveAddress
     * @param string $data
     */
    public function write($slaveAddress, $data) {
      $this->ensureConfiguration();
      $request = new I2C\Request\Write($this, $slaveAddress, $data);
      $request->send();
    }

    /**
     * Request data from an i2c device and trigger callback if the
     * data is sent.
     *
     * @param integer $slaveAddress
     * @param integer $byteCount
     * @param callable $callback
     */
    public function read($slaveAddress, $byteCount, Callable $callback) {
      $this->ensureConfiguration();
      $this->events()->once('reply-'.$slaveAddress, $callback);
      $request = new I2C\Request\Read($this, $slaveAddress, $byteCount);
      $request->send();
    }
  }
}