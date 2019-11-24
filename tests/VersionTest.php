<?php

namespace Carica\Firmata {

  use LogicException;
  use PHPUnit\Framework\TestCase;

  include_once(__DIR__ . '/Bootstrap.php');

  class VersionTest extends TestCase {

    /**
     * @covers \Carica\Firmata\Version
     */
    public function testConstructor(): void {
      $version = new Version(2, 3);
      $this->assertEquals(2, $version->major);
      $this->assertEquals(3, $version->minor);
    }

    /**
     * @covers \Carica\Firmata\Version
     */
    public function testConstructorWithAllArguments(): void {
      $version = new Version(2, 3, 'Success');
      $this->assertEquals('Success', $version->text);
    }

    /**
     * @covers \Carica\Firmata\Version
     */
    public function testToString(): void {
      $version = new Version(2, 3, 'Success');
      $this->assertEquals('Success 2.3', (string)$version);
    }

    /**
     * @covers \Carica\Firmata\Version
     */
    public function testGetPropertyWithIvalidPropertyName(): void {
      $version = new Version(2, 3, 'Success');
      $this->expectException(LogicException::class);
      /** @noinspection PhpUndefinedFieldInspection */
      $version->INVALID;
    }

    /**
     * @covers \Carica\Firmata\Version
     */
    public function testSetProperty(): void {
      $version = new Version(2, 3, 'Success');
      $this->expectException(LogicException::class);
      $version->major = 'fail';
    }
  }
}
