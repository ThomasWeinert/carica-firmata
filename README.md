Carica Firmata
==============

License: [The MIT License](http://www.opensource.org/licenses/mit-license.php)
           
Copyright: 2013 Thomas Weinert <thomas@weinert.info>
 
Carica Firmata is a PHP client library for the Firmata protocol.

***It's a learning project not a product. Use it at your own risk.***

Basics
------

The repository provides a library to control an Arduino using the Firmata server sketch.

It is based on the [Javascript implementation](https://github.com/jgautier/firmata) by Julian Gautier.

Dependencies
------------

Carica Firmata uses Carica Io a non blocking I/O library for PHP. At least PHP 5.4 is needed. 
On Windows, [Serproxy](http://www.lspace.nildram.co.uk/freeware.html) should be used to map serial 
ports to tcp. 

Installation
------------

Carica Firmata is avaiable on [Packagist](https://packagist.org/packages/carica/firmata). Use Composer to add it as an
dependency into your own projects.

