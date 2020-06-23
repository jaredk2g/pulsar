Pulsar is an ORM implementing the [Active Record](https://en.wikipedia.org/wiki/Active_record_pattern) pattern in PHP.

## Installing Pulsar

The easiest way to install Pulsar is with [composer](http://getcomposer.org):

```
composer require pulsar/pulsar
```

Once you have the library installed you need to provide a database connection. The steps for this depend on which database library you use.

### Doctrine DBAL

If you are using Doctrine DBAL then you first need to require the `doctrine/dbal` dependency through composer. Then you need to provide a DBAL connection instance to the ORM.

```php
<?php

use Pulsar\Driver\DbalDriver;
use Pulsar\Model;

/** @var Doctrine\DBAL\Connection $connection */
$driver = new DbalDriver($connection);
Model::setDriver($driver);
```

### JAQB

If you are using JAQB (a query builder based on PDO) to connect to the database then you need to perform the following setup.

```php
<?php

use Pulsar\Driver\DatabaseDriver;
use Pulsar\Model;

$driver = new DatabaseDriver();
/** @var JAQB\ConnectionManager $connectionManager */
$driver->setConnectionManager($connectionManager);
Model::setDriver($driver);
```