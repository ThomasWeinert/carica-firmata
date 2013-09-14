<?php

namespace Carica\Firmata {

  use Carica\Io;

  class Buffer {

    use Io\Event\Emitter\Aggregation;

    private $_bytes = array();
    private $_versionReceived = FALSE;
    private $_lastResponse = NULL;

    private $_classes = array(
      Board::REPORT_VERSION => 'Midi\ReportVersion',
      Board::ANALOG_MESSAGE => 'Midi\Message',
      Board::DIGITAL_MESSAGE => 'Midi\Message',
      Board::STRING_DATA => 'Sysex\String',
      Board::PULSE_IN => 'Sysex\PulseIn',
      Board::QUERY_FIRMWARE => 'Sysex\QueryFirmware',
      Board::CAPABILITY_RESPONSE => 'Sysex\CapabilityResponse',
      Board::PIN_STATE_RESPONSE => 'Sysex\PinStateResponse',
      Board::ANALOG_MAPPING_RESPONSE => 'Sysex\AnalogMappingResponse',
      Board::I2C_REPLY => 'SysEx\I2CReply'
    );

    public function addData($data) {
      if (count($this->_bytes) == 0) {
        $data = ltrim($data, pack('C', 0));
      }
      $bytes = array_slice(unpack("C*", "\0".$data), 1);
      foreach ($bytes as $byte) {
        $this->addByte($byte);
      }
    }

    private function addByte($byte) {
      if (!$this->_versionReceived) {
        if ($byte !== Board::REPORT_VERSION) {
          return;
        } else {
          $this->_versionReceived = TRUE;
        }
      }
      $byteCount = count($this->_bytes);
      if ($byte == 0 && $byteCount == 0) {
        return;
      } else {
        $this->_bytes[] = $byte;
        ++$byteCount;
      }
      if ($byteCount > 0) {
        $first = reset($this->_bytes);
        $last = end($this->_bytes);
        if ($first === Board::START_SYSEX &&
            $last === Board::END_SYSEX) {
          if ($byteCount > 2) {
            $this->handleResponse($this->_bytes[1], array_slice($this->_bytes, 1, -1));
          }
          $this->_bytes = array();
        } elseif ($byteCount == 3 && $first !== Board::START_SYSEX) {
          $command = ($first < 240) ? ($first & 0xF0) : $first;
          $this->handleResponse($command, $this->_bytes);
          $this->_bytes = array();
        }
      }
    }

    private function handleResponse($command, array $bytes) {
      $response = NULL;
      if (isset($this->_classes[$command])) {
        $className = __NAMESPACE__.'\\Response\\'.$this->_classes[$command];
        $response = new $className($command, $bytes);
      }
      if ($response) {
        $this->_lastResponse = $response;
        $this->events()->emit('response', $response);
      }
    }

    public function getLastResponse() {
      return $this->_lastResponse;
    }
  }
}
