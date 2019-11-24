<?php
namespace Carica\Firmata {

  use Carica\Io\Device;
  use InvalidArgumentException;

  class ShiftOut implements Device\ShiftOut {

    /**
     * @var Pin
     */
    private $_latchPin;
    /**
     * @var Pin
     */
    private $_clockPin;
    /**
     * @var Pin
     */
    private $_dataPin;

    private $_highLatch;

    public function __construct(Pin $latch, Pin $clock, Pin $data, bool $highLatch = FALSE) {
      $this->_latchPin = $latch;
      $this->_clockPin = $clock;
      $this->_dataPin = $data;
      $this->_highLatch = $highLatch;
      if (!($latch->board === $clock->board && $latch->board === $data->board)) {
        throw new InvalidArgumentException('Pins have to be on the same board');
      }
    }

    /**
     * Write data using shiftOut (this includes being and end)
     *
     * @param int|string|int[] $value
     * @param bool $isBigEndian
     * @throws Exception\UnsupportedMode
     */
    public function write($value, bool $isBigEndian = TRUE): void {
      $this->begin();
      $this->transfer($value, $isBigEndian);
      $this->end();
    }

    /**
     * Begin transfer (put the latch pin to low)
     *
     * @throws Exception\UnsupportedMode
     */
    public function begin(): void {
      $this->_latchPin->setMode(Pin::MODE_OUTPUT);
      $this->_clockPin->setMode(Pin::MODE_OUTPUT);
      $this->_dataPin->setMode(Pin::MODE_OUTPUT);
      $this->_latchPin->setDigital($this->_highLatch);
    }

    /**
     * Begin transfer (put the latch pin to high)
     */
    public function end(): void {
      $this->_latchPin->setDigital(!$this->_highLatch);
    }

    /**
     * Shift out the data. The data kann be an integer values representing a
     * byte value (0 to 255) an array of integers or a binary string.
     *
     * @param int|int[]|string $value
     * @param bool $isBigEndian
     */
    public function transfer($value, bool $isBigEndian = TRUE): void {
      $dataPort = floor($this->_dataPin->pin / 8);
      $clockPort = floor($this->_clockPin->pin / 8);
      $dataOffset = 1 << (int)($this->_dataPin->pin - ($dataPort * 8));
      $clockOffset = 1 << (int)($this->_clockPin->pin - ($clockPort * 8));
      $board = $this->_latchPin->board;
      if ($dataPort === $clockPort) {
        $portValue = $this->getDigitalPortValue($board, $clockPort);
        $low = $portValue & ~$clockOffset & ~$dataOffset;
        $high = $portValue & ~$clockOffset | $dataOffset;
        $endLow = $low  | $clockOffset;
        $endHigh = $high | $clockOffset;
        $messages = [
          'low' => [
            Board::DIGITAL_MESSAGE | $clockPort, $low & 0x7F, ($low >> 7) & 0x7F,
            Board::DIGITAL_MESSAGE | $clockPort, $endLow & 0x7F, ($endLow >> 7) & 0x7F,
          ],
          'high' => [
            Board::DIGITAL_MESSAGE | $clockPort, $high & 0x7F, ($high >> 7) & 0x7F,
            Board::DIGITAL_MESSAGE | $clockPort, $endHigh & 0x7F, ($endHigh >> 7) & 0x7F,
          ]
        ];
      } else {
        $clockPortValue = $this->getDigitalPortValue($board, $clockPort);
        $dataPortValue = $this->getDigitalPortValue($board, $dataPort);
        $start = $clockPortValue & ~$clockOffset;
        $low = $dataPortValue & ~$dataOffset;
        $high = $dataPortValue | $dataOffset;
        $end = $clockPortValue | $clockOffset;
        $messages = [
          'low' => [
            Board::DIGITAL_MESSAGE | $clockPort, $start & 0x7F, ($start >> 7) & 0x7F,
            Board::DIGITAL_MESSAGE | $dataPort, $low & 0x7F, ($low >> 7) & 0x7F,
            Board::DIGITAL_MESSAGE | $clockPort, $end & 0x7F, ($end >> 7) & 0x7F,
          ],
          'high' => [
            Board::DIGITAL_MESSAGE | $clockPort, $start & 0x7F, ($start >> 7) & 0x7F,
            Board::DIGITAL_MESSAGE | $dataPort, $high & 0x7F, ($high >> 7) & 0x7F,
            Board::DIGITAL_MESSAGE | $clockPort, $end & 0x7F, ($end >> 7) & 0x7F,
          ]
        ];
      }

      $write = static function ($mask, $value) use ($board, $messages) {
        $board->stream()->write(
          $messages[($value & $mask) ? 'high' : 'low']
        );
      };

      if (is_string($value)) {
        $values = array_slice(unpack('C*', "\0".$value), 1);
      } elseif (is_array($value)) {
        $values = $value;
      } else {
        $values = array((int)$value);
      }

      foreach ($values as $partValue) {
        if ($isBigEndian) {
          for ($mask = 128; $mask > 0; $mask >>= 1) {
            $write($partValue, $mask);
          }
        } else {
          for ($mask = 0; $mask < 128; $mask <<= 1) {
            $write($partValue, $mask);
          }
        }
      }
    }

    /**
     * Return the value for a digital port (Pins in groups of 8)
     *
     * @param Board $board
     * @param int $port
     * @return int
     */
    private function getDigitalPortValue(Board $board, int $port): int {
      $portValue = 0;
      for ($i = 0; $i < 8; $i++) {
        $index = 8 * $port + $i;
        if (isset($board->pins[$index]) && $board->pins[$index]->getDigital()) {
          $portValue |= (1 << $i);
        }
      }
      return $portValue;
    }
  }
}
