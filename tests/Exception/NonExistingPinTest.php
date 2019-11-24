<?php

namespace Carica\Firmata\Exception {

  use PHPUnit\Framework\TestCase;

  include_once(__DIR__.'/../Bootstrap.php');

  class NonExistingPinTest extends TestCase {

    public function testConstructor(): void {
      $exception = new NonExistingPin(42);
      $this->assertEquals(
        'Pin 42 does not exists.',
        $exception->getMessage()
      );
    }
  }
}
