[Return to main page](/pulsar)

Model Definitions
=====

The properties for each model are defined using the `$properties` variable on every model class. Each element is a key-value array, with the key corresponding to the property name.

Standard Options:
- [type](#type)
- [mutable](#mutable)
- [persisted](#persisted)
- [null](#null)
- [required](#required)
- [validate](#validate)
- [default](#default)
- [encrypted](#encrypted)
- [in_array](#in_array)

Relationships:
- [belongs_to](#belongs_to)
- [has_one](#has_one)
- [belongs_to_many](#belongs_to_many)
- [has_many](#has_many)
- [morphs_to](#morphs_to)
- [foreign_key](#foreign_key)
- [local_key](#local_key)
- [pivot_tablename](#pivot_tablename)

## Standard Options

### type

The data type of the property. This setting will type cast values when retrieved from the database to a PHP value. If the type is not specified then no type casting is performed and the value is returned as provided by the database driver. 

Supported Types:
- `string`
- `float`
- `integer`
- `boolean`
- `date`
- `array`
- `object`

String, Optional, Default: `null`

### mutable

Specifies whether the property can be set (mutated)

Types of mutability:
- `mutable` - The property can be modified when creating or updating the model.
- `mutable_create_only` - The property can ONLY be modified when creating the model.
- `immutable` - The property cannot be modified when creating or updating the model.

String, Optional, Default: `mutable`

### persisted

Specifies whether the property is saved to the database.

Boolean, Optional, Default: `true`

### null

Specifies whether the property is allowed to have null values.

Boolean, Optional, Default: `false`

### required

Specifies whether the property value must be provided in order to create a new model.

Boolean, Optional, Default: `falase`

### validate

Validation rule(s) that are used by Validator::validate() to validate the property before the model is saved. If the validation rules do not succeed for the property then the model is blocked from being saved.

String or Array, Optional, Default: `null`

### default

This value is returned in the absence of a value existing for the property, such as when the model is being created or accessing the property before the model has been saved.

Optional, Default: `null`

### encrypted

Enables property-level encryption. Pulsar will handle encryption to/from the databse using [php-encyrption](https://github.com/defuse/php-encryption).

Boolean, Optional, Default: `false`

### in_array

Indicates whether the property will be included in the array representation of the model via `Model::toArray()`.

Boolean, Optional, Default: `true` (`false` for relationships)

## Relationships

### belongs_to

Establishes a belongs-to relationship. This value should be the class name of the model that is on the other side of the relationship.

String, Optional, Default: `null`

### has_one

Establishes a has-one relationship. This value should be the class name of the model that is on the other side of the relationship.

String, Optional, Default: `null`

### belongs_to_many

Establishes a belongs-to-many relationship. This value should be the class name of the model that is on the other side of the relationship.

String, Optional, Default: `null`

### has_many

Establishes a has-many relationship. This value should be the class name of the model that is on the other side of the relationship.

String, Optional, Default: `null`

### morphs_to

Establishes a polymorphic relationship. This value should be a key-value list of the model class names on the other side of the relationship.

Array, Optional, Default: `null`

### foreign_key

Overrides the column name of the foreign key for a relationship property.

String, Optional, Default: `null`

### local_key

Overrides the column name of the local key for a relationship property.

String, Optional, Default: `null`

### pivot_tablename

Overrides the table name of the pivot table for a belongs-to-many relationship.

String, Optional, Default: `null`
