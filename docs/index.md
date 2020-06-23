Pulsar is an ORM implementing the [Active Record](https://en.wikipedia.org/wiki/Active_record_pattern) pattern in PHP.

## Table of Contents

- [Setup](#setup)
- [Defining Models](#defining-models)
- [Using Models](#using-models)
- [Creating and Modifying Models](#creating-and-modifying-models)
- [Querying Models](#querying-models)
- [Relationships](#relationships)
- [Validation](#validation)
- [Lifecycle Events](#lifecycle-events)
- [Learn More](#learn-more)

## Setup

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

## Defining Models

You can define models by creating a new class extending the base model class.

Pulsar does not setup or provide the database schema. The assumption is that a table exists based on the plural version of the model name (for example a `User` model requires a `Users` table) and columns exist for each named property.

The example below shows how to create a user model. Please consult the model definition settings to see a complete list of all possible settings.

[Model Definition Reference](model-definitions)

```php
<?php

use Pulsar\Model;
use Pulsar\Type;

class User extends Model
{
    // Unless specified the `id` property is automatically added as an autoincrement integer
    protected static $properties = [
        'first_name' => [
            'required' => true,
        ],
        'last_name' => [
            'null' => true,
        ],
        'email' => [
            'validate' => 'email',
            'required' => true,
        ],
        'password' => [
            // Validates that matching passwords are provided and at least 8 characters.
            // Then stores passwords hashed with password_hash().
            'validate' => 'matching|password:8',
            'required' => true,
        ],
        'balance' => [
            'type' => Type::FLOAT,
        ],
        'last_sign_in' => [
            'type' => Type::DATE,
            'null' => true,
        ]
    ];
    
    // Add `created_at` and `updated_at` properties
    protected static $autoTimestamps;
}
```

## Using Models

Properties can be accessed using normal property syntax on a model instance:

```php
$user = new User([
    'first_name' => 'Bob',
    'last_name' => 'Smith',
    'email' => 'bob@example.com',
    'balance' => 100.50,
]);

echo 'Hello, '.$user->first_name;
echo 'Your balance is $'.$user->balance;
```

Properties can be set using the same syntax. The values are kept in memory until save() is called.

```php
$user->email = 'bob@gmail.com';
```

### toArray()

The `toArray()` method converts a model to an array:

```php
$user->toArray();
```

## Creating and Modifying Models

The model can be saved with the `save()` method. If the model does not exist yet then `create()` will be used and a new record is inserted. If the model does exist then `set()` will be called which updates an existing row.

```php
$user = new User([
    'first_name' => 'Bob',
    'last_name' => 'Smith',
    'email' => 'bob@example.com',
]);
$user->save(); // creates a new row in Users table

$user->last_sign_in = time();
$user->balance = 1000;
$user->save(); // changes the `last_sign_in` and `balance` columns 
``` 

### dirty()

The dirty method allows checking if a model has unsaved values. If you want to check if a model has ANY unsaved value then use:

```php
$user = new User();
$user->dirty(); // returns false
$user->first_name = 'Bob';
$user->dirty(); // returns true 
```

You can check if a specific property has an unsaved value:

```php
$user->dirty('email');
```

And you can also check if a property has an unsaved value that is different from the saved value. If the model has not been saved yet then this will only check for the presence of an unsaved value.

```php
$user->dirty('email', true);
```

## Querying Models

Model classes include a fluent interface for querying models from the database.

### find()

The `find()` method allows a model to be loaded from the database given its ID.

```php
// locate a user with ID # 1234
User::find(1234);
``` 

### first()

The first() method will return a fixed number of results from a query.

```php
// returns a User model or null, if no results
$users = User::where('email', 'alice@example.com')->first();
```

The optional first argument is the number of results to return.

```php
// returns an array containing between 0 and 10 models, sorted by first name
$users = User::where('last_name', 'Smith')
    ->sort('first_name', 'ASC')
    ->first(10);
```

### all()

If you want to be able to iterate through all results for a query then the `all()` method gives you an Iterator object. It seamlessly fetches models in sets of 100 to provide better performance with large result sets.

```php
$users = User::all();
foreach ($users as $user) {
    echo "Found user: {$user->first_name} {$user->last_name}";
}
```

```php
$users = User::where('last_name', 'Smith')->all();
```

### Aggregate Functions

Aggregate functions are available to query individual properties.

#### count()

```php
User::count();
```

#### sum()

```php
User::sum('balance');
``` 

#### average()

```php
User::average('balance');
``` 

#### min()

```php
User::min('balance');
``` 

#### max()

```php
User::max('balance');
``` 

## Relationships

Pulsar allows you to define relationships between models and makes it easy to access 

### Belongs To

```php
use Pulsar\Model;

class Car extends Model
{
    protected static $properties = [
        'garage' => [
            'belongs_to' => Garage::class,
        ],
    ];
}
```

The garage model can be set or accessed with `$car->garage`.

### Has One

```php
use Pulsar\Model;

class Person extends Model
{
    protected static $properties = [
        'garage' => [
            'has_one' => Garage::class,
        ],
    ];
}
```

The garage model can be accessed with `$person->garage`.

### Belongs To Many

```php
use Pulsar\Model;

class BlogPost extends Model
{
    protected static $properties = [
        'categories' => [
            'belongs_to_many' => Category::class,
        ],
    ];
}
```

The category models can then be set or accessed with `$customer->categories`.

### Has Many

```php
use Pulsar\Model;

class Garage extends Model
{
    protected static $properties = [
        'cars' => [
            'has_many' => Car::class,
        ],
    ];
}
```

The car models can then be set or accessed with `$garage->cars`.

### Polymorphic

```php
use Pulsar\Model;

class Customer extends Model
{
    protected static $properties = [
        'payment_method' => [
            'morphs_to' => [
                'card' => Card::class,
                'bank_account' => BankAccount::class,
            ],
        ],
    ];
}
```

The payment method model can then be set or accessed with `$customer->payment_method`.

### Eager Loading

Coming soon....

## Validation

Coming soon....

## Lifecycle Events

Coming soon....

## Learn More

- [Model Definition Reference](model-definitions)