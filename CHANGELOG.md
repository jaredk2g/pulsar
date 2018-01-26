# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## Unreleased
## Changed
- Reduce a loadModel call by caching the values after a save.

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