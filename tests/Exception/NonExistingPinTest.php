<?php

namespace Carica\Firmata\Exception {

  include_once(__DIR__ . '/../Bootstrap.php');

  class NonExistingPinTest extends \PHPUnit\Framework\TestCase {

    public function testConstructor() {
      $exception = new NonExistingPin(42);
      $this->assertEquals(
        'Pin 42 does not exists.',
        $exception->getMessage()
      );
    }
  }
}
