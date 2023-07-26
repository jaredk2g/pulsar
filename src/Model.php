<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @see http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

namespace Pulsar;

use ArrayAccess;
use ICanBoogie\Inflector;
use InvalidArgumentException;
use Pulsar\Driver\DriverInterface;
use Pulsar\Event\ModelCreated;
use Pulsar\Event\ModelCreating;
use Pulsar\Event\ModelDeleted;
use Pulsar\Event\ModelDeleting;
use Pulsar\Event\ModelUpdated;
use Pulsar\Event\ModelUpdating;
use Pulsar\Exception\DriverMissingException;
use Pulsar\Exception\MassAssignmentException;
use Pulsar\Exception\ModelException;
use Pulsar\Exception\ModelNotFoundException;
use Pulsar\Relation\AbstractRelation;
use Pulsar\Relation\Relationship;
use Throwable;

/**
 * @mixin Query<static>
 */
abstract class Model implements ArrayAccess
{
    const DEFAULT_ID_NAME = 'id';

    //
    // Model visible variables
    //

    protected array $_values = [];
    private array $_unsaved = [];
    protected bool $_persisted = false;
    protected array $_relationships = [];
    /** @var AbstractRelation[] */
    private array $relationships = [];

    //
    // Base model variables
    //

    private static array $initialized = [];
    private static ?DriverInterface $driver = null;
    private static array $accessors = [];
    private static array $mutators = [];
    private string $tablename;
    private bool $hasId = false;
    private array $idValues = [];
    private bool $loaded = false;
    private Errors $errors;
    private bool $ignoreUnsaved = false;

    /**
     * Creates a new model object.
     *
     * @param array|string|Model|false $id     ordered array of ids or comma-separated id string
     * @param array                    $values optional key-value map to pre-seed model
     */
    public function __construct(array $values = [])
    {
        // initialize the model
        $this->init();

        $ids = [];
        $this->hasId = true;
        foreach (static::getIDProperties() as $name) {
            $id = null;
            if (array_key_exists($name, $values)) {
                $idProperty = static::definition()->get($name);
                $id = Type::cast($idProperty, $values[$name]);
            }

            $ids[$name] = $id;
            $this->hasId = $this->hasId && $id;
        }

        $this->idValues = $ids;

        // load any given values
        if ($this->hasId && count($values) > count($ids)) {
            $this->refreshWith($values);
        } elseif (!$this->hasId) {
            $this->_unsaved = $values;
        } else {
            $this->_values = $this->idValues;
        }
    }

    /**
     * Performs initialization on this model.
     */
    private function init(): void
    {
        // ensure the initialize function is called only once
        $k = static::class;
        if (!isset(self::$initialized[$k])) {
            $this->initialize();
            self::$initialized[$k] = true;
        }
    }

    /**
     * This method is called once per model and is a great
     * place to install event listeners. Any methods on the model that have
     * "autoInitialize" in the name will automatically be called.
     */
    protected function initialize(): void
    {
        // Use reflection to automatically call any method here that has a name
        // that starts with "autoInitialize". This is useful for traits to install listeners.
        $methods = get_class_methods(static::class);
        foreach ($methods as $method) {
            if (0 === strpos($method, 'autoInitialize')) {
                $this->$method();
            }
        }
    }

    /**
     * Sets the driver for all models.
     */
    public static function setDriver(DriverInterface $driver)
    {
        self::$driver = $driver;
    }

    /**
     * Gets the driver for all models.
     *
     * @throws DriverMissingException when a driver has not been set yet
     */
    public static function getDriver(): DriverInterface
    {
        if (!self::$driver) {
            throw new DriverMissingException('A model driver has not been set yet.');
        }

        return self::$driver;
    }

    /**
     * Clears the driver for all models.
     */
    public static function clearDriver(): void
    {
        self::$driver = null;
    }

    /**
     * Gets the name of the model, i.e. User.
     */
    public static function modelName(): string
    {
        // strip namespacing
        $paths = explode('\\', static::class);

        return end($paths);
    }

    /**
     * Gets the model ID.
     */
    public function id(): string|int|false
    {
        if (!$this->hasId) {
            return false;
        }

        if (1 == count($this->idValues)) {
            return reset($this->idValues);
        }

        $result = [];
        foreach (static::definition()->getIds() as $k) {
            $result[] = $this->idValues[$k];
        }

        return implode(',', $result);
    }

    /**
     * Gets a key-value map of the model ID.
     *
     * @return array ID map
     */
    public function ids(): array
    {
        return $this->idValues;
    }

    /**
     * Checks if the model has an identifier present.
     * This does not indicate whether the model has been
     * persisted to the database or loaded from the database.
     */
    public function hasId(): bool
    {
        return $this->hasId;
    }

    //
    // Magic Methods
    //

    /**
     * Converts the model into a string.
     *
     * @return string
     */
    public function __toString()
    {
        $values = array_merge($this->_values, $this->_unsaved, $this->idValues);
        ksort($values);

        return static::class.'('.json_encode($values, JSON_PRETTY_PRINT).')';
    }

    /**
     * Shortcut to a get() call for a given property.
     */
    public function __get(string $name): mixed
    {
        $result = $this->get([$name]);

        return reset($result);
    }

    /**
     * Sets an unsaved value.
     */
    public function __set(string $name, mixed $value): void
    {
        // if changing property, remove relation model
        if (isset($this->_relationships[$name])) {
            unset($this->_relationships[$name]);
        }

        // call any mutators
        $mutator = self::getMutator($name);
        if ($mutator) {
            $this->_unsaved[$name] = $this->$mutator($value);
        } else {
            $this->_unsaved[$name] = $value;
        }

        // set local ID property on belongs_to relationship
        if (static::definition()->has($name)) {
            $property = static::definition()->get($name);
            if (Relationship::BELONGS_TO == $property->relation_type && !$property->persisted) {
                if ($value instanceof self) {
                    $this->_unsaved[$property->local_key] = $value->{$property->foreign_key};
                } elseif (null === $value) {
                    $this->_unsaved[$property->local_key] = null;
                } else {
                    throw new ModelException('The value set on the "'.$name.'" property must be a model or null.');
                }
            }
        }
    }

    /**
     * Checks if an unsaved value or property exists by this name.
     */
    public function __isset(string $name): bool
    {
        // isset() must return true for any value that could be returned by offsetGet
        // because many callers will first check isset() to see if the value is accessible.
        // This method is not supposed to only be valid for unsaved values, or properties
        // that have a value.
        return array_key_exists($name, $this->_unsaved) || static::definition()->has($name);
    }

    /**
     * Unsets an unsaved value.
     */
    public function __unset(string $name): void
    {
        if (array_key_exists($name, $this->_unsaved)) {
            // if changing property, remove relation model
            if (isset($this->_relationships[$name])) {
                unset($this->_relationships[$name]);
            }

            unset($this->_unsaved[$name]);
        }
    }

    //
    // ArrayAccess Interface
    //

    public function offsetExists($offset): bool
    {
        return isset($this->$offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value): void
    {
        $this->$offset = $value;
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    public static function __callStatic($name, $parameters)
    {
        // Any calls to unkown static methods should be deferred to
        // the query. This allows calls like User::where()
        // to replace User::query()->where().
        return call_user_func_array([static::query(), $name], $parameters);
    }

    //
    // Property Definitions
    //

    /**
     * Gets the model definition.
     */
    public static function definition(): Definition
    {
        return DefinitionBuilder::get(static::class);
    }

    /**
     * The buildDefinition() method is called once per model. It's used
     * to generate the model definition. This is a great place to add any
     * dynamic model properties.
     */
    public static function buildDefinition(): Definition
    {
        $properties = static::getProperties();

        // Use reflection to automatically call any method on the model that has a name
        // that starts with "autoDefinition". This is useful for traits to add properties.
        $methods = get_class_methods(static::class);
        foreach ($methods as $method) {
            if (str_starts_with($method, 'autoDefinition')) {
                $properties = array_replace(static::$method(), $properties);
            }
        }

        return DefinitionBuilder::build(static::getIDProperties(), $properties, static::class);
    }

    /**
     * Gets the names of the model ID properties.
     */
    protected static function getIDProperties(): array
    {
        return [self::DEFAULT_ID_NAME];
    }

    /**
     * Gets the model properties.
     *
     * Property definitions expressed as a key-value map with
     * property names as the keys.
     * i.e. ['enabled' => new Property(type: Type::BOOLEAN)].
     *
     * @return Property[]
     */
    protected static function getProperties(): array
    {
        return [];
    }

    /**
     * Gets the mutator method name for a given property name.
     * Looks for methods in the form of `setPropertyValue`.
     * i.e. the mutator for `last_name` would be `setLastNameValue`.
     *
     * @param string $property property
     *
     * @return string|null method name if it exists
     */
    public static function getMutator(string $property): ?string
    {
        $class = static::class;

        $k = $class.':'.$property;
        if (!array_key_exists($k, self::$mutators)) {
            $inflector = Inflector::get();
            $method = 'set'.$inflector->camelize($property).'Value';

            if (!method_exists($class, $method)) {
                $method = null;
            }

            self::$mutators[$k] = $method;
        }

        return self::$mutators[$k];
    }

    /**
     * Gets the accessor method name for a given property name.
     * Looks for methods in the form of `getPropertyValue`.
     * i.e. the accessor for `last_name` would be `getLastNameValue`.
     *
     * @param string $property property
     *
     * @return string|null method name if it exists
     */
    public static function getAccessor(string $property): ?string
    {
        $class = static::class;

        $k = $class.':'.$property;
        if (!array_key_exists($k, self::$accessors)) {
            $inflector = Inflector::get();
            $method = 'get'.$inflector->camelize($property).'Value';

            if (!method_exists($class, $method)) {
                $method = null;
            }

            self::$accessors[$k] = $method;
        }

        return self::$accessors[$k];
    }

    //
    // CRUD Operations
    //

    /**
     * Gets the table name for storing this model.
     */
    public function getTablename(): string
    {
        if (!isset($this->tablename)) {
            $inflector = Inflector::get();

            $this->tablename = $inflector->camelize($inflector->pluralize(static::modelName()));
        }

        return $this->tablename;
    }

    /**
     * Gets the ID of the connection in the connection manager
     * that stores this model.
     */
    public function getConnection(): ?string
    {
        return null;
    }

    protected function usesTransactions(): bool
    {
        return false;
    }

    /**
     * Saves the model.
     *
     * @return bool true when the operation was successful
     */
    public function save(): bool
    {
        if (!$this->hasId) {
            return $this->create();
        }

        return $this->set();
    }

    /**
     * Saves the model. Throws an exception when the operation fails.
     *
     * @throws ModelException when the model cannot be saved
     */
    public function saveOrFail()
    {
        if (!$this->save()) {
            $msg = 'Failed to save '.static::modelName();
            if ($validationErrors = $this->getErrors()->all()) {
                $msg .= ': '.implode(', ', $validationErrors);
            }

            throw new ModelException($msg);
        }
    }

    /**
     * Creates a new model.
     *
     * @param array $data optional key-value properties to set
     *
     * @throws ModelException when called on an existing model
     *
     * @return bool true when the operation was successful
     */
    public function create(array $data = []): bool
    {
        if ($this->hasId) {
            throw new ModelException('Cannot call create() on an existing model');
        }

        // mass assign values passed into create()
        $this->setValues($data);

        // clear any previous errors
        $this->getErrors()->clear();

        // start a DB transaction if needed
        $usesTransactions = $this->usesTransactions();
        if ($usesTransactions) {
            self::$driver->startTransaction($this->getConnection());
        }

        try {
            // dispatch the model.creating event
            if (!EventManager::dispatch($this, new ModelCreating($this), $usesTransactions)) {
                return false;
            }

            $requiredProperties = [];
            foreach (static::definition()->all() as $name => $property) {
                // build a list of the required properties
                if ($property->required) {
                    $requiredProperties[] = $property;
                }

                // add in default values
                if (!array_key_exists($name, $this->_unsaved) && $property->hasDefault) {
                    $this->_unsaved[$name] = $property->default;
                }
            }

            // save any relationships
            if (!$this->saveRelationships($usesTransactions)) {
                return false;
            }

            // validate the values being saved
            $validated = true;
            $insertArray = [];
            $preservedValues = [];
            foreach ($this->_unsaved as $name => $value) {
                // exclude if value does not map to a property
                $property = static::definition()->get($name);
                if (!$property) {
                    continue;
                }

                // check if this property is persisted to the DB
                if (!$property->persisted) {
                    $preservedValues[$name] = $value;
                    continue;
                }

                // cannot insert immutable values
                // (unless using the default value)
                if ($property->isImmutable() && $value !== $property->default) {
                    continue;
                }

                $validated = $validated && Validator::validateProperty($this, $property, $value);
                $insertArray[$name] = $value;
            }

            // check for required fields
            foreach ($requiredProperties as $property) {
                $name = $property->name;
                if (!isset($insertArray[$name]) && !isset($preservedValues[$name])) {
                    $context = [
                        'field' => $name,
                        'field_name' => $property->getTitle($this),
                    ];
                    $this->getErrors()->add('pulsar.validation.required', $context);

                    $validated = false;
                }
            }

            if (!$validated) {
                // when validations fail roll back any database transaction
                if ($usesTransactions) {
                    self::$driver->rollBackTransaction($this->getConnection());
                }

                return false;
            }

            $created = self::$driver->createModel($this, $insertArray);

            if ($created) {
                // determine the model's new ID
                $this->getNewId();

                // store the persisted values to the in-memory cache
                $this->_unsaved = [];
                $hydrateValues = array_replace($this->idValues, $preservedValues);

                // only type-cast the values that were converted to the database format
                foreach ($insertArray as $k => $v) {
                    if ($property = static::definition()->get($k)) {
                        $hydrateValues[$k] = Type::cast($property, $v);
                    } else {
                        $hydrateValues[$k] = $v;
                    }
                }
                $this->refreshWith($hydrateValues);

                // dispatch the model.created event
                if (!EventManager::dispatch($this, new ModelCreated($this), $usesTransactions)) {
                    $this->_persisted = false;

                    return false;
                }
            }
        } catch (Throwable $e) {
            $this->_persisted = false;

            // roll back the transaction, if used
            if ($usesTransactions) {
                self::$driver->rollBackTransaction($this->getConnection());
            }

            // now that the transaction is rolled back we can rethrow
            throw $e;
        }

        // commit the transaction, if used
        if ($usesTransactions) {
            self::$driver->commitTransaction($this->getConnection());
        }

        return $created;
    }

    /**
     * Ignores unsaved values when fetching the next value.
     *
     * @return $this
     */
    public function ignoreUnsaved(): static
    {
        $this->ignoreUnsaved = true;

        return $this;
    }

    /**
     * Fetches property values from the model.
     *
     * This method looks up values in this order:
     * IDs, local cache, unsaved values, storage layer, defaults
     *
     * @param array $properties list of property names to fetch values of
     */
    public function get(array $properties): array
    {
        // check if unsaved values will be returned
        $ignoreUnsaved = $this->ignoreUnsaved;
        $this->ignoreUnsaved = false;

        // Check if the model needs to be loaded from the database. This
        // is used if an ID was supplied for the model but the values have
        // not been hydrated from the database. We only want to load values
        // from the database if there are properties requested that are both
        // persisted to the database AND do not already have a value present.
        $this->loadIfNeeded($properties, $ignoreUnsaved);

        // build a key-value map of the requested properties
        $return = [];
        foreach ($properties as $k) {
            $return[$k] = $this->getValue($k, $ignoreUnsaved);
        }

        return $return;
    }

    /**
     * Loads the model from the database if needed.
     */
    private function loadIfNeeded(array $properties, bool $ignoreUnsaved): void
    {
        if ($this->loaded | !$this->hasId) {
            return;
        }

        foreach ($properties as $k) {
            if (!isset($this->_values[$k]) && ($ignoreUnsaved || !isset($this->_unsaved[$k]))) {
                $property = static::definition()->get($k);
                if ($property && $property->persisted) {
                    $this->refresh();

                    return;
                }
            }
        }
    }

    /**
     * Gets a property value from the model.
     *
     * Values are looked up in this order:
     *  1. unsaved values
     *  2. local values
     *  3. default value
     *  4. null
     */
    private function getValue(string $name, bool $ignoreUnsaved): mixed
    {
        $value = null;
        if (!$ignoreUnsaved && array_key_exists($name, $this->_unsaved)) {
            $value = $this->_unsaved[$name];
        } elseif (array_key_exists($name, $this->_values)) {
            $value = $this->_values[$name];
        } elseif ($property = static::definition()->get($name)) {
            if ($property->relation_type && !$property->persisted) {
                $relationship = $this->getRelationship($property);
                $value = $this->_values[$name] = $relationship->getResults();
            } else {
                $value = $this->_values[$name] = $property->default;
            }
        }

        // call any accessors
        if ($accessor = self::getAccessor($name)) {
            $value = $this->$accessor($value);
        }

        return $value;
    }

    /**
     * Populates a newly created model with its ID.
     */
    private function getNewId(): void
    {
        $ids = [];
        $namedIds = [];
        $definition = static::definition();
        foreach ($definition->getIds() as $k) {
            // attempt use the supplied value if the ID property is mutable
            $property = $definition->get($k);
            if (!$property->isImmutable() && isset($this->_unsaved[$k])) {
                $id = $this->_unsaved[$k];
            } else {
                // type-cast the value because it came from the database
                $id = Type::cast($property, self::$driver->getCreatedId($this, $k));
            }

            $ids[] = $id;
            $namedIds[$k] = $id;
        }

        $this->hasId = true;
        $this->idValues = $namedIds;
        $this->_values = array_replace($this->_values, $namedIds);
    }

    protected function getMassAssignmentWhitelist(): ?array
    {
        return null;
    }

    protected function getMassAssignmentBlacklist(): ?array
    {
        return null;
    }

    /**
     * Sets a collection values on the model from an untrusted input.
     *
     * @throws MassAssignmentException when assigning a value that is protected or not whitelisted
     *
     * @return $this
     */
    public function setValues(array $values): static
    {
        if ($permitted = $this->getMassAssignmentWhitelist()) {
            // use a mass assignment whitelist
            foreach ($values as $k => $value) {
                // check for mass assignment violations
                if (!in_array($k, $permitted)) {
                    throw new MassAssignmentException("Mass assignment of $k on ".static::modelName().' is not allowed');
                }

                $this->$k = $value;
            }
        } elseif ($protected = $this->getMassAssignmentBlacklist()) {
            // use a mass assignment blacklist
            foreach ($values as $k => $value) {
                // check for mass assignment violations
                if (in_array($k, $protected)) {
                    throw new MassAssignmentException("Mass assignment of $k on ".static::modelName().' is not allowed');
                }

                $this->$k = $value;
            }
        } else {
            // no mass assignment protection enabled
            foreach ($values as $k => $value) {
                $this->$k = $value;
            }
        }

        return $this;
    }

    /**
     * Converts the model to an array.
     */
    public function toArray(): array
    {
        // build the list of properties to return
        $properties = [];
        foreach (static::definition()->all() as $property) {
            if ($property->in_array) {
                $properties[] = $property->name;
            }
        }

        // get the values for the properties
        $result = $this->get($properties);

        foreach ($result as $k => &$value) {
            // convert arrays of models to arrays
            if (is_array($value)) {
                foreach ($value as &$subValue) {
                    if ($subValue instanceof Model) {
                        $subValue = $subValue->toArray();
                    }
                }
            }

            // convert any models to arrays
            if ($value instanceof self) {
                $value = $value->toArray();
            }
        }

        return $result;
    }

    /**
     * Checks if the unsaved value for a property is present and
     * is different from the original value.
     *
     * @property string|null $name
     * @property bool        $hasChanged when true, checks if the unsaved value is different from the saved value
     */
    public function dirty(?string $name = null, bool $hasChanged = false): bool
    {
        if (!$name) {
            if ($hasChanged) {
                throw new \RuntimeException('Checking if all properties have changed is not supported');
            }

            return count($this->_unsaved) > 0;
        }

        if (!array_key_exists($name, $this->_unsaved)) {
            return false;
        }

        if (!$hasChanged) {
            return true;
        }

        return $this->$name !== $this->ignoreUnsaved()->$name;
    }

    /**
     * Updates the model.
     *
     * @param array $data optional key-value properties to set
     *
     * @throws ModelException when not called on an existing model
     *
     * @return bool true when the operation was successful
     */
    public function set(array $data = []): bool
    {
        if (!$this->hasId) {
            throw new ModelException('Can only call set() on an existing model');
        }

        // mass assign values passed into set()
        $this->setValues($data);

        // clear any previous errors
        $this->getErrors()->clear();

        // not updating anything?
        if (0 == count($this->_unsaved)) {
            return true;
        }

        // start a DB transaction if needed
        $usesTransactions = $this->usesTransactions();
        if ($usesTransactions) {
            self::$driver->startTransaction($this->getConnection());
        }

        try {
            // dispatch the model.updating event
            if (!EventManager::dispatch($this, new ModelUpdating($this), $usesTransactions)) {
                return false;
            }

            // save any relationships
            if (!$this->saveRelationships($usesTransactions)) {
                return false;
            }

            // validate the values being saved
            $validated = true;
            $updateArray = [];
            $preservedValues = [];
            foreach ($this->_unsaved as $name => $value) {
                // exclude if value does not map to a property
                if (!static::definition()->has($name)) {
                    continue;
                }

                $property = static::definition()->get($name);

                // check if this property is persisted to the DB
                if (!$property->persisted) {
                    $preservedValues[$name] = $value;
                    continue;
                }

                // can only modify mutable properties
                if (!$property->isMutable()) {
                    continue;
                }

                $validated = $validated && Validator::validateProperty($this, $property, $value);
                $updateArray[$name] = $value;
            }

            if (!$validated) {
                // when validations fail roll back any database transaction
                if ($usesTransactions) {
                    self::$driver->rollBackTransaction($this->getConnection());
                }

                return false;
            }

            $updated = self::$driver->updateModel($this, $updateArray);

            if ($updated) {
                // store the persisted values to the in-memory cache
                $this->_unsaved = [];
                $hydrateValues = array_replace($this->_values, $preservedValues);

                // only type-cast the values that were converted to the database format
                foreach ($updateArray as $k => $v) {
                    if ($property = static::definition()->get($k)) {
                        $hydrateValues[$k] = Type::cast($property, $v);
                    } else {
                        $hydrateValues[$k] = $v;
                    }
                }
                $this->refreshWith($hydrateValues);

                // dispatch the model.updated event
                if (!EventManager::dispatch($this, new ModelUpdated($this), $usesTransactions)) {
                    return false;
                }
            }
        } catch (Throwable $e) {
            // roll back the transaction, if used
            if ($usesTransactions) {
                self::$driver->rollBackTransaction($this->getConnection());
            }

            // now that the transaction is rolled back we can rethrow
            throw $e;
        }

        // commit the transaction, if used
        if ($usesTransactions) {
            self::$driver->commitTransaction($this->getConnection());
        }

        return $updated;
    }

    /**
     * Delete the model.
     *
     * @throws ModelException when not called on an existing model
     *
     * @return bool true when the operation was successful
     */
    public function delete(): bool
    {
        if (!$this->hasId) {
            throw new ModelException('Can only call delete() on an existing model');
        }

        // clear any previous errors
        $this->getErrors()->clear();

        // start a DB transaction if needed
        $usesTransactions = $this->usesTransactions();
        if ($usesTransactions) {
            self::$driver->startTransaction($this->getConnection());
        }

        try {
            // dispatch the model.deleting event
            if (!EventManager::dispatch($this, new ModelDeleting($this), $usesTransactions)) {
                return false;
            }

            // perform the delete operation in the data store
            $deleted = $this->performDelete();

            if ($deleted) {
                // dispatch the model.deleted event
                if (!EventManager::dispatch($this, new ModelDeleted($this), $usesTransactions)) {
                    $this->_persisted = true;

                    return false;
                }
            }
        } catch (Throwable $e) {
            // roll back the transaction, if used
            if ($usesTransactions) {
                self::$driver->rollBackTransaction($this->getConnection());
            }

            // now that the transaction is rolled back we can rethrow
            throw $e;
        }

        // commit the transaction, if used
        if ($usesTransactions) {
            self::$driver->commitTransaction($this->getConnection());
        }

        return $deleted;
    }

    /**
     * Delete the model.
     *
     * @throws ModelException when the model cannot be deleted
     */
    public function deleteOrFail(): void
    {
        if (!$this->delete()) {
            $msg = 'Failed to delete '.static::modelName();
            if ($validationErrors = $this->getErrors()->all()) {
                $msg .= ': '.implode(', ', $validationErrors);
            }

            throw new ModelException($msg);
        }
    }

    /**
     * Performs the delete operation against the database driver.
     * This is a separate protected method to allow traits to override
     * the behavior (i.e. soft deletes).
     */
    protected function performDelete(): bool
    {
        $deleted = self::$driver->deleteModel($this);
        if ($deleted) {
            $this->_persisted = false;
        }

        return $deleted;
    }

    /**
     * Checks if the model has been deleted.
     */
    public function isDeleted(): bool
    {
        return !$this->_persisted;
    }

    //
    // Queries
    //

    /**
     * Generates a new query instance.
     *
     * @return Query<static>
     */
    public static function query(): Query
    {
        // Create a new model instance for the query to ensure
        // that the model's initialize() method gets called.
        // Otherwise, the property definitions will be incomplete.
        return new Query(new static());
    }

    /**
     * Finds a single instance of a model given it's ID.
     */
    public static function find(mixed $id): ?static
    {
        $ids = [];
        $id = (array) $id;
        $idProperties = static::definition()->getIds();
        foreach ($idProperties as $j => $k) {
            if (isset($id[$j])) {
                $ids[$k] = $id[$j];
            }
        }

        // malformed ID
        if (count($ids) < count($idProperties)) {
            return null;
        }

        return static::query()->where($ids)->oneOrNull();
    }

    /**
     * Finds a single instance of a model given it's ID or throws an exception.
     *
     * @throws ModelNotFoundException when a model could not be found
     */
    public static function findOrFail(mixed $id): static
    {
        $model = static::find($id);
        if (!$model) {
            throw new ModelNotFoundException('Could not find the requested '.static::modelName());
        }

        return $model;
    }

    /**
     * Tells if this model instance has been persisted to the data layer.
     *
     * NOTE: this does not actually perform a check with the data layer
     */
    public function persisted(): bool
    {
        return $this->_persisted;
    }

    /**
     * Loads the model from the storage layer.
     *
     * @return $this
     */
    public function refresh(): static
    {
        if (!$this->hasId) {
            return $this;
        }

        $values = self::$driver->loadModel($this);

        if (!is_array($values)) {
            return $this;
        }

        // clear any relations
        $this->_relationships = [];

        // type-cast the values that come from the database
        foreach ($values as $k => &$v) {
            if ($property = static::definition()->get($k)) {
                $v = Type::cast($property, $v);
            }
        }

        return $this->refreshWith($values);
    }

    /**
     * Loads values into the model.
     *
     * @return $this
     */
    public function refreshWith(array $values): self
    {
        $this->loaded = true;
        $this->_persisted = true;
        $this->_values = $values;

        return $this;
    }

    /**
     * Clears the cache for this model.
     *
     * @return $this
     */
    public function clearCache(): static
    {
        $this->loaded = false;
        $this->_unsaved = [];
        $this->_values = [];
        $this->_relationships = [];

        return $this;
    }

    //
    // Relationships
    //

    /**
     * Gets the relationship manager for a property.
     *
     * @throws InvalidArgumentException when the relationship manager cannot be created
     */
    private function getRelationship(Property $property): AbstractRelation
    {
        $name = $property->name;
        if (!isset($this->relationships[$name])) {
            $this->relationships[$name] = Relationship::make($this, $property);
        }

        return $this->relationships[$name];
    }

    /**
     * Saves any unsaved models attached through a relationship. This will only
     * save attached models that have not been saved yet.
     */
    private function saveRelationships(bool $usesTransactions): bool
    {
        try {
            foreach ($this->_unsaved as $k => $value) {
                if ($value instanceof self && !$value->persisted()) {
                    $property = static::definition()->get($k);
                    if ($property && !$property->persisted) {
                        $value->saveOrFail();
                        // set the model again to update any ID properties
                        $this->$k = $value;
                    }
                } elseif (is_array($value)) {
                    foreach ($value as $subValue) {
                        if ($subValue instanceof self && !$subValue->persisted()) {
                            $property = static::definition()->get($k);
                            if ($property && !$property->persisted) {
                                $subValue->saveOrFail();
                            }
                        }
                    }
                }
            }
        } catch (ModelException $e) {
            $this->getErrors()->add($e->getMessage());

            if ($usesTransactions) {
                self::$driver->rollBackTransaction($this->getConnection());
            }

            return false;
        }

        return true;
    }

    /**
     * This hydrates an individual property in the model. It can be a
     * scalar value or relationship.
     *
     * @internal
     */
    public function hydrateValue(string $name, $value): void
    {
        // type-cast the value because it came from the database
        if ($property = static::definition()->get($name)) {
            $this->_values[$name] = Type::cast($property, $value);
        } else {
            $this->_values[$name] = $value;
        }
    }

    /**
     * @deprecated
     *
     * Gets the model(s) for a relationship
     *
     * @throws InvalidArgumentException when the relationship manager cannot be created
     */
    public function relation(string $k): Model|array|null
    {
        if (!array_key_exists($k, $this->_relationships)) {
            $relation = Relationship::make($this, static::definition()->get($k));
            $this->_relationships[$k] = $relation->getResults();
        }

        return $this->_relationships[$k];
    }

    /**
     * @deprecated
     *
     * Sets the model for a one-to-one relationship (has-one or belongs-to)
     *
     * @return $this
     */
    public function setRelation(string $k, Model $model): static
    {
        $this->$k = $model->id();
        $this->_relationships[$k] = $model;

        return $this;
    }

    /**
     * @deprecated
     *
     * Sets the model for a one-to-many relationship
     *
     * @return $this
     */
    public function setRelationCollection(string $k, iterable $models): static
    {
        $this->_relationships[$k] = $models;

        return $this;
    }

    /**
     * @deprecated
     *
     * Sets the model for a one-to-one relationship (has-one or belongs-to) as null
     *
     * @return $this
     */
    public function clearRelation(string $k): static
    {
        $this->$k = null;
        $this->_relationships[$k] = null;

        return $this;
    }

    //
    // Events
    //

    /**
     * Adds a listener to the model.creating and model.updating events.
     */
    public static function saving(callable $listener, int $priority = 0): void
    {
        EventManager::listen(static::class, ModelCreating::NAME, $listener, $priority);
        EventManager::listen(static::class, ModelUpdating::NAME, $listener, $priority);
    }

    /**
     * Adds a listener to the model.created and model.updated events.
     */
    public static function saved(callable $listener, int $priority = 0): void
    {
        EventManager::listen(static::class, ModelCreated::NAME, $listener, $priority);
        EventManager::listen(static::class, ModelUpdated::NAME, $listener, $priority);
    }

    /**
     * Adds a listener to the model.creating, model.updating, and model.deleting events.
     */
    public static function beforePersist(callable $listener, int $priority = 0): void
    {
        EventManager::listen(static::class, ModelCreating::NAME, $listener, $priority);
        EventManager::listen(static::class, ModelUpdating::NAME, $listener, $priority);
        EventManager::listen(static::class, ModelDeleting::NAME, $listener, $priority);
    }

    /**
     * Adds a listener to the model.created, model.updated, and model.deleted events.
     */
    public static function afterPersist(callable $listener, int $priority = 0): void
    {
        EventManager::listen(static::class, ModelCreated::NAME, $listener, $priority);
        EventManager::listen(static::class, ModelUpdated::NAME, $listener, $priority);
        EventManager::listen(static::class, ModelDeleted::NAME, $listener, $priority);
    }

    /**
     * Adds a listener to the model.creating event.
     */
    public static function creating(callable $listener, int $priority = 0): void
    {
        EventManager::listen(static::class, ModelCreating::NAME, $listener, $priority);
    }

    /**
     * Adds a listener to the model.created event.
     */
    public static function created(callable $listener, int $priority = 0): void
    {
        EventManager::listen(static::class, ModelCreated::NAME, $listener, $priority);
    }

    /**
     * Adds a listener to the model.updating event.
     */
    public static function updating(callable $listener, int $priority = 0): void
    {
        EventManager::listen(static::class, ModelUpdating::NAME, $listener, $priority);
    }

    /**
     * Adds a listener to the model.updated event.
     */
    public static function updated(callable $listener, int $priority = 0): void
    {
        EventManager::listen(static::class, ModelUpdated::NAME, $listener, $priority);
    }

    /**
     * Adds a listener to the model.deleting event.
     */
    public static function deleting(callable $listener, int $priority = 0): void
    {
        EventManager::listen(static::class, ModelDeleting::NAME, $listener, $priority);
    }

    /**
     * Adds a listener to the model.deleted event.
     */
    public static function deleted(callable $listener, int $priority = 0): void
    {
        EventManager::listen(static::class, ModelDeleted::NAME, $listener, $priority);
    }

    //
    // Validation
    //

    /**
     * Gets the error stack for this model.
     */
    public function getErrors(): Errors
    {
        if (!isset($this->errors)) {
            $this->errors = new Errors();
        }

        return $this->errors;
    }

    /**
     * Checks if the model in its current state is valid.
     */
    public function valid(): bool
    {
        // clear any previous errors
        $this->getErrors()->clear();

        // run the validator against the unsaved model values
        $validated = true;
        foreach ($this->_unsaved as $k => &$v) {
            $property = static::definition()->get($k);
            $validated = Validator::validateProperty($this, $property, $v) && $validated;
        }

        return $validated;
    }
}
