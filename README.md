Carica Firmata
==============

[![Build Status](https://travis-ci.org/ThomasWeinert/carica-firmata.svg?branch=master)](https://travis-ci.org/ThomasWeinert/carica-firmata)
[![HHVM Status](http://hhvm.h4cc.de/badge/carica/firmata.png)](http://hhvm.h4cc.de/package/carica/firmata)
[![License](https://poser.pugx.org/carica/firmata/license.svg)](https://packagist.org/packages/carica/firmata)
[![Total Downloads](https://poser.pugx.org/carica/firmata/downloads.svg)](https://packagist.org/packages/carica/firmata)
[![Latest Stable Version](https://poser.pugx.org/carica/firmata/v/stable.svg)](https://packagist.org/packages/carica/firmata)
[![Latest Unstable Version](https://poser.pugx.org/carica/firmata/v/unstable.svg)](https://packagist.org/packages/carica/firmata)

License: [The MIT License](http://www.opensource.org/licenses/mit-license.php)

Copyright: 2013 Thomas Weinert <thomas@weinert.info>

Carica Firmata is a PHP client library for the Firmata protocol.

***It's a learning project not a product. Use it at your own risk.***

Basics
------

The repository provides a library to control an Arduino using the Firmata server sketch.

It was originally based on the [Javascript implementation](https://github.com/jgautier/firmata) by Julian Gautier.

Dependencies
------------

Carica Firmata uses Carica Io a non blocking I/O library for PHP. At least PHP 5.4 is needed.
On Windows, [Serproxy](http://www.lspace.nildram.co.uk/freeware.html) should be used to map serial
ports to tcp.

Installation
------------

Carica Firmata is avaiable on [Packagist](https://packagist.org/packages/carica/firmata). Use Composer to add it as an
dependency into your own projects.

Carica\Firmata\Pin
------------------

Carica Firmata provides an OOP abstraction for the pins on an Arduino.
Values are stored and only send to the board if changed. The values can be changed using different properties.

* $pin->value  is the original, internal value, the maximum value depends on the resolution of the mode.
* $pin->digital is a boolean value and sets the pin to low/high
* $pin->analog is a float between 0 and 1, the need value is calculated internally using the resolution of the current mode.

It is suggested to use the digital/analog properties to support different resolutions.

Carica Chip
-----------

Carica Chip uses this project and provides device objects like a led or a motor. Take
a look at: https://github.com/ThomasWeinert/carica-chip