<?php

namespace Carica\Firmata\Exception {

  use Carica\Io;

  class NonExistingPin extends \Exception implements Io\Exception {

    /**
     * @param int $pin
     */
    public function __construct(int $pin) {
      parent::__construct(
        sprintf('Pin %d does not exists.', $pin)
      );
    }
  }
}
