<?php

namespace Carica\Firmata {

  use Carica\Io;

  class Buffer {

    use Io\Event\Emitter\Aggregation;

    private $_bytes = array();
    private $_versionReceived = FALSE;
    private $_lastReponse = NULL;

    private $_classes = array(
      COMMAND_REPORT_VERSION => 'Midi\ReportVersion',
      COMMAND_ANALOG_MESSAGE => 'Midi\Message',
      COMMAND_DIGITAL_MESSAGE => 'Midi\Message',
      COMMAND_STRING_DATA => 'Sysex\String',
      COMMAND_PULSE_IN => 'Sysex\PulseIn',
      COMMAND_QUERY_FIRMWARE => 'Sysex\QueryFirmware',
      COMMAND_CAPABILITY_RESPONSE => 'Sysex\CapabilityResponse',
      COMMAND_PIN_STATE_RESPONSE => 'Sysex\PinStateResponse',
      COMMAND_ANALOG_MAPPING_RESPONSE => 'Sysex\AnalogMappingResponse',
      COMMAND_I2C_REPLY => 'SysEx\I2CReply'
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
        if ($byte !== COMMAND_REPORT_VERSION) {
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
        if ($first === COMMAND_START_SYSEX &&
            $last === COMMAND_END_SYSEX) {
          if ($byteCount > 2) {
            $this->handleResponse($this->_bytes[1], array_slice($this->_bytes, 1, -1));
          }
          $this->_bytes = array();
        } elseif ($byteCount == 3 && $first !== COMMAND_START_SYSEX) {
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
