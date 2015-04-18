<?php
namespace Carica\Firmata {

  use Carica\Io\Event;
  use Carica\Io\Device;
  
  class ShiftOut implements Device\ShiftOut {

    use Event\Emitter\Aggregation;

    /**
     * @var Pin
     */
    private $_latchPin = NULL;
    /**
     * @var Pin
     */
    private $_clockPin = NULL;
    /**
     * @var Pin
     */
    private $_dataPin = NULL;
    
    private $_highLatch = FALSE;
    
    public function __construct(Pin $latch, Pin $clock, Pin $data, $highLatch = FALSE) {
      $this->_latchPin = $latch;
      $this->_clockPin = $clock;
      $this->_dataPin = $data;
      $this->_highLatch = (bool)$highLatch;
      if (!($latch->board == $clock->board && $latch->board == $clock->board)) {
        throw new \InvalidArgumentException('Pins have to be on the same board');
      }
    }

    /**
     * Write data using shiftOut (this includes being and end)
     * 
     * @param $value
     * @param bool $isBigEndian
     */
    public function write($value, $isBigEndian = TRUE) {
      $this->begin();
      $this->transfer($value, $isBigEndian);
      $this->end();
    }

    /**
     * Begin transfer (put the latch pin to low)
     */
    public function begin() {
      $this->_latchPin->setMode(Pin::MODE_DIGITAL_OUTPUT);
      $this->_clockPin->setMode(Pin::MODE_DIGITAL_OUTPUT);
      $this->_dataPin->setMode(Pin::MODE_DIGITAL_OUTPUT);
      $this->_latchPin->setDigital($this->_highLatch);
    }
    
    /**
     * Begin transfer (put the latch pin to high)
     */
    public function end() {
      $this->_latchPin->setDigital(!$this->_highLatch);
    }

    /**
     * Shift out the data. The data kann be an integer values representing a
     * byte value (0 to 255) an array of integers or a binary string.
     *
     * @param int|array:int|string $value
     * @param bool $isBigEndian
     */
    public function transfer($value, $isBigEndian = TRUE) {
      $dataPort = floor($this->_dataPin->pin / 8);
      $clockPort = floor($this->_clockPin->pin / 8);
      $dataOffset = 1 << (int)($this->_dataPin->pin - ($dataPort * 8));
      $clockOffset = 1 << (int)($this->_clockPin->pin - ($clockPort * 8));
      $board = $this->_latchPin->board;
      if ($dataPort == $clockPort) {
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

      $write = function ($mask, $value) use ($board, $messages) {
        $board->stream()->write(
          $messages[($value & $mask) ? 'high' : 'low']
        );
      };

      if (is_string($value)) {
        $values = array_slice(unpack("C*", "\0".$value), 1);
      } elseif (is_array($value)) {
        $values = $value;
      } else {
        $values = array((int)$value);
      }

      foreach ($values as $value) {
        if ($isBigEndian) {
          for ($mask = 128; $mask > 0; $mask = $mask >> 1) {
            $write($value, $mask);
          }
        } else {
          for ($mask = 0; $mask < 128; $mask = $mask << 1) {
            $write($value, $mask);
          }
        }
      }
    }

    /**
     * Return the value for a digital port (Pins in groups of 8)
     * 
     * @param int $port
     * @return int
     */
    private function getDigitalPortValue($board, $port) {
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