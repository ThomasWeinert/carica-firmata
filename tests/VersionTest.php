<?php

namespace Carica\Firmata {

  include_once(__DIR__ . '/Bootstrap.php');

  class VersionTest extends \PHPUnit_Framework_TestCase {

    /**
     * @covers Carica\Firmata\Version
     */
    public function testConstructor() {
      $version = new Version(2, 3);
      $this->assertEquals(2, $version->major);
      $this->assertEquals(3, $version->minor);
    }

    /**
     * @covers Carica\Firmata\Version
     */
    public function testConstructorWithAllArguments() {
      $version = new Version(2, 3, 'Success');
      $this->assertEquals('Success', $version->text);
    }

    /**
     * @covers Carica\Firmata\Version
     */
    public function testToString() {
      $version = new Version(2, 3, 'Success');
      $this->assertEquals('Success 2.3', (string)$version);
    }

    /**
     * @covers Carica\Firmata\Version
     */
    public function testGetPropertyWithIvalidPropertyName() {
      $version = new Version(2, 3, 'Success');
      $this->setExpectedException('LogicException');
      $dummy = $version->INVALID;
    }

    /**
     * @covers Carica\Firmata\Version
     */
    public function testSetProperty() {
      $version = new Version(2, 3, 'Success');
      $this->setExpectedException('LogicException');
      $version->major = 'fail';
    }
  }
}