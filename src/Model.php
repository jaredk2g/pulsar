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
use BadMethodCallException;
use ICanBoogie\Inflector;
use InvalidArgumentException;
use Pulsar\Driver\DriverInterface;
use Pulsar\Exception\DriverMissingException;
use Pulsar\Exception\MassAssignmentException;
use Pulsar\Exception\ModelException;
use Pulsar\Exception\ModelNotFoundException;
use Pulsar\Relation\AbstractRelation;
use Pulsar\Relation\Relationship;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Model.
 *
 * @method Query             where($where, $value = null, $condition = null)
 * @method Query             limit($limit)
 * @method Query             start($start)
 * @method Query             sort($sort)
 * @method Query             join($model, $column, $foreignKey)
 * @method Query             with($k)
 * @method Iterator          all()
 * @method array|static|null first($limit = 1)
 * @method int               count()
 * @method number            sum($property)
 * @method number            average($property)
 * @method number            max($property)
 * @method number            min($property)
 */
abstract class Model implements ArrayAccess
{
    const DEFAULT_ID_NAME = 'id';

    /////////////////////////////
    // Model visible variables
    /////////////////////////////

    /**
     * List of model ID property names.
     *
     * @var array
     */
    protected static $ids = [self::DEFAULT_ID_NAME];

    /**
     * Property definitions expressed as a key-value map with
     * property names as the keys.
     * i.e. ['enabled' => ['type' => Type::BOOLEAN]].
     *
     * @var array
     */
    protected static $properties = [];

    /**
     * @var array
     */
    protected $_values = [];

    /**
     * @var array
     */
    protected $_unsaved = [];

    /**
     * @var bool
     */
    protected $_persisted = false;

    /**
     * @var array
     */
    protected $_relationships = [];

    /**
     * @var AbstractRelation[]
     */
    private $relationships = [];

    /////////////////////////////
    // Base model variables
    /////////////////////////////

    /**
     * @var array
     */
    private static $initialized = [];

    /**
     * @var DriverInterface
     */
    private static $driver;

    /**
     * @var array
     */
    private static $accessors = [];

    /**
     * @var array
     */
    private static $mutators = [];

    /**
     * @var array
     */
    private static $dispatchers = [];

    /**
     * @var string
     */
    private $tablename;

    /**
     * @var bool
     */
    private $hasId;

    /**
     * @var array
     */
    private $idValues;

    /**
     * @var bool
     */
    private $loaded = false;

    /**
     * @var Errors
     */
    private $errors;

    /**
     * @var bool
     */
    private $ignoreUnsaved;

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
        foreach (static::$ids as $name) {
            $id = false;
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
        }
    }

    /**
     * Performs initialization on this model.
     */
    private function init()
    {
        // ensure the initialize function is called only once
        $k = static::class;
        if (!isset(self::$initialized[$k])) {
            $this->initialize();
            self::$initialized[$k] = true;
        }
    }

    /**
     * The initialize() method is called once per model. This is a great
     * place to install event listeners.
     */
    protected function initialize()
    {
        if (property_exists(static::class, 'autoTimestamps')) {
            self::creating(function (ModelEvent $event) {
                $model = $event->getModel();
                $model->created_at = time();
                $model->updated_at = time();
            });

            self::updating(function (ModelEvent $event) {
                $event->getModel()->updated_at = time();
            });
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
    public static function clearDriver()
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
     *
     * @return string|number|false ID
     */
    public function id()
    {
        if (!$this->hasId) {
            return false;
        }

        if (1 == count($this->idValues)) {
            return reset($this->idValues);
        }

        $result = [];
        foreach (static::$ids as $k) {
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

    /////////////////////////////
    // Magic Methods
    /////////////////////////////

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
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        $result = $this->get([$name]);

        return reset($result);
    }

    /**
     * Sets an unsaved value.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
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
        $property = static::definition()->get($name);
        if ($property && Relationship::BELONGS_TO == $property->getRelationshipType() && !$property->isPersisted()) {
            if ($value instanceof self) {
                $this->_unsaved[$property->getLocalKey()] = $value->{$property->getForeignKey()};
            } elseif (null === $value) {
                $this->_unsaved[$property->getLocalKey()] = null;
            } else {
                throw new ModelException('The value set on the "'.$name.'" property must be a model or null.');
            }
        }
    }

    /**
     * Checks if an unsaved value or property exists by this name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        // isset() must return true for any value that could be returned by offsetGet
        // because many callers will first check isset() to see if the value is accessible.
        // This method is not supposed to only be valid for unsaved values, or properties
        // that have a value.
        return array_key_exists($name, $this->_unsaved) || static::definition()->has($name);
    }

    /**
     * Unsets an unsaved value.
     *
     * @param string $name
     */
    public function __unset($name)
    {
        if (array_key_exists($name, $this->_unsaved)) {
            // if changing property, remove relation model
            if (isset($this->_relationships[$name])) {
                unset($this->_relationships[$name]);
            }

            unset($this->_unsaved[$name]);
        }
    }

    /////////////////////////////
    // ArrayAccess Interface
    /////////////////////////////

    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

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

    /////////////////////////////
    // Property Definitions
    /////////////////////////////

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
        $autoTimestamps = property_exists(static::class, 'autoTimestamps');
        $softDelete = property_exists(static::class, 'softDelete');

        return DefinitionBuilder::build(static::$properties, static::class, $autoTimestamps, $softDelete);
    }

    /**
     * Gets the names of the model ID properties.
     */
    public static function getIDProperties(): array
    {
        return static::$ids;
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

    /**
     * @deprecated
     *
     * Gets the definition of all model properties
     */
    public static function getProperties(): Definition
    {
        return DefinitionBuilder::get(static::class);
    }

    /**
     * @deprecated
     *
     * Gets the definition of a specific property
     *
     * @param string $property property to lookup
     */
    public static function getProperty(string $property): ?Property
    {
        return static::definition()->get($property);
    }

    /**
     * @deprecated
     *
     * Checks if the model has a property
     *
     * @param string $property property
     *
     * @return bool has property
     */
    public static function hasProperty(string $property): bool
    {
        return static::definition()->has($property);
    }

    /////////////////////////////
    // CRUD Operations
    /////////////////////////////

    /**
     * Gets the table name for storing this model.
     */
    public function getTablename(): string
    {
        if (!$this->tablename) {
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
     * @return bool true when the operation was successful
     *
     * @throws BadMethodCallException when called on an existing model
     */
    public function create(array $data = []): bool
    {
        if ($this->hasId) {
            throw new BadMethodCallException('Cannot call create() on an existing model');
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

        // dispatch the model.creating event
        if (!$this->performDispatch(ModelEvent::CREATING, $usesTransactions)) {
            return false;
        }

        $requiredProperties = [];
        foreach (static::definition()->all() as $name => $property) {
            // build a list of the required properties
            if ($property->isRequired()) {
                $requiredProperties[] = $property;
            }

            // add in default values
            if (!array_key_exists($name, $this->_unsaved) && $property->hasDefault()) {
                $this->_unsaved[$name] = $property->getDefault();
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
            if (!$property->isPersisted()) {
                $preservedValues[$name] = $value;
                continue;
            }

            // cannot insert immutable values
            // (unless using the default value)
            if ($property->isImmutable() && $value !== $property->getDefault()) {
                continue;
            }

            $validated = $validated && Validator::validateProperty($this, $property, $value);
            $insertArray[$name] = $value;
        }

        // check for required fields
        foreach ($requiredProperties as $property) {
            $name = $property->getName();
            if (!isset($insertArray[$name]) && !isset($preservedValues[$name])) {
                $params = [
                    'field' => $name,
                    'field_name' => $property->getTitle($this),
                ];
                $this->getErrors()->add('pulsar.validation.required', $params);

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
            $this->refreshWith(array_replace($this->idValues, $preservedValues, $insertArray));

            // dispatch the model.created event
            if (!$this->performDispatch(ModelEvent::CREATED, $usesTransactions)) {
                return false;
            }
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
    public function ignoreUnsaved()
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
        // load the values from the IDs and local model cache
        $values = array_replace($this->ids(), $this->_values);

        // unless specified, use any unsaved values
        $ignoreUnsaved = $this->ignoreUnsaved;
        $this->ignoreUnsaved = false;

        if (!$ignoreUnsaved) {
            $values = array_replace($values, $this->_unsaved);
        }

        // see if there are any model properties that do not exist.
        // when true then this means the model needs to be hydrated
        // NOTE: only looking at model properties and excluding dynamic/non-existent properties
        $modelProperties = static::definition()->propertyNames();
        $numMissing = count(array_intersect($modelProperties, array_diff($properties, array_keys($values))));

        if ($numMissing > 0 && !$this->loaded) {
            // load the model from the storage layer, if needed
            $this->refresh();

            $values = array_replace($values, $this->_values);

            if (!$ignoreUnsaved) {
                $values = array_replace($values, $this->_unsaved);
            }
        }

        // build a key-value map of the requested properties
        $return = [];
        foreach ($properties as $k) {
            $return[$k] = $this->getValue($k, $values);
        }

        return $return;
    }

    /**
     * Gets a property value from the model.
     *
     * Values are looked up in this order:
     *  1. unsaved values
     *  2. local values
     *  3. default value
     *  4. null
     *
     * @return mixed
     */
    private function getValue(string $name, array $values)
    {
        $value = null;

        if (array_key_exists($name, $values)) {
            $value = $values[$name];
        } elseif ($property = static::definition()->get($name)) {
            if ($property->getRelationshipType() && !$property->isPersisted()) {
                $relationship = $this->getRelationship($property);
                $value = $this->_values[$name] = $relationship->getResults();
            } else {
                $value = $this->_values[$name] = $property->getDefault();
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
    private function getNewId()
    {
        $ids = [];
        $namedIds = [];
        foreach (static::$ids as $k) {
            // attempt use the supplied value if the ID property is mutable
            $property = static::definition()->get($k);
            if (!$property->isImmutable() && isset($this->_unsaved[$k])) {
                $id = $this->_unsaved[$k];
            } else {
                $id = Type::cast($property, self::$driver->getCreatedId($this, $k));
            }

            $ids[] = $id;
            $namedIds[$k] = $id;
        }

        $this->hasId = true;
        $this->idValues = $namedIds;
    }

    /**
     * Sets a collection values on the model from an untrusted input.
     *
     * @param array $values
     *
     * @throws MassAssignmentException when assigning a value that is protected or not whitelisted
     *
     * @return $this
     */
    public function setValues($values)
    {
        // check if the model has a mass assignment whitelist
        $permitted = (property_exists($this, 'permitted')) ? static::$permitted : false;

        // if no whitelist, then check for a blacklist
        $protected = (!is_array($permitted) && property_exists($this, 'protected')) ? static::$protected : false;

        foreach ($values as $k => $value) {
            // check for mass assignment violations
            if (($permitted && !in_array($k, $permitted)) ||
                ($protected && in_array($k, $protected))) {
                throw new MassAssignmentException("Mass assignment of $k on ".static::modelName().' is not allowed');
            }

            $this->$k = $value;
        }

        return $this;
    }

    /**
     * Converts the model to an array.
     */
    public function toArray(): array
    {
        // build the list of properties to retrieve
        $properties = static::definition()->propertyNames();

        // remove any relationships
        $relationships = [];
        foreach (static::definition()->all() as $property) {
            if ($property->getRelationshipType() && !$property->isPersisted()) {
                $relationships[] = $property->getName();
            }
        }
        $properties = array_diff($properties, $relationships);

        // remove any hidden properties
        $hide = (property_exists($this, 'hidden')) ? static::$hidden : [];
        $properties = array_diff($properties, $hide);

        // add any appended properties
        $append = (property_exists($this, 'appended')) ? static::$appended : [];
        $properties = array_merge($properties, $append);

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
     * @property string $name
     * @property bool   $hasChanged when true, checks if the unsaved value is different from the saved value
     */
    public function dirty(string $name, bool $hasChanged = false): bool
    {
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
     * @return bool true when the operation was successful
     *
     * @throws BadMethodCallException when not called on an existing model
     */
    public function set(array $data = []): bool
    {
        if (!$this->hasId) {
            throw new BadMethodCallException('Can only call set() on an existing model');
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

        // dispatch the model.updating event
        if (!$this->performDispatch(ModelEvent::UPDATING, $usesTransactions)) {
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
            if (!$property->isPersisted()) {
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
            $this->refreshWith(array_replace($this->_values, $preservedValues, $updateArray));

            // dispatch the model.updated event
            if (!$this->performDispatch(ModelEvent::UPDATED, $usesTransactions)) {
                return false;
            }
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
     * @return bool true when the operation was successful
     */
    public function delete(): bool
    {
        if (!$this->hasId) {
            throw new BadMethodCallException('Can only call delete() on an existing model');
        }

        // clear any previous errors
        $this->getErrors()->clear();

        // start a DB transaction if needed
        $usesTransactions = $this->usesTransactions();
        if ($usesTransactions) {
            self::$driver->startTransaction($this->getConnection());
        }

        // dispatch the model.deleting event
        if (!$this->performDispatch(ModelEvent::DELETING, $usesTransactions)) {
            return false;
        }

        // perform a hard (default) or soft delete
        $hardDelete = true;
        if (property_exists($this, 'softDelete')) {
            $t = time();
            $this->deleted_at = $t;
            $t = Validator::validateProperty($this, static::definition()->get('deleted_at'), $t);
            $deleted = self::$driver->updateModel($this, ['deleted_at' => $t]);
            $hardDelete = false;
        } else {
            $deleted = self::$driver->deleteModel($this);
        }

        if ($deleted) {
            // dispatch the model.deleted event
            if (!$this->performDispatch(ModelEvent::DELETED, $usesTransactions)) {
                return false;
            }

            if ($hardDelete) {
                $this->_persisted = false;
            }
        }

        // commit the transaction, if used
        if ($usesTransactions) {
            self::$driver->commitTransaction($this->getConnection());
        }

        return $deleted;
    }

    /**
     * Restores a soft-deleted model.
     */
    public function restore(): bool
    {
        if (!property_exists($this, 'softDelete') || !$this->deleted_at) {
            throw new BadMethodCallException('Can only call restore() on a soft-deleted model');
        }

        // start a DB transaction if needed
        $usesTransactions = $this->usesTransactions();
        if ($usesTransactions) {
            self::$driver->startTransaction($this->getConnection());
        }

        // dispatch the model.updating event
        if (!$this->performDispatch(ModelEvent::UPDATING, $usesTransactions)) {
            return false;
        }

        $this->deleted_at = null;
        $restored = self::$driver->updateModel($this, ['deleted_at' => null]);

        if ($restored) {
            // dispatch the model.updated event
            if (!$this->performDispatch(ModelEvent::UPDATED, $usesTransactions)) {
                return false;
            }
        }

        // commit the transaction, if used
        if ($usesTransactions) {
            self::$driver->commitTransaction($this->getConnection());
        }

        return $restored;
    }

    /**
     * Checks if the model has been deleted.
     */
    public function isDeleted(): bool
    {
        if (property_exists($this, 'softDelete') && $this->deleted_at) {
            return true;
        }

        return !$this->_persisted;
    }

    /////////////////////////////
    // Queries
    /////////////////////////////

    /**
     * Generates a new query instance.
     */
    public static function query(): Query
    {
        // Create a new model instance for the query to ensure
        // that the model's initialize() method gets called.
        // Otherwise, the property definitions will be incomplete.
        $model = new static();
        $query = new Query($model);

        // scope soft-deleted models to only include non-deleted models
        if (property_exists($model, 'softDelete')) {
            $query->where('deleted_at IS NOT NULL');
        }

        return $query;
    }

    /**
     * Generates a new query instance that includes soft-deleted models.
     */
    public static function withDeleted(): Query
    {
        // Create a new model instance for the query to ensure
        // that the model's initialize() method gets called.
        // Otherwise, the property definitions will be incomplete.
        $model = new static();

        return new Query($model);
    }

    /**
     * Finds a single instance of a model given it's ID.
     *
     * @param mixed $id
     *
     * @return static|null
     */
    public static function find($id): ?self
    {
        $ids = [];
        $id = (array) $id;
        foreach (static::$ids as $j => $k) {
            if (isset($id[$j])) {
                $ids[$k] = $id[$j];
            }
        }

        // malformed ID
        if (count($ids) < count(static::$ids)) {
            return null;
        }

        return static::query()->where($ids)->first();
    }

    /**
     * Finds a single instance of a model given it's ID or throws an exception.
     *
     * @param mixed $id
     *
     * @return static
     *
     * @throws ModelNotFoundException when a model could not be found
     */
    public static function findOrFail($id): self
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
    public function refresh()
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

        return $this->refreshWith($values);
    }

    /**
     * Loads values into the model.
     *
     * @param array $values values
     *
     * @return $this
     */
    public function refreshWith(array $values)
    {
        // type cast the values
        foreach ($values as $k => &$value) {
            if ($property = static::definition()->get($k)) {
                $value = Type::cast($property, $value);
            }
        }

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
    public function clearCache()
    {
        $this->loaded = false;
        $this->_unsaved = [];
        $this->_values = [];
        $this->_relationships = [];

        return $this;
    }

    /////////////////////////////
    // Relationships
    /////////////////////////////

    /**
     * Gets the relationship manager for a property.
     *
     * @throws InvalidArgumentException when the relationship manager cannot be created
     */
    private function getRelationship(Property $property): AbstractRelation
    {
        $name = $property->getName();
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
                if ($value instanceof self) {
                    if (!$value->persisted()) {
                        $value->saveOrFail();
                        // set the model again to update any ID properties
                        $this->$k = $value;
                    }
                } elseif (is_array($value)) {
                    foreach ($value as $subValue) {
                        if ($subValue instanceof self) {
                            $value->saveOrFail();
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
     *
     * @param $value
     */
    public function hydrateValue(string $name, $value): void
    {
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
     * @param string $k property
     *
     * @throws InvalidArgumentException when the relationship manager cannot be created
     *
     * @return Model|array|null
     */
    public function relation(string $k)
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
    public function setRelation(string $k, Model $model)
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
    public function setRelationCollection(string $k, iterable $models)
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
    public function clearRelation(string $k)
    {
        $this->$k = null;
        $this->_relationships[$k] = null;

        return $this;
    }

    /////////////////////////////
    // Events
    /////////////////////////////

    /**
     * Gets the event dispatcher.
     */
    public static function getDispatcher($ignoreCache = false): EventDispatcher
    {
        $class = static::class;
        if ($ignoreCache || !isset(self::$dispatchers[$class])) {
            self::$dispatchers[$class] = new EventDispatcher();
        }

        return self::$dispatchers[$class];
    }

    /**
     * Subscribes to a listener to an event.
     *
     * @param string $event    event name
     * @param int    $priority optional priority, higher #s get called first
     */
    public static function listen(string $event, callable $listener, int $priority = 0)
    {
        static::getDispatcher()->addListener($event, $listener, $priority);
    }

    /**
     * Adds a listener to the model.creating and model.updating events.
     */
    public static function saving(callable $listener, int $priority = 0)
    {
        static::listen(ModelEvent::CREATING, $listener, $priority);
        static::listen(ModelEvent::UPDATING, $listener, $priority);
    }

    /**
     * Adds a listener to the model.created and model.updated events.
     */
    public static function saved(callable $listener, int $priority = 0)
    {
        static::listen(ModelEvent::CREATED, $listener, $priority);
        static::listen(ModelEvent::UPDATED, $listener, $priority);
    }

    /**
     * Adds a listener to the model.creating event.
     */
    public static function creating(callable $listener, int $priority = 0)
    {
        static::listen(ModelEvent::CREATING, $listener, $priority);
    }

    /**
     * Adds a listener to the model.created event.
     */
    public static function created(callable $listener, int $priority = 0)
    {
        static::listen(ModelEvent::CREATED, $listener, $priority);
    }

    /**
     * Adds a listener to the model.updating event.
     */
    public static function updating(callable $listener, int $priority = 0)
    {
        static::listen(ModelEvent::UPDATING, $listener, $priority);
    }

    /**
     * Adds a listener to the model.updated event.
     */
    public static function updated(callable $listener, int $priority = 0)
    {
        static::listen(ModelEvent::UPDATED, $listener, $priority);
    }

    /**
     * Adds a listener to the model.deleting event.
     */
    public static function deleting(callable $listener, int $priority = 0)
    {
        static::listen(ModelEvent::DELETING, $listener, $priority);
    }

    /**
     * Adds a listener to the model.deleted event.
     */
    public static function deleted(callable $listener, int $priority = 0)
    {
        static::listen(ModelEvent::DELETED, $listener, $priority);
    }

    /**
     * Dispatches the given event and checks if it was successful.
     *
     * @return bool true if the events were successfully propagated
     */
    private function performDispatch(string $eventName, bool $usesTransactions): bool
    {
        $event = new ModelEvent($this);
        static::getDispatcher()->dispatch($event, $eventName);

        // when listeners fail roll back any database transaction
        if ($event->isPropagationStopped()) {
            if ($usesTransactions) {
                self::$driver->rollBackTransaction($this->getConnection());
            }

            return false;
        }

        return true;
    }

    /////////////////////////////
    // Validation
    /////////////////////////////

    /**
     * Gets the error stack for this model.
     */
    public function getErrors(): Errors
    {
        if (!$this->errors) {
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

        // run the validator against the model values
        $values = $this->_unsaved + $this->_values;

        $validated = true;
        foreach ($values as $k => $v) {
            $property = static::definition()->get($k);
            $validated = Validator::validateProperty($this, $property, $v) && $validated;
        }

        // add back any modified unsaved values
        foreach (array_keys($this->_unsaved) as $k) {
            $this->_unsaved[$k] = $values[$k];
        }

        return $validated;
    }
}
