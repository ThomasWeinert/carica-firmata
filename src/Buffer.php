<?php

namespace Carica\Firmata {

  use Carica\Io;

  class Buffer
    implements Io\Event\HasEmitter {

    use Io\Event\Emitter\Aggregation;

    /**
     * @var array
     */
    private $_bytes = array();

    /**
     * @var bool
     */
    private $_versionReceived = FALSE;

    /**
     * @var null
     */
    private $_lastResponse = NULL;

    /**
     * @var array
     */
    private $_classes = array(
      Board::REPORT_VERSION => 'Midi\\ReportVersion',
      Board::ANALOG_MESSAGE => 'Midi\\Message',
      Board::DIGITAL_MESSAGE => 'Midi\\Message',
      Board::STRING_DATA => 'SysEx\\String',
      Board::PULSE_IN => 'SysEx\\PulseIn',
      Board::QUERY_FIRMWARE => 'SysEx\\QueryFirmware',
      Board::CAPABILITY_RESPONSE => 'SysEx\\CapabilityResponse',
      Board::PIN_STATE_RESPONSE => 'SysEx\\PinStateResponse',
      Board::ANALOG_MAPPING_RESPONSE => 'SysEx\\AnalogMappingResponse',
      Board::I2C_REPLY => 'SysEx\\I2CReply'
    );

    /**
     * @param string $data
     */
    public function addData($data) {
      if (count($this->_bytes) == 0) {
        $data = ltrim($data, pack('C', 0));
      }
      $bytes = array_slice(unpack("C*", "\0".$data), 1);
      foreach ($bytes as $byte) {
        $this->addByte($byte);
      }
    }

    /**
     * @param int $byte
     */
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

    /**
     * @param int $command
     * @param array $bytes
     */
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

    /**
     * @return Response
     */
    public function getLastResponse() {
      return $this->_lastResponse;
    }
  }
}
