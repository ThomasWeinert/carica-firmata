<?php
$board = require(__DIR__.'/../bootstrap.php');

use Carica\Io;
use Carica\Firmata;

$loop = Io\Event\Loop\Factory::get();

class Max7219 {

  /** @var Firmata\Board */
  private $_board = NULL;
  /** @var Firmata\Pin */
  private $_latch = NULL;
  /** @var Firmata\Pin */
  private $_clock = NULL;
  /** @var Firmata\Pin */
  private $_data = NULL;

  public function __construct(
    Firmata\Board $board, $latch, $clock, $data
  ) {
    $this->_board = $board;
    $this->_latch = $board->pins[$latch];
    $this->_clock = $board->pins[$clock];
    $this->_data = $board->pins[$data];
    $this->_latch->mode = Firmata\Pin::MODE_OUTPUT;
    $this->_clock->mode = Firmata\Pin::MODE_OUTPUT;
    $this->_data->mode = Firmata\Pin::MODE_OUTPUT;
  }

  public function transfer($address, $value) {
    $this->_latch->digital = FALSE;
    $this->_board->shiftOut($this->_data->pin, $this->_clock->pin, [$address, $value]);
    $this->_latch->digital = TRUE;
  }
}


$board
  ->activate()
  ->done(
    function () use ($board, $loop) {
      echo "Firmata ".$board->version." active\n";

      $digits = 8;
      $maximum = pow(10, $digits) - 0;

      $max = new Max7219(
        $board,
        8, // green, latch
        12, // blue, clock
        11// white, data
      );
      $max->transfer(0x0F, 0x01);
      sleep(1);
      $max->transfer(0x0F, 0x00);
      // Enable mode B
      $max->transfer(0x09, 0xFF);
      // Use max intensity
      $max->transfer(0x0A, 0xFF);
      // Only scan eight digits
      $max->transfer(0x0B, $digits - 1);
      // Turn on chip
      $max->transfer(0x0C, 0x01);


      $loop->setInterval(
        function () use ($max, $maximum) {
          static $number = 0;

          if (--$number < 0) {
            $number = $maximum;
          }

          $string = str_pad($number, 8, ' ',STR_PAD_LEFT);
          echo $string, "\n";

          $length = strlen($string);
          for($i = 0; $i < $length; $i++) {
            $digit = $string[$i] === ' ' ? 0 : (int)$string[$i];
            $max->transfer(8 - $i, $digit);
          }

        },
        200
      );
    }
  )
  ->fail(
    function ($error) {
      echo $error."\n";
    }
  );

if ($board->isActive()) {
  $loop->run();
}

