Pulsar ORM
==========

[![Latest Stable Version](https://poser.pugx.org/pulsar/pulsar/v/stable.svg?style=flat)](https://packagist.org/packages/pulsar/pulsar)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)
[![Build Status](https://travis-ci.org/jaredtking/pulsar.svg?branch=master&style=flat)](https://travis-ci.org/jaredtking/pulsar)
[![Coverage Status](https://coveralls.io/repos/jaredtking/pulsar/badge.svg?style=flat)](https://coveralls.io/r/jaredtking/pulsar)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jaredtking/pulsar/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jaredtking/pulsar/?branch=master)
[![Total Downloads](https://poser.pugx.org/pulsar/pulsar/downloads.svg?style=flat)](https://packagist.org/packages/pulsar/pulsar)

Pulsar is an ORM implementing the [Active Record](https://en.wikipedia.org/wiki/Active_record_pattern) pattern in PHP.

## Getting Started

### Requirements

The main requirement is that PHP version 8.1 or later is used. The library depends on PDO or Doctrine DBAL to interact with the database.

## Installation

The easiest way to install Pulsar is with [composer](http://getcomposer.org):

```
composer require pulsar/pulsar
```

### Using Pulsar

See the [Pulsar Documentation](https://jaredtking.github.io/pulsar) for more information on how to use the library.

## Developing

### Tests

Use phpunit to run the included tests:

```
vendor/bin/phpunit
```

### Contributing

Please feel free to contribute by participating in the issues or by submitting a pull request. :-)

## License

The MIT License (MIT)

Copyright © 2015 Jared King

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.