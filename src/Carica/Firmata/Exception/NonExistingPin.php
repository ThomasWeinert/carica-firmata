<?php

namespace Carica\Firmata\Exception {

  use Carica\Io;

  class NonExistingPin extends \Exception implements Io\Exception {

    public function __construct($pin) {
      parent::__construct(
        sprintf('Pin %d does not exists.', $pin)
      );
    }
  }
}