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

use BadMethodCallException;
use ICanBoogie\Inflector;
use Pulsar\Driver\DriverInterface;
use Pulsar\Exception\DriverMissingException;
use Pulsar\Exception\MassAssignmentException;
use Pulsar\Exception\ModelException;
use Pulsar\Exception\ModelNotFoundException;
use Pulsar\Relation\BelongsTo;
use Pulsar\Relation\BelongsToMany;
use Pulsar\Relation\HasMany;
use Pulsar\Relation\HasOne;
use Pulsar\Relation\Relation;
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
abstract class Model implements \ArrayAccess
{
    const IMMUTABLE = 0;
    const MUTABLE_CREATE_ONLY = 1;
    const MUTABLE = 2;

    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_OBJECT = 'object';
    const TYPE_ARRAY = 'array';

    const RELATIONSHIP_HAS_ONE = 'has_one';
    const RELATIONSHIP_HAS_MANY = 'has_many';
    const RELATIONSHIP_BELONGS_TO = 'belongs_to';
    const RELATIONSHIP_BELONGS_TO_MANY = 'belongs_to_many';

    const DEFAULT_ID_PROPERTY = 'id';

    /////////////////////////////
    // Model visible variables
    /////////////////////////////

    /**
     * List of model ID property names.
     *
     * @var array
     */
    protected static $ids = [self::DEFAULT_ID_PROPERTY];

    /**
     * Property definitions expressed as a key-value map with
     * property names as the keys.
     * i.e. ['enabled' => ['type' => Model::TYPE_BOOLEAN]].
     *
     * @var array
     */
    protected static $properties = [];

    /**
     * @var array
     */
    protected static $dispatchers;

    /**
     * @var number|string|false
     */
    protected $_id;

    /**
     * @var array
     */
    protected $_ids;

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
     * @var bool
     */
    protected $_loaded = false;

    /**
     * @var array
     */
    protected $_relationships = [];

    /**
     * @var Errors
     */
    protected $_errors;

    /////////////////////////////
    // Base model variables
    /////////////////////////////

    /**
     * @var array
     */
    private static $propertyDefinitionBase = [
        'type' => null,
        'mutable' => self::MUTABLE,
        'null' => false,
        'unique' => false,
        'required' => false,
    ];

    /**
     * @var array
     */
    private static $defaultIDProperty = [
        'type' => self::TYPE_INTEGER,
        'mutable' => self::IMMUTABLE,
    ];

    /**
     * @var array
     */
    private static $timestampProperties = [
        'created_at' => [
            'type' => self::TYPE_DATE,
            'validate' => 'timestamp|db_timestamp',
        ],
        'updated_at' => [
            'type' => self::TYPE_DATE,
            'validate' => 'timestamp|db_timestamp',
        ],
    ];

    /**
     * @var array
     */
    private static $softDeleteProperties = [
        'deleted_at' => [
            'type' => self::TYPE_DATE,
            'validate' => 'timestamp|db_timestamp',
            'null' => true,
        ],
    ];

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
     * @var bool
     */
    private $_ignoreUnsaved;

    /**
     * Creates a new model object.
     *
     * @param array|string|Model|false $id     ordered array of ids or comma-separated id string
     * @param array                    $values optional key-value map to pre-seed model
     */
    public function __construct($id = false, array $values = [])
    {
        // initialize the model
        $this->init();

        // parse the supplied model ID
        $this->parseId($id);

        // load any given values
        if (count($values) > 0) {
            $this->refreshWith($values);
        }
    }

    /**
     * Performs initialization on this model.
     */
    private function init()
    {
        // ensure the initialize function is called only once
        $k = get_called_class();
        if (!isset(self::$initialized[$k])) {
            $this->initialize();
            self::$initialized[$k] = true;
        }
    }

