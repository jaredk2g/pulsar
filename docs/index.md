Pulsar is an ORM implementing the [Active Record](https://en.wikipedia.org/wiki/Active_record_pattern) pattern in PHP.

## Table of Contents

- [Setup](#setup)
- [Defining Models](#defining-models)
- [Using Models](#using-models)
- [Saving Models](#saving-models)
- [Querying Models](#querying-models)
- [Relationships](#relationships)
- [Validation](#validation)
- [Lifecycle Events](#lifecycle-events)
- [Transactions](#transactions)
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
            // Then stores passwords hashed with password_hash().
            'validate' => 'password',
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

## Saving Models

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

You can validate model properties before the model is saved to the database.

[Available Validators](validators)

Validation can be enabled on a per-property basis with the `validate` parameter. Different rule syntaxes are supported for simple and complex validation rules.

```php
<?php

use Pulsar\Model;

class User extends Model
{
    protected static $properties = [
        // single validation rule
        'email' => [
            'validate' => 'email',
        ],
        // multiple validation rules
        'password' => [
            'validate' => [
                'matching',
                ['password', 'min' => 8],
            ],
        ],
        // single validation rule with options
        'balance' => [
            'validate' => ['callable', 'fn' => [self::class, 'validateBalance']],
        ],
    ];

    static function validateBalance(&$value, array $options, Model $model): bool
    {
        // Custom validation logic goes here.
        // Supply your own error message with:
        // $model->getErrors()->add('my error', ['field' => 'balance']);
        return true;
    }
}
``` 

### Validation Errors

If a model fails validation then any error messages can be retrieved with `getErrors()`. 

```php
$user = new User(['email' => 'not valid']);
$user->save();
echo $user->getErrors(); // Email must be a valid email address
```

### valid()

You can also check if a model passes validation without saving it.

```php
$user->valid();
```

Get a list of all error messages:

```php
$user->getErrors()->all();
```

Find an error for a specific field:

```php
$user->getErrors()->find('password');
```

Check if an error exists for a specific field:

```php
$user->getErrors()->has('username');
```

### I18n

Validation messages can be translated to the user's locale. Pulsar provides a simple translation implementation although it is trivial to implement `TranslatorInterface` to plug into your existing i18n system. In order to enable translations you must at the beginning of script execution provide a translator object.

```php
<?php

use Pulsar\Errors;
use Pulsar\Translator;

$translator = new Translator();
$translator->setDataDir('...');
Errors::setTranslator($translator);
```

## Lifecycle Events

```php
<?php

use Pulsar\Model;
use Pulsar\Event\AbstractEvent;
use Pulsar\Type;

class LineItem extends Model
{
    protected static $properties = [
        'quantity' => [
            'type' => Type::FLOAT,
        ],
        'unit_cost' => [
            'type' => Type::FLOAT,
        ],
        'total' => [
            'type' => Type::FLOAT,
        ],
    ];

    protected function initialize()
    {
        self::saving(function(AbstractEvent $event) {
            $model = $event->getModel();
            $model->total = $model->quantity * $model->unit_cost;
        });
    }
}
```

Lifecycle event listeners can be added to a model. The best place to do this is by defining the `initialize()` method, which will only be called once per program execution. The second argument allows an optional priority to be specified. Higher priority listeners execute first.

- `creating()` - Executed before a model is inserted in the database
- `created()` - Executed after a model is inserted in the database
- `updating()` - Executed before a model is updated in the database
- `updated()` - Executed after a model is updated in the database
- `deleting()` - Executed before a model is deleted from the database
- `deleted()` - Executed after a model is deleted from the database

Aliases:
- `saving()` - Alias for `creating` and `updating`
- `saved()` - Alias for `created` and `updated`
- `beforePersist()` - Alias for `creating`, `updating`, and `deleting`
- `afterPersist()` - Alias for `created`, `updated`, and `deleted`

### Stopping event propagation

Using `stopPropagation()` in an event listener will stop the save operation. Any future event listeners will not be called. If transactions are used then the database will be rolled back. 

```php
<?php

use Pulsar\Model;
use Pulsar\Event\AbstractEvent;

class User extends Model
{
    // ...

    protected function initialize()
    {
        self::creating(function(AbstractEvent $event) {
            $model = $event->getModel();
            if ($model->mfa_enabled && !$model->phone) {
                $model->getErrors()->add('You must supply a phone number to enable 2FA');
                $event->stopPropagation();
            }
        });
    }
}
```

## Transactions

You can wrap the model save operations in a database transaction by returning true in `useTransactions()`. Transactions are disabled by default.

```php
<?php

use Pulsar\Model;

class Payment extends Model
{
    // ...

    protected function usesTransactions(): bool
    {
        return true;
    }
}
```

## Learn More

- [Model Definition Reference](model-definitions)
- [Validators Reference](validators)