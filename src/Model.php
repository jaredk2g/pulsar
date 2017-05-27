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
use Pimple\Container;
use Pulsar\Driver\DriverInterface;
use Pulsar\Exception\DriverMissingException;
use Pulsar\Exception\ModelNotFoundException;
use Pulsar\Relation\BelongsTo;
use Pulsar\Relation\BelongsToMany;
use Pulsar\Relation\HasMany;
use Pulsar\Relation\HasOne;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class Model.
 */
abstract class Model implements \ArrayAccess
{
    const IMMUTABLE = 0;
    const MUTABLE_CREATE_ONLY = 1;
    const MUTABLE = 2;

    const TYPE_STRING = 'string';
    const TYPE_NUMBER = 'number'; // DEPRECATED
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_OBJECT = 'object';
    const TYPE_ARRAY = 'array';

    const ERROR_REQUIRED_FIELD_MISSING = 'required_field_missing';
    const ERROR_VALIDATION_FAILED = 'validation_failed';
    const ERROR_NOT_UNIQUE = 'not_unique';

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
     * @var Container
     */
    protected static $injectedApp;

    /**
     * @var array
     */
    protected static $dispatchers;

    /**
     * @var number|string|bool
     */
    protected $_id;

    /**
     * @var Container
     */
    protected $app;

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

    /////////////////////////////
    // Base model variables
    /////////////////////////////

    /**
     * @var array
     */
    private static $propertyDefinitionBase = [
        'type' => self::TYPE_STRING,
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
        $this->app = self::$injectedApp;
        $this->init();

        // TODO need to store the id as an array
        // instead of a string to maintain type integrity
        if (is_array($id)) {
            // A model can be supplied as a primary key
            foreach ($id as &$el) {
                if ($el instanceof self) {
                    $el = $el->id();
                }
            }

            $id = implode(',', $id);
        // A model can be supplied as a primary key
        } elseif ($id instanceof self) {
            $id = $id->id();
        }

        $this->_id = $id;

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

        // fill in each property by extending the property
        // definition base
        foreach (static::$properties as &$property) {
            $property = array_replace(self::$propertyDefinitionBase, $property);
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
     * Injects a DI container.
     *
     * @param Container $app
     */
    public static function inject(Container $app)
    {
        self::$injectedApp = $app;
    }

    /**
     * Gets the DI container used for this model.
     *
     * @return Container
     */
    public function getApp()
    {
        return $this->app;
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
        $return = [];

        // match up id values from comma-separated id string with property names
        $ids = explode(',', $this->_id);
        $ids = array_reverse($ids);

        // TODO need to store the id as an array
        // instead of a string to maintain type integrity
        foreach (static::$ids as $k => $f) {
            $id = (count($ids) > 0) ? array_pop($ids) : false;

            $return[$f] = $id;
        }

        return $return;
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
        return get_called_class().'('.$this->_id.')';
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

    /////////////////////////////
    // CRUD Operations
    /////////////////////////////

    /**
     * Saves the model.
     *
     * @return bool true when the operation was successful
     */
    public function save()
    {
        if ($this->_id === false) {
            return $this->create();
        }

        return $this->set($this->_unsaved);
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
        if ($this->_id !== false) {
            throw new BadMethodCallException('Cannot call create() on an existing model');
        }

        if (!empty($data)) {
            foreach ($data as $k => $value) {
                $this->$k = $value;
            }
        }

        // dispatch the model.creating event
        if (!$this->handleDispatch(ModelEvent::CREATING)) {
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
            if ($property['mutable'] == self::IMMUTABLE && $value !== $this->getPropertyDefault($property)) {
                continue;
            }

            $validated = $validated && $this->filterAndValidate($property, $name, $value);
            $insertArray[$name] = $value;
        }

        // check for required fields
        foreach ($requiredProperties as $name) {
            if (!isset($insertArray[$name])) {
                $property = static::$properties[$name];
                $this->app['errors']->push([
                    'error' => self::ERROR_REQUIRED_FIELD_MISSING,
                    'params' => [
                        'field' => $name,
                        'field_name' => (isset($property['title'])) ? $property['title'] : Inflector::get()->titleize($name), ], ]);

                $validated = false;
            }
        }

        if (!$validated) {
            return false;
        }

        $created = self::$driver->createModel($this, $insertArray);

        if ($created) {
            // determine the model's new ID
            $this->_id = $this->getNewID();

            // NOTE clear the local cache before the model.created
            // event so that fetching values forces a reload
            // from the storage layer
            $this->clearCache();

            // dispatch the model.created event
            if (!$this->handleDispatch(ModelEvent::CREATED)) {
                return false;
            }
        }

        return $created;
    }

    /**
     * Ignores unsaved values when fetching the next value.
     *
     * @return self
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

        // attempt to load any missing values from the storage layer
        $numMissing = count(array_diff($properties, array_keys($values)));
        if ($numMissing > 0) {
            $this->refresh();
            $values = array_replace($values, $this->_values);

            if (!$ignoreUnsaved) {
                $values = array_replace($values, $this->_unsaved);
            }
        }

        // build a key-value map of the requested properties
        $return = [];
        foreach ($properties as $k) {
            if (array_key_exists($k, $values)) {
                $return[$k] = $values[$k];
            // set any missing values to the default value
            } elseif (static::hasProperty($k)) {
                $return[$k] = $this->_values[$k] = $this->getPropertyDefault(static::$properties[$k]);
            // use null for values of non-properties
            } else {
                $return[$k] = null;
            }

            // call any accessors
            if ($accessor = self::getAccessor($k)) {
                $return[$k] = $this->$accessor($return[$k]);
            }
        }

        return $return;
    }

    /**
     * Gets the ID for a newly created model.
     *
     * @return string
     */
    protected function getNewID()
    {
        $ids = [];
        foreach (static::$ids as $k) {
            // attempt use the supplied value if the ID property is mutable
            $property = static::getProperty($k);
            if (in_array($property['mutable'], [self::MUTABLE, self::MUTABLE_CREATE_ONLY]) && isset($this->_unsaved[$k])) {
                $ids[] = $this->_unsaved[$k];
            } else {
                $ids[] = self::$driver->getCreatedID($this, $k);
            }
        }

        // TODO need to store the id as an array
        // instead of a string to maintain type integrity
        return (count($ids) > 1) ? implode(',', $ids) : $ids[0];
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
        if ($this->_id === false) {
            throw new BadMethodCallException('Can only call set() on an existing model');
        }

        // not updating anything?
        if (count($data) == 0) {
            return true;
        }

        // apply mutators
        foreach ($data as $k => $value) {
            if ($mutator = self::getMutator($k)) {
                $data[$k] = $this->$mutator($value);
            }
        }

        // dispatch the model.updating event
        if (!$this->handleDispatch(ModelEvent::UPDATING)) {
            return false;
        }

        // DEPRECATED
        if (method_exists($this, 'preSetHook') && !$this->preSetHook($data)) {
            return false;
        }

        // validate the values being saved
        $validated = true;
        $updateArray = [];
        foreach ($data as $name => $value) {
            // exclude if value does not map to a property
            if (!isset(static::$properties[$name])) {
                continue;
            }

            $property = static::$properties[$name];

            // can only modify mutable properties
            if ($property['mutable'] != self::MUTABLE) {
                continue;
            }

            $validated = $validated && $this->filterAndValidate($property, $name, $value);
            $updateArray[$name] = $value;
        }

        if (!$validated) {
            return false;
        }

        $updated = self::$driver->updateModel($this, $updateArray);

        if ($updated) {
            // NOTE clear the local cache before the model.updated
            // event so that fetching values forces a reload
            // from the storage layer
            $this->clearCache();

            // dispatch the model.updated event
            if (!$this->handleDispatch(ModelEvent::UPDATED)) {
                return false;
            }
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
        if ($this->_id === false) {
            throw new BadMethodCallException('Can only call delete() on an existing model');
        }

        // dispatch the model.deleting event
        if (!$this->handleDispatch(ModelEvent::DELETING)) {
            return false;
        }

        $deleted = self::$driver->deleteModel($this);

        if ($deleted) {
            // dispatch the model.deleted event
            if (!$this->handleDispatch(ModelEvent::DELETED)) {
                return false;
            }

            $this->_persisted = false;
        }

        return $deleted;
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

        return new Query($model);
    }

    /**
     * Finds a single instance of a model given it's ID.
     *
     * @param mixed $id
     *
     * @return Model|false
     */
    public static function find($id)
    {
        $model = new static($id);
        $values = self::getDriver()->loadModel($model);
        if (!is_array($values)) {
            return false;
        }

        return $model->refreshWith($values);
    }

    /**
     * Finds a single instance of a model given it's ID or throws an exception.
     *
     * @param mixed $id
     *
     * @return Model
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
     * Gets the total number of records matching an optional criteria.
     *
     * @param array $where criteria
     *
     * @return int
     */
    public static function totalRecords(array $where = [])
    {
        $query = static::query();
        $query->where($where);

        return self::getDriver()->totalRecords($query);
    }

    /**
     * @deprecated
     *
     * Checks if the model exists in the database
     *
     * @return bool
     */
    public function exists()
    {
        return static::totalRecords($this->ids()) == 1;
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
     * @return self
     */
    public function refresh()
    {
        if ($this->_id === false) {
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
     * @return self
     */
    public function refreshWith(array $values)
    {
        $this->_persisted = true;
        $this->_values = $values;

        return $this;
    }

    /**
     * Clears the cache for this model.
     *
     * @return self
     */
    public function clearCache()
    {
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
     * Gets the model object corresponding to a relation
     * WARNING no check is used to see if the model returned actually exists
     *
     * @param string $propertyName property
     *
     * @return Model
     */
    public function relation($propertyName)
    {
        $property = static::getProperty($propertyName);

        if (!isset($this->_relationships[$propertyName])) {
            $relationModelName = $property['relation'];
            $this->_relationships[$propertyName] = new $relationModelName($this->$propertyName);
        }

        return $this->_relationships[$propertyName];
    }

    /**
     * Creates the parent side of a One-To-One relationship.
     *
     * @param string $model      foreign model class
     * @param string $foreignKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     *
     * @return Relation\Relation
     */
    public function hasOne($model, $foreignKey = '', $localKey = '')
    {
        // the default local key would look like `user_id`
        // for a model named User
        if (!$foreignKey) {
            $inflector = Inflector::get();
            $foreignKey = strtolower($inflector->underscore(static::modelName())).'_id';
        }

        if (!$localKey) {
            $localKey = self::DEFAULT_ID_PROPERTY;
        }

        return new HasOne($model, $foreignKey, $localKey, $this);
    }

    /**
     * Creates the child side of a One-To-One or One-To-Many relationship.
     *
     * @param string $model      foreign model class
     * @param string $foreignKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     *
     * @return Relation\Relation
     */
    public function belongsTo($model, $foreignKey = '', $localKey = '')
    {
        if (!$foreignKey) {
            $foreignKey = self::DEFAULT_ID_PROPERTY;
        }

        // the default local key would look like `user_id`
        // for a model named User
        if (!$localKey) {
            $inflector = Inflector::get();
            $localKey = strtolower($inflector->underscore($model::modelName())).'_id';
        }

        return new BelongsTo($model, $foreignKey, $localKey, $this);
    }

    /**
     * Creates the parent side of a Many-To-One or Many-To-Many relationship.
     *
     * @param string $model      foreign model class
     * @param string $foreignKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     *
     * @return Relation\Relation
     */
    public function hasMany($model, $foreignKey = '', $localKey = '')
    {
        // the default local key would look like `user_id`
        // for a model named User
        if (!$foreignKey) {
            $inflector = Inflector::get();
            $foreignKey = strtolower($inflector->underscore(static::modelName())).'_id';
        }

        if (!$localKey) {
            $localKey = self::DEFAULT_ID_PROPERTY;
        }

        return new HasMany($model, $foreignKey, $localKey, $this);
    }

    /**
     * Creates the child side of a Many-To-Many relationship.
     *
     * @param string $model      foreign model class
     * @param string $foreignKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     *
     * @return Relation\Relation
     */
    public function belongsToMany($model, $foreignKey = '', $localKey = '')
    {
        if (!$foreignKey) {
            $foreignKey = self::DEFAULT_ID_PROPERTY;
        }

        // the default local key would look like `user_id`
        // for a model named User
        if (!$localKey) {
            $inflector = Inflector::get();
            $localKey = strtolower($inflector->underscore($model::modelName())).'_id';
        }

        return new BelongsToMany($model, $foreignKey, $localKey, $this);
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
     * Dispatches an event.
     *
     * @param string $eventName
     *
     * @return ModelEvent
     */
    protected function dispatch($eventName)
    {
        $event = new ModelEvent($this);

        return static::getDispatcher()->dispatch($eventName, $event);
    }

    /**
     * Dispatches the given event and checks if it was successful.
     *
     * @param string $eventName
     *
     * @return bool true if the events were successfully propagated
     */
    private function handleDispatch($eventName)
    {
        $event = $this->dispatch($eventName);

        return !$event->isPropagationStopped();
    }

    /////////////////////////////
    // Validation
    /////////////////////////////

    /**
     * Validates and marshals a value to storage.
     *
     * @param array  $property
     * @param string $propertyName
     * @param mixed  $value
     *
     * @return bool
     */
    private function filterAndValidate(array $property, $propertyName, &$value)
    {
        // assume empty string is a null value for properties
        // that are marked as optionally-null
        if ($property['null'] && empty($value)) {
            $value = null;

            return true;
        }

        // validate
        list($valid, $value) = $this->validate($property, $propertyName, $value);

        // unique?
        if ($valid && $property['unique'] && ($this->_id === false || $value != $this->ignoreUnsaved()->$propertyName)) {
            $valid = $this->checkUniqueness($property, $propertyName, $value);
        }

        return $valid;
    }

    /**
     * Validates a value for a property.
     *
     * @param array  $property
     * @param string $propertyName
     * @param mixed  $value
     *
     * @return array
     */
    private function validate(array $property, $propertyName, $value)
    {
        $valid = true;

        if (isset($property['validate']) && is_callable($property['validate'])) {
            $valid = call_user_func_array($property['validate'], [$value]);
        } elseif (isset($property['validate'])) {
            $valid = Validate::is($value, $property['validate']);
        }

        if (!$valid) {
            $this->app['errors']->push([
                'error' => self::ERROR_VALIDATION_FAILED,
                'params' => [
                    'field' => $propertyName,
                    'field_name' => (isset($property['title'])) ? $property['title'] : Inflector::get()->titleize($propertyName), ], ]);
        }

        return [$valid, $value];
    }

    /**
     * Checks if a value is unique for a property.
     *
     * @param array  $property
     * @param string $propertyName
     * @param mixed  $value
     *
     * @return bool
     */
    private function checkUniqueness(array $property, $propertyName, $value)
    {
        if (static::totalRecords([$propertyName => $value]) > 0) {
            $this->app['errors']->push([
                'error' => self::ERROR_NOT_UNIQUE,
                'params' => [
                    'field' => $propertyName,
                    'field_name' => (isset($property['title'])) ? $property['title'] : Inflector::get()->titleize($propertyName), ], ]);

            return false;
        }

        return true;
    }

    /**
     * Gets the marshaled default value for a property (if set).
     *
     * @param string $property
     *
     * @return mixed
     */
    private function getPropertyDefault(array $property)
    {
        return array_value($property, 'default');
    }
}