    /**
     * The initialize() method is called once per model. It's used
     * to perform any one-off tasks before the model gets
     * constructed. This is a great place to add any model
     * properties. When extending this method be sure to call
     * parent::initialize() as some important stuff happens here.
     * If extending this method to add properties then you should
     * call parent::initialize() after adding any properties.
     */
    protected function initialize()
    {
        // load the driver
        static::getDriver();

        // add in the default ID property
        if (static::$ids == [self::DEFAULT_ID_PROPERTY] && !isset(static::$properties[self::DEFAULT_ID_PROPERTY])) {
            static::$properties[self::DEFAULT_ID_PROPERTY] = self::$defaultIDProperty;
        }

        // generates created_at and updated_at timestamps
        if (property_exists($this, 'autoTimestamps')) {
            $this->installAutoTimestamps();
        }

        // generates deleted_at timestamps
        if (property_exists($this, 'softDelete')) {
            $this->installSoftDelete();
        }

        // fill in each property by extending the property
        // definition base
        foreach (static::$properties as $k => &$property) {
            $property = array_replace(self::$propertyDefinitionBase, $property);

            // populate relationship property settings
            if (isset($property['relation'])) {
                // this is added for BC with older versions of pulsar
                // that only supported belongs to relationships
                if (!isset($property['relation_type'])) {
                    $property['relation_type'] = self::RELATIONSHIP_BELONGS_TO;
                    $property['local_key'] = $k;
                }

                $relation = $this->getRelationshipManager($k);
                if (!isset($property['foreign_key'])) {
                    $property['foreign_key'] = $relation->getForeignKey();
                }

                if (!isset($property['local_key'])) {
                    $property['local_key'] = $relation->getLocalKey();
                }

                if (!isset($property['pivot_tablename']) && $relation instanceof BelongsToMany) {
                    $property['pivot_tablename'] = $relation->getTablename();
                }
            }
        }

        // order the properties array by name for consistency
        // since it is constructed in a random order
        ksort(static::$properties);
    }

    /**
     * Installs the `created_at` and `updated_at` properties.
     */
    private function installAutoTimestamps()
    {
        static::$properties = array_replace(self::$timestampProperties, static::$properties);

        self::creating(function (ModelEvent $event) {
            $model = $event->getModel();
            $model->created_at = time();
            $model->updated_at = time();
        });

        self::updating(function (ModelEvent $event) {
            $event->getModel()->updated_at = time();
        });
    }

    /**
     * Installs the `deleted_at` properties.
     */
    private function installSoftDelete()
    {
        static::$properties = array_replace(self::$softDeleteProperties, static::$properties);
    }

    /**
     * Parses the given ID, which can be a single or composite primary key.
     *
     * @param mixed $id
     */
    private function parseId($id)
    {
        if (is_array($id)) {
            // A model can be supplied as a primary key
            foreach ($id as &$el) {
                if ($el instanceof self) {
                    $el = $el->id();
                }
            }

            // The IDs come in as the same order as ::$ids.
            // We need to match up the elements on that
            // input into a key-value map for each ID property.
            $ids = [];
            $idQueue = array_reverse($id);
            foreach (static::$ids as $k => $f) {
                // type cast
                if (count($idQueue) > 0) {
                    $idProperty = static::getProperty($f);
                    $ids[$f] = static::cast($idProperty, array_pop($idQueue));
                } else {
                    $ids[$f] = false;
                }
            }

            $this->_id = implode(',', $id);
            $this->_ids = $ids;
        } elseif ($id instanceof self) {
            // A model can be supplied as a primary key
            $this->_id = $id->id();
            $this->_ids = $id->ids();
        } else {
            // type cast the single primary key
            $idName = static::$ids[0];
            if (false !== $id) {
                $idProperty = static::getProperty($idName);
                $id = static::cast($idProperty, $id);
            }

            $this->_id = $id;
            $this->_ids = [$idName => $id];
        }
    }

    /**
     * Sets the driver for all models.
     *
     * @param DriverInterface $driver
     */
    public static function setDriver(DriverInterface $driver)
    {
        self::$driver = $driver;
    }

    /**
     * Gets the driver for all models.
     *
     * @return DriverInterface
     *
     * @throws DriverMissingException when a driver has not been set yet
     */
    public static function getDriver()
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
     *
     * @return string
     */
    public static function modelName()
    {
        // strip namespacing
        $paths = explode('\\', get_called_class());

        return end($paths);
    }

    /**
     * Gets the model ID.
     *
     * @return string|number|false ID
     */
    public function id()
    {
        return $this->_id;
    }

    /**
     * Gets a key-value map of the model ID.
     *
     * @return array ID map
     */
    public function ids()
    {
        return $this->_ids;
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
        $values = array_merge($this->_values, $this->_unsaved, $this->_ids);
        ksort($values);

        return get_called_class().'('.json_encode($values, JSON_PRETTY_PRINT).')';
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
        return array_key_exists($name, $this->_unsaved) || static::hasProperty($name);
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
     * Gets all the property definitions for the model.
     *
     * @return array key-value map of properties
     */
    public static function getProperties()
    {
        return static::$properties;
    }

    /**
     * Gets a property defition for the model.
     *
     * @param string $property property to lookup
     *
     * @return array|null property
     */
    public static function getProperty($property)
    {
        return array_value(static::$properties, $property);
    }

    /**
     * Gets the names of the model ID properties.
     *
     * @return array
     */
    public static function getIDProperties()
    {
        return static::$ids;
    }

    /**
     * Checks if the model has a property.
     *
     * @param string $property property
     *
     * @return bool has property
     */
    public static function hasProperty($property)
    {
        return isset(static::$properties[$property]);
    }

    /**
     * Gets the mutator method name for a given proeprty name.
     * Looks for methods in the form of `setPropertyValue`.
     * i.e. the mutator for `last_name` would be `setLastNameValue`.
     *
     * @param string $property property
     *
     * @return string|false method name if it exists
     */
    public static function getMutator($property)
    {
        $class = get_called_class();

        $k = $class.':'.$property;
        if (!array_key_exists($k, self::$mutators)) {
            $inflector = Inflector::get();
            $method = 'set'.$inflector->camelize($property).'Value';

            if (!method_exists($class, $method)) {
                $method = false;
            }

            self::$mutators[$k] = $method;
        }

        return self::$mutators[$k];
    }

    /**
     * Gets the accessor method name for a given proeprty name.
     * Looks for methods in the form of `getPropertyValue`.
     * i.e. the accessor for `last_name` would be `getLastNameValue`.
     *
     * @param string $property property
     *
     * @return string|false method name if it exists
     */
    public static function getAccessor($property)
    {
        $class = get_called_class();

        $k = $class.':'.$property;
        if (!array_key_exists($k, self::$accessors)) {
            $inflector = Inflector::get();
            $method = 'get'.$inflector->camelize($property).'Value';

            if (!method_exists($class, $method)) {
                $method = false;
            }

            self::$accessors[$k] = $method;
        }

        return self::$accessors[$k];
    }

    /**
     * Marshals a value for a given property from storage.
     *
     * @param array $property
     * @param mixed $value
     *
     * @return mixed type-casted value
     */
    public static function cast(array $property, $value)
    {
        if (null === $value) {
            return;
        }

        // handle empty strings as null
        if ($property['null'] && '' == $value) {
            return;
        }

        $type = array_value($property, 'type');
        $m = 'to_'.$type;

        if (!method_exists(Property::class, $m)) {
            return $value;
        }

        return Property::$m($value);
    }

    /////////////////////////////
    // CRUD Operations
    /////////////////////////////

    /**
     * Gets the tablename for storing this model.
     *
     * @return string
     */
    public function getTablename()
    {
        $inflector = Inflector::get();

        return $inflector->camelize($inflector->pluralize(static::modelName()));
    }

    /**
     * Gets the ID of the connection in the connection manager
     * that stores this model.
     *
     * @return string|null
     */
    public function getConnection()
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
    public function save()
    {
        if (false === $this->_id) {
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
    public function create(array $data = [])
    {
        if (false !== $this->_id) {
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
        foreach (static::$properties as $name => $property) {
            // build a list of the required properties
            if ($property['required']) {
                $requiredProperties[] = $name;
            }

            // add in default values
            if (!array_key_exists($name, $this->_unsaved) && array_key_exists('default', $property)) {
                $this->_unsaved[$name] = $property['default'];
            }
        }

        // validate the values being saved
        $validated = true;
        $insertArray = [];
        foreach ($this->_unsaved as $name => $value) {
            // exclude if value does not map to a property
            if (!isset(static::$properties[$name])) {
                continue;
            }

            $property = static::$properties[$name];

            // cannot insert immutable values
            // (unless using the default value)
            if (self::IMMUTABLE == $property['mutable'] && $value !== $this->getPropertyDefault($property)) {
                continue;
            }

            $validated = $validated && $this->filterAndValidate($property, $name, $value);
            $insertArray[$name] = $value;
        }

        // check for required fields
        foreach ($requiredProperties as $name) {
            if (!isset($insertArray[$name])) {
                $property = static::$properties[$name];
                $params = [
                    'field' => $name,
                    'field_name' => $this->getPropertyTitle($property, $name),
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
            $this->getNewID();

            // store the persisted values to the in-memory cache
            $this->_unsaved = [];
            $this->refreshWith(array_replace($this->_ids, $insertArray));

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
        $this->_ignoreUnsaved = true;

        return $this;
    }

    /**
     * Fetches property values from the model.
     *
     * This method looks up values in this order:
     * IDs, local cache, unsaved values, storage layer, defaults
     *
     * @param array $properties list of property names to fetch values of
     *
     * @return array
     */
    public function get(array $properties)
    {
        // load the values from the IDs and local model cache
        $values = array_replace($this->ids(), $this->_values);

        // unless specified, use any unsaved values
        $ignoreUnsaved = $this->_ignoreUnsaved;
        $this->_ignoreUnsaved = false;

        if (!$ignoreUnsaved) {
            $values = array_replace($values, $this->_unsaved);
        }

        // see if there are any model properties that do not exist.
        // when true then this means the model needs to be hydrated
        // NOTE: only looking at model properties and excluding dynamic/non-existent properties
        $modelProperties = array_keys(static::$properties);
        $numMissing = count(array_intersect($modelProperties, array_diff($properties, array_keys($values))));

        if ($numMissing > 0 && !$this->_loaded) {
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
     * @param string $property
     * @param array  $values
     *
     * @return mixed
     */
    protected function getValue($property, array $values)
    {
        $value = null;

        if (array_key_exists($property, $values)) {
            $value = $values[$property];
        } elseif (static::hasProperty($property)) {
            $value = $this->_values[$property] = $this->getPropertyDefault(static::$properties[$property]);
        }

        // call any accessors
        if ($accessor = self::getAccessor($property)) {
            $value = $this->$accessor($value);
        }

        return $value;
    }

    /**
     * Populates a newly created model with its ID.
     */
    protected function getNewID()
    {
        $ids = [];
        $namedIds = [];
        foreach (static::$ids as $k) {
            // attempt use the supplied value if the ID property is mutable
            $property = static::getProperty($k);
            if (in_array($property['mutable'], [self::MUTABLE, self::MUTABLE_CREATE_ONLY]) && isset($this->_unsaved[$k])) {
                $id = $this->_unsaved[$k];
            } else {
                $id = self::$driver->getCreatedID($this, $k);
            }

            $ids[] = $id;
            $namedIds[$k] = $id;
        }

        $this->_id = implode(',', $ids);
        $this->_ids = $namedIds;
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
     *
     * @return array
     */
    public function toArray()
    {
        // build the list of properties to retrieve
        $properties = array_keys(static::$properties);

        // remove any hidden properties
        $hide = (property_exists($this, 'hidden')) ? static::$hidden : [];
        $properties = array_diff($properties, $hide);

        // add any appended properties
        $append = (property_exists($this, 'appended')) ? static::$appended : [];
        $properties = array_merge($properties, $append);

        // get the values for the properties
        $result = $this->get($properties);

        foreach ($result as $k => &$value) {
            // convert any models to arrays
            if ($value instanceof self) {
                $value = $value->toArray();
            }
        }

        // DEPRECATED
        // apply the transformation hook
        if (method_exists($this, 'toArrayHook')) {
            $this->toArrayHook($result, [], [], []);
        }

        return $result;
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
    public function set(array $data = [])
    {
        if (false === $this->_id) {
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

        // validate the values being saved
        $validated = true;
        $updateArray = [];
        foreach ($this->_unsaved as $name => $value) {
            // exclude if value does not map to a property
            if (!isset(static::$properties[$name])) {
                continue;
            }

            $property = static::$properties[$name];

            // can only modify mutable properties
            if (self::MUTABLE != $property['mutable']) {
                continue;
            }

            $validated = $validated && $this->filterAndValidate($property, $name, $value);
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
            $this->refreshWith(array_replace($this->_values, $updateArray));

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
    public function delete()
    {
        if (false === $this->_id) {
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
            $t = $this->filterAndValidate(static::getProperty('deleted_at'), 'deleted_at', $t);
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
     *
     * @return bool
     */
    public function restore()
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
     *
     * @return bool
     */
    public function isDeleted()
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
     *
     * @return Query
     */
    public static function query()
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
     *
     * @return Query
     */
    public static function withDeleted()
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
    public static function find($id)
    {
        $ids = [];
        $id = (array) $id;
        foreach (static::$ids as $j => $k) {
            if ($_id = array_value($id, $j)) {
                $ids[$k] = $_id;
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
    public static function findOrFail($id)
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
     *
     * @return bool
     */
    public function persisted()
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
        if (false === $this->_id) {
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
            if ($property = static::getProperty($k)) {
                $value = static::cast($property, $value);
            }
        }

        $this->_loaded = true;
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
        $this->_loaded = false;
        $this->_unsaved = [];
        $this->_values = [];
        $this->_relationships = [];

        return $this;
    }

    /////////////////////////////
    // Relationships
    /////////////////////////////

    /**
     * @deprecated
     *
     * Gets the model(s) for a relationship
     *
     * @param string $k property
     *
     * @throws \InvalidArgumentException when the relationship manager cannot be created
     *
     * @return Model|null
     */
    public function relation($k)
    {
        if (!array_key_exists($k, $this->_relationships)) {
            $relation = $this->getRelationshipManager($k);
            $this->_relationships[$k] = $relation->getResults();
        }

        return $this->_relationships[$k];
    }

    /**
     * @deprecated
     *
     * Sets the model for a one-to-one relationship (has-one or belongs-to)
     *
     * @param string $k
     * @param Model  $model
     *
     * @return $this
     */
    public function setRelation($k, self $model)
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
     * @param string   $k
     * @param iterable $models
     *
     * @return $this
     */
    public function setRelationCollection($k, $models)
    {
        $this->_relationships[$k] = $models;

        return $this;
    }

    /**
     * Sets the model for a one-to-one relationship (has-one or belongs-to) as null.
     *
     * @param string $k
     *
     * @return $this
     */
    public function clearRelation($k)
    {
        $this->$k = null;
        $this->_relationships[$k] = null;

        return $this;
    }

    /**
     * Builds a relationship manager object for a given property.
     *
     * @param array $k
     *
     * @throws \InvalidArgumentException when the relationship manager cannot be created
     *
     * @return Relation
     */
    public function getRelationshipManager($k)
    {
        $property = static::getProperty($k);
        if (!isset($property['relation'])) {
            throw new \InvalidArgumentException('Property "'.$k.'" does not have a relationship.');
        }

        $relationModelClass = $property['relation'];
        $foreignKey = array_value($property, 'foreign_key');
        $localKey = array_value($property, 'local_key');

        if (self::RELATIONSHIP_HAS_ONE == $property['relation_type']) {
            return $this->hasOne($relationModelClass, $foreignKey, $localKey);
        }

        if (self::RELATIONSHIP_HAS_MANY == $property['relation_type']) {
            return $this->hasMany($relationModelClass, $foreignKey, $localKey);
        }

        if (self::RELATIONSHIP_BELONGS_TO == $property['relation_type']) {
            return $this->belongsTo($relationModelClass, $foreignKey, $localKey);
        }

        if (self::RELATIONSHIP_BELONGS_TO_MANY == $property['relation_type']) {
            $pivotTable = array_value($property, 'pivot_tablename');

            return $this->belongsToMany($relationModelClass, $pivotTable, $foreignKey, $localKey);
        }

        throw new \InvalidArgumentException('Relationship type on "'.$k.'" property not supported: '.$property['relation_type']);
    }

    /**
     * Creates the parent side of a One-To-One relationship.
     *
     * @param string $model      foreign model class
     * @param string $foreignKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     *
     * @return HasOne
     */
    public function hasOne($model, $foreignKey = '', $localKey = '')
    {
        return new HasOne($this, $localKey, $model, $foreignKey);
    }

    /**
     * Creates the child side of a One-To-One or One-To-Many relationship.
     *
     * @param string $model      foreign model class
     * @param string $foreignKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     *
     * @return BelongsTo
     */
    public function belongsTo($model, $foreignKey = '', $localKey = '')
    {
        return new BelongsTo($this, $localKey, $model, $foreignKey);
    }

    /**
     * Creates the parent side of a Many-To-One or Many-To-Many relationship.
     *
     * @param string $model      foreign model class
     * @param string $foreignKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     *
     * @return HasMany
     */
    public function hasMany($model, $foreignKey = '', $localKey = '')
    {
        return new HasMany($this, $localKey, $model, $foreignKey);
    }

    /**
     * Creates the child side of a Many-To-Many relationship.
     *
     * @param string $model      foreign model class
     * @param string $tablename  pivot table name
     * @param string $foreignKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     *
     * @return BelongsToMany
     */
    public function belongsToMany($model, $tablename = '', $foreignKey = '', $localKey = '')
    {
        return new BelongsToMany($this, $localKey, $tablename, $model, $foreignKey);
    }

    /////////////////////////////
    // Events
    /////////////////////////////

    /**
     * Gets the event dispatcher.
     *
     * @return EventDispatcher
     */
    public static function getDispatcher($ignoreCache = false)
    {
        $class = get_called_class();
        if ($ignoreCache || !isset(self::$dispatchers[$class])) {
            self::$dispatchers[$class] = new EventDispatcher();
        }

        return self::$dispatchers[$class];
    }

    /**
     * Subscribes to a listener to an event.
     *
     * @param string   $event    event name
     * @param callable $listener
     * @param int      $priority optional priority, higher #s get called first
     */
    public static function listen($event, callable $listener, $priority = 0)
    {
        static::getDispatcher()->addListener($event, $listener, $priority);
    }

    /**
     * Adds a listener to the model.creating and model.updating events.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public static function saving(callable $listener, $priority = 0)
    {
        static::listen(ModelEvent::CREATING, $listener, $priority);
        static::listen(ModelEvent::UPDATING, $listener, $priority);
    }

    /**
     * Adds a listener to the model.created and model.updated events.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public static function saved(callable $listener, $priority = 0)
    {
        static::listen(ModelEvent::CREATED, $listener, $priority);
        static::listen(ModelEvent::UPDATED, $listener, $priority);
    }

    /**
     * Adds a listener to the model.creating event.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public static function creating(callable $listener, $priority = 0)
    {
        static::listen(ModelEvent::CREATING, $listener, $priority);
    }

    /**
     * Adds a listener to the model.created event.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public static function created(callable $listener, $priority = 0)
    {
        static::listen(ModelEvent::CREATED, $listener, $priority);
    }

    /**
     * Adds a listener to the model.updating event.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public static function updating(callable $listener, $priority = 0)
    {
        static::listen(ModelEvent::UPDATING, $listener, $priority);
    }

    /**
     * Adds a listener to the model.updated event.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public static function updated(callable $listener, $priority = 0)
    {
        static::listen(ModelEvent::UPDATED, $listener, $priority);
    }

    /**
     * Adds a listener to the model.deleting event.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public static function deleting(callable $listener, $priority = 0)
    {
        static::listen(ModelEvent::DELETING, $listener, $priority);
    }

    /**
     * Adds a listener to the model.deleted event.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public static function deleted(callable $listener, $priority = 0)
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

        // DEPRECATED
        if (ModelEvent::UPDATING == $eventName && !$event->isPropagationStopped() && method_exists($this, 'preSetHook')) {
            if (!$this->preSetHook($this->_unsaved)) {
                // when listeners fail roll back any database transaction
                if ($usesTransactions) {
                    self::$driver->rollBackTransaction($this->getConnection());
                }

                return false;
            }
        }

        return true;
    }

    /////////////////////////////
    // Validation
    /////////////////////////////

    /**
     * Gets the error stack for this model.
     *
     * @return Errors
     */
    public function getErrors()
    {
        if (!$this->_errors) {
            $this->_errors = new Errors();
        }

        return $this->_errors;
    }

    /**
     * Checks if the model in its current state is valid.
     *
     * @return bool
     */
    public function valid()
    {
        // clear any previous errors
        $this->getErrors()->clear();

        // run the validator against the model values
        $values = $this->_unsaved + $this->_values;

        $validated = true;
        foreach ($values as $k => $v) {
            $property = static::getProperty($k);
            $validated = $this->filterAndValidate($property, $k, $v) && $validated;
        }

        // add back any modified unsaved values
        foreach (array_keys($this->_unsaved) as $k) {
            $this->_unsaved[$k] = $values[$k];
        }

        return $validated;
    }

    /**
     * Validates and marshals a value to storage.
     *
     * @param array  $property property definition
     * @param string $name     property name
     * @param mixed  $value
     *
     * @return bool
     */
    private function filterAndValidate(array $property, $name, &$value)
    {
        // assume empty string is a null value for properties
        // that are marked as optionally-null
        if ($property['null'] && empty($value)) {
            $value = null;

            return true;
        }

        // validate
        list($valid, $value) = $this->validateValue($property, $name, $value);

        // unique?
        if ($valid && $property['unique'] && (false === $this->_id || $value != $this->ignoreUnsaved()->$name)) {
            $valid = $this->checkUniqueness($property, $name, $value);
        }

        return $valid;
    }

    /**
     * Validates a value for a property.
     *
     * @param array  $property property definition
     * @param string $name     property name
     * @param mixed  $value
     *
     * @return array
     */
    private function validateValue(array $property, $name, $value)
    {
        $valid = true;

        $error = 'pulsar.validation.failed';
        if (isset($property['validate']) && is_callable($property['validate'])) {
            $valid = call_user_func_array($property['validate'], [$value]);
        } elseif (isset($property['validate'])) {
            $validator = new Validator($property['validate']);
            $valid = $validator->validate($value);
            $error = 'pulsar.validation.'.$validator->getFailingRule();
        }

        if (!$valid) {
            $params = [
                'field' => $name,
                'field_name' => $this->getPropertyTitle($property, $name),
            ];
            $this->getErrors()->add($error, $params);
        }

        return [$valid, $value];
    }

    /**
     * Checks if a value is unique for a property.
     *
     * @param array  $property property definition
     * @param string $name     property name
     * @param mixed  $value
     *
     * @return bool
     */
    private function checkUniqueness(array $property, $name, $value)
    {
        $n = static::query()->where([$name => $value])->count();
        if ($n > 0) {
            $params = [
                'field' => $name,
                'field_name' => $this->getPropertyTitle($property, $name),
            ];
            $this->getErrors()->add('pulsar.validation.unique', $params);

            return false;
        }

        return true;
    }

    /**
     * Gets the marshaled default value for a property (if set).
     *
     * @param array $property
     *
     * @return mixed
     */
    private function getPropertyDefault(array $property)
    {
        return array_value($property, 'default');
    }

    /**
     * Gets the humanized name of a property.
     *
     * @param array  $property property definition
     * @param string $name     property name
     *
     * @return string
     */
    private function getPropertyTitle(array $property, $name)
    {
        // look up the property from the locale service first
        $k = 'pulsar.properties.'.static::modelName().'.'.$name;
        $locale = $this->getErrors()->getLocale();
        $title = $locale->t($k);
        if ($title != $k) {
            return $title;
        }

        // DEPRECATED
        if (isset($property['title'])) {
            return $property['title'];
        }

        // otherwise just attempt to title-ize the property name
        return Inflector::get()->titleize($name);
    }
}
