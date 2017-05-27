# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
- `DriverException` represents errors that occur in the data layer.
- Added model adapter service for Infuse Framework.
- Imported error stack and service from Infuse Framework.
- Added `Model::saved()` and `Model::saving()` shortcuts for listening to any write operation.

### Changed
- All model-related exceptions inherit from `ModelException`.
- Support Symfony 3.
- Use version 0.6 of `infuse/libs`.
- Deprecated `relation()` on model class.
- Deprecated `exists()` on model class.
- Deprecated `Validate::is()`.
- Deprecated `toArrayHook()` on model class.
- Deprecated `preSetHook()` on model class.

### Removed
- Removed `toArrayDeprecated()` on model class.
- Removed `load()` on model class.
- Removed deprecated hooks for create and delete operations.

### Fixed
- Throw exception when calling `create()`, `set()`, or `delete()` inappropriately.

## 0.1.0 - 2015-12-22
### Added
- Initial release! Project imported from `infuse/libs`.