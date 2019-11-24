<?php
namespace Carica\Firmata {

  use Carica\Io\ByteArray;
  use Carica\Io\Deferred;
  use Carica\Io\Device\I2C as I2CDevice;
  use Carica\Io\Event\Emitter as EventEmitter;
  use OutOfRangeException;

  class I2C implements I2CDevice {

    use EventEmitter\Aggregation;

    public const REQUEST = 0x76;
    public const REPLY = 0x77;
    public const CONFIG = 0x78;

    public const MODE_WRITE = 0;
    public const MODE_READ = 1;
    public const MODE_CONTINOUS_READ = 2;
    public const MODE_STOP_READING = 3;

    public const EVENT_REPLY = 'reply';

    /**
     * @var Board
     */
    private $_board;

    /**
     * @var bool Debug mode, blocks actual write() and issues a debug event.
     */
    private $_debug = FALSE;

    /**
     * @var bool
     */
    private $_isInitialized = FALSE;

    /**
     * @var int $_deviceAddress
     */
    private $_deviceAddress;

    public function __construct(Board $board, int $deviceAddress) {
      $this->_board = $board;
      $this->_board->events()->on(
        Board::EVENT_RESPONSE,
        function(Response $response) {
          if ($response->command === self::REPLY) {
            $reply = new I2C\Reply(self::REPLY, $response->getRawData());
            $this->events()->emit(self::EVENT_REPLY.'-'.$reply->slaveAddress, $reply->data);
          }
        }
      );
      $this->_deviceAddress = $deviceAddress;
      if ($this->_deviceAddress <= 0 || $this->_deviceAddress > 127) {
        throw new OutOfRangeException('Invalid I2C address.');
      }
    }

    /**
     * Enable/disable debug mode - Blocks write actions and emits a debug event with the data.
     * @param bool $enable
     */
    public function debug(bool $enable): void {
      $this->_debug = $enable;
    }

    /**
     * Emit the debug event with the address as hex string and data bytes in binary representation.
     *
     * @param string $method
     * @param array $data
     */
    private function emitDebug(string $method, array $data): void {
      $this->emitEvent(
        'debug',
        $method,
        '0x'.str_pad(dechex($this->_deviceAddress), 2, '0', STR_PAD_LEFT),
        ByteArray::createFromArray($data)->asBitString()
      );
    }

     /**
     * Allow i2c read/write to make sure that config was called.
     */
    private function ensureConfiguration(): void {
      if (!$this->_isInitialized) {
        $this->configure();
        $this->_isInitialized = true;
      }
    }

    public function configure(int $delay = 0): void {
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
     * @param array $data
     */
    public function write(array $data): void {
      if ($this->_debug) {
        $this->emitDebug(__FUNCTION__, $data);
        return;
      }
      $this->ensureConfiguration();
      $request = new I2C\Request($this->_board, $this->_deviceAddress, self::MODE_WRITE, $data);
      $request->send();
    }

    /**
     * Request data from an i2c device and trigger callback if the
     * data is sent.
     *
     * @param int $byteCount
     * @return Deferred\PromiseLike
     */
    public function read(int $byteCount): Deferred\PromiseLike {
      $this->ensureConfiguration();
      $defer = new Deferred();
      $this->events()->once(
        self::EVENT_REPLY.'-'.$this->_deviceAddress,
        static function ($bytes) use ($defer, $byteCount) {
          if (count($bytes) === $byteCount) {
            $defer->resolve($bytes);
          } else {
            $defer->reject('Invalid I2C response.');
          }
        }
      );
      $request = new I2C\Request(
        $this->_board, $this->_deviceAddress, self::MODE_READ, $byteCount
      );
      $request->send();
      return $defer;
    }

    /**
     * @param int $byteCount
     * @param callable $listener
     */
    public function startReading(int $byteCount, callable $listener): void {
      $deviceAddress = $this->_deviceAddress;
      $this->ensureConfiguration();
      $this->stopReading();
      $this->events()->on(
        self::EVENT_REPLY.'-'.$deviceAddress,
        function ($bytes) use ($listener, $byteCount) {
          if (count($bytes) === $byteCount) {
            $listener($bytes);
          } else {
            $this->stopReading();
          }
        }
      );
      $request = new I2C\Request(
        $this->_board, $deviceAddress, self::MODE_CONTINOUS_READ, $byteCount
      );
      $request->send();
    }

    public function stopReading(): void {
      $this->events()->removeAllListeners(self::EVENT_REPLY.'-'.$this->_deviceAddress);
      $request = new I2C\Request(
        $this->_board, $this->_deviceAddress, self::MODE_STOP_READING
      );
      $request->send();
    }
  }
}
