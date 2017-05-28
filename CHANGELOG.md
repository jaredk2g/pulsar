# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
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