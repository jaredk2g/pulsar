# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased
### Added
- The new `ListenerException` class can be thrown from event listeners as a shortcut to stop events.
- A `deleted` property has been added to soft deleted models.
- The `Query::one()` method finds exactly one model or throws an exception.
- The `Query::oneOrNull()` method finds exactly one model or returns null.
- Capture the options of the failing validation rule for more accurate error messages.

### Changed
- Moved adding event listeners and dispatching events to `EventManager`
- Queries against models that support soft deletes now return all models by default. Use `::withoutDeleted()` to exclude deleted models from a query.
- Soft delete logic has been moved to the `SoftDelete` trait
- Use @mixin instead of @method for query operations on model
- `Query::first()` now returns an array of results when the limit is 1
- Added return typehint to `AclModel::hasPermission()`

### Fixed
- Rollback database transaction after uncaught exception during model persistance.

### Removed
- `Model::getDispatcher()` was removed and replaced with the `EventManager` class

## 0.11 - 2020-07-26
### Added
- You can now supply your own translation implementation with `TranslatorInterface`. A simple translation implementation is provided with Pulsar.
- The `Errors::setTranslator()` method can set the global translation implementation.
- Models now have a `buildDefinition()` method that can be overridden to dynamically customize the model properties.
- Added `Model::dirty()` to check for unsaved values, and detect changes to those values.
- Added `Type::*`, `Property::*`, and `Relationship::*` constants to replace removed `Model` constants.
- New method `Model::hasId()` will indicate when a model has a complete identifier.
- Model definitions have new shortcuts to define relationships: `belongs_to`, `belongs_to_many`, `has_many`, `has_one`, and `morphs_to`.
- Added `encrypt` validation rule to encrypt values prior to saving to database using defuse/php-encryption.
- Added `encrypt` setting to model definitions that when enabled will encrypt the property value in the database.
- Added polymorphic relationship type.
- Saved values are no longer validated by `Model::valid()`. Only unsaved values will be validated.
- Callable validation rules can now set their own validation error message.
- Added `Model::beforePersist()` and `Model::afterPersist()` shortcut to install lifecycle event listeners for all create, update, and delete.
- Added `Model::getMassAssignmentWhitelist()` and `Model::getMassAssignmentBlacklist()` that can be overriden to define mass assignment rules.
- Added `in_array` model definition setting to indicate whether a property is included in the array representation.

### Changed
- Make model internal properties private when possible.
- The package no longer depends on `infuse/libs`.
- Renamed `Property` class to `Type`.
- A new `Definition` class contains all the properties that belong to a method. Retrieving the model properties with `Model::definition()` will return this object instead of an array.
- A new `Property` class encapsulates the definition of a model property. The model property methods will return this object instead of an array.
- Moved `Model::cast()` to `Type::cast()`
- Marked classes as final when appropriate.
- The model constructor no longer has an `$id` argument. Instead you can use `new Model(['id' => 1234])`.
- Changed the structure of the `validate` property setting to be an array with named options.
- The unique constraint is now a validation rule specified as: `['validate' => ['unique', 'column' => 'email']]`.
- Callable validation rules must now be specified as: `['validate' => ['callable', 'fn' => ...]]`.
- The error stack represents error messages as objects instead of arrays.
- Lifecycle events are now represented as a different class for each event type.
- Automatic timestamps are now installed with the `AutoTimestamps` trait.
- Soft delete is now enabled with the `SoftDelete` trait.
- Deprecated `Model::$hidden`, `Model::$appended`, `Model::$permitted`, and `Model::$protected`.

### Fixed
- Use a strict equality check when casting an empty string to null on a nullable property. Previously this would check for a falsey value.
- Ensure `Model::definition()` is always called in static context

### Removed
- Removed `Errors::setLocale`, `Errors::getLocale()`, and `Errors::setGlobalLocale()`. It is now required to use `Errors::setTranslator()`.
- Removed the `toArrayHook()` call. It is recommended to override `toArray()` if it is necessary to modify its output.
- Removed `Model::TYPE_*`, `Model` mutability, and `Model::RELATIONSHIP_*` constants.
- Removed `Model::getProperties()`, `::hasProperty()`, and `::getProperty()`.
- Removed the `password_php` validation rule in favor of `password`.
- It is no longer possible to supply a locale when grabbing error messages.
- Removed the `ModelEvent` class.

## 0.10 - 2020-04-22
### Added
- Added a DBAL driver.
- Model operations will now be wrapped in database transactions by overriding the `Model::usesTransactions()` method. 

### Changed
- Added argument and return type hinting to most methods.

### Fixed
- Ensure that the array property type always returns an array

### Removed
- Removed the `DriverException::getException()` method in favor of `getPrevious()`.
- Removed the number property type. Use integer or float instead.
- Removed `exists()` on model class.
- Removed `title` setting on model properties.
- Removed `ACLModel::setRequester()` and `ACLModel::getRequester()`
- Removed `Errors::errors()`, `Errors::messages()`, and `Errors::push()`
- Removed `Query::totalRecords()`
- Removed the `preSetHook()` method call during the `model.updating` event. If you need to use this functionality install the below event listener in your model.
  ```php
   use Pulsar\Event\AbstractEvent;

   self::updating(function (AbstractEvent $modelEvent) {
     $model = $modelEvent->getModel();
     if (!$model->preSetHook($model->_unsaved)) {
         $modelEvent->stopPropagation();
     }
   }, -512);
   ```

## 0.9.1 - 2019-11-16
## Fixed
- Querying a belongs-to relationship with eager loading could return incorrect results. 

## 0.9 - 2019-08-13
## Changed
- Updated Symfony dependency to version 4.3+
- PHP 7.1 is the minimum supported version

## 0.8 - 2019-01-19
### Added
- There is a `relation_type` setting on model properties that defaults to `belongs_to`.
- Added a `local_key` setting on model properties for overriding the default local key on relationships.
- Added a `foreign_key` setting on model properties for overriding the default foreign key on relationships.
- Added a `pivot_tablename` setting on model properties for overriding the default pivot table name on belongs-to-many relationships.
- Added a Collection class to represent a collection of models and provide functionality around managing that collection.
- Implemented eager loading for has-one and has-many relationships.
- The `ACLModelRequester` class now holds the current requester and supports callables for lazy-loading.

## Changed
- Reduce a loadModel call by caching the values after a save.
- Use Relation classes in `relation()` instead of `::find()`.
- Deprecated `ACLModel::setRequester()` and `ACLModel::getRequester()`

## Fixed
- Catch `PDOException` in `getConnection()` and rethrow as `DriverException` database driver.
- The time zone validator was rejecting many valid time zones.

## 0.7 - 2018-01-21
### Added
- Can set global locale instance **used by error instances with `Errors::setGlobalLocale()`.

### Changed
- Relax `icanboogie/inflector` version dependency.

### Fixed
- Fetching non-existent or dynamic properties no longer calls refresh().
- De-duplicate eager loaded relationships set using `with()`.

### Removed
- Removed global error stack support and Infuse error stack service.
- No longer support `db` service with Infuse. Now you must use the `database` service set to connection manager.
- Remove `Model::getApp()` and no longer inject a global Infuse container into models.

## 0.6 - 2017-12-02
### Changed
- Support Symfony 4.
- Require PHP 7.
- Update infuse/libs to v1.

### Removed
- Removed `password` validator.

## 0.5.4 - 2017-11-16
### Changed
- Add more detail to saveOrFail() exception messages.

## 0.5.3 - 2017-08-26
### Added
- Added `password_php` validator that uses PHP's password hashing.
- Property titles are now looked up from `pulsar.properties.*` locale phrases.

### Changed
- Deprecated `password` validator.
- Validator configuration has been moved to `models.validator` namespace.

## 0.5.2 - 2017-08-04
### Fixed
- Validating values with multiple validation rules short-circuits on the first failed rule.
- ACLModel was incorrectly caching permissions.

## 0.5.1 - 2017-07-03
### Added
- Added `valid()` method to model.

### Fixed
- Add join conditions to aggregate queries.
- Prefix columns used in aggregate queries.
- Clear previous validation errors before each save.

## 0.5.0 - 2017-07-02
### Added
- Soft deletes when model has `::$softDelete` property.
- Added `sum()`, `average()`, `max()`, and `min()` methods to queries.

### Changed
- Refactored `ErrorStack` class into `Errors` class.
- Refactored `Validate` class into `Validator` class.
- Deprecated several error methods.
- Improved validation error messaging.
- Renamed `totalRecords` on driver interface to `count()`.
- Model properties no longer casted to string type by default.

### Fixed
- Giving an invalid ID to `find()` no longer triggers a PHP error.
- Ensure model ID is properly type cast.

## 0.4.0 - 2017-06-25
### Added
- Added `set()` method to queries for batch updates.
- Added `delete()` method to queries for batch deletes.
- Added many helper methods to relationships, including `save()`, `create()`, `sync()`, `attach()`, and `detach()`.
- Moved `getTablename()` out of database driver and into model class.
- Added `saveOrFail()` method to models.
- Added support for multiple database connections.
- Added `getConnection()` method to models for specifying the connection that a model should use. 

### Fixed
- Models are always marked as persisted immediately after saving.

## 0.3.0 - 2017-06-18
### Added
- Model queries now have a `count()` method to return the total number of records.
- Added `setRelation()` method to be used with `relation()`.
- Added `with()` for eager loading relationships.

### Changed
- Deprecated `Model::totalRecords()`.
- When the iterator gets a model count it reuses the same query instead of creating a new one.
- The deprecated `relation()` method now performs a lookup on the data layer and can return null values.
- `Model::find()` now uses `queryModels()` on the data driver instead of using `loadModel()`.
- Models can now maintain their own error stack instances.
- Deprecate storing DI container on models.
- Database driver no longer requires a DI container.
- `ErrorStack` no longer requires a DI container or locale instance.

### Removed
- Models no longer have `$this->app` property.

## 0.2.0 - 2017-05-29
### Added
- `DriverException` represents errors that occur in the data layer.
- Added model adapter service for Infuse Framework.
- Added error stack service for Infuse Framework.
- Added `Model::saved()` and `Model::saving()` shortcuts for listening to any write operation.
- Added integer and float property types.
- Added `Model::find()` and `Model::findOrFail()` for loading models from the data layer.
- Added `persisted()` method to model class.
- Added optional mass assignment protection via `Model::$protected` and `Model::$permitted`. 

### Changed
- All model-related exceptions inherit from `ModelException`.
- Support Symfony 3.
- Use version 0.6 of `infuse/libs`.
- Deprecated `relation()` on model class.
- Deprecated `exists()` on model class.
- Deprecated `toArrayHook()` on model class.
- Deprecated `preSetHook()` on model class.
- Deprecated number property type.
- Deprecated `Validate::is()`.
- Imported error stack library from `infuse/libs`.
- Calling `delete()` no longer clears the local values cache.
- Models with auto-timestamps now compute `created_at` and `updated_at` instead of relying on database triggers.
- Use PSR-6 in `Cacheable` trait. 

### Removed
- Removed `toArrayDeprecated()` on model class.
- Removed `load()` on model class.
- Removed deprecated hooks for create and delete operations.

### Fixed
- Throw exception when calling `create()`, `set()`, or `delete()` inappropriately.
- Mass assigned values in `set()` are now available in the `model.updating` event.
- Models returned by `toArray()` will be converted to arrays.

## 0.1.0 - 2015-12-22
### Added
- Initial release! Project imported from `infuse/libs`.