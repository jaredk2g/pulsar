<?php

namespace Pulsar;

use ArrayAccess;

class Property implements ArrayAccess
{
    const IMMUTABLE = 'immutable';
    const MUTABLE_CREATE_ONLY = 'mutable_create_only';
    const MUTABLE = 'mutable';

    /** @var string|null */
    private $type = null;

    /** @var string */
    private $mutable = self::MUTABLE;

    /** @var bool */
    private $null = false;

    /** @var bool */
    private $unique = false;

    /** @var bool */
    private $required = false;

    /** @var callable|string|null */
    private $validate = null;

    /** @var mixed|null */
    private $default = null;

    /** @var string|null */
    private $relation;

    /** @var string|null */
    private $relation_type;

    /** @var string|null */
    private $foreign_key;

    /** @var string|null */
    private $local_key;

    /** @var string|null */
    private $pivot_tablename;

    public function __construct(array $values = [])
    {
        foreach ($values as $k => $v) {
            $this->$k = $v;
        }
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function isMutable(): bool
    {
        return self::MUTABLE == $this->mutable;
    }

    public function isMutableCreateOnly(): bool
    {
        return self::MUTABLE_CREATE_ONLY == $this->mutable;
    }

    public function isImmutable(): bool
    {
        return self::IMMUTABLE == $this->mutable;
    }

    public function isNullable(): bool
    {
        return $this->null;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return callable|string|null
     */
    public function getValidationRules()
    {
        return $this->validate;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    public function getRelation(): ?string
    {
        return $this->relation;
    }

    public function getRelationType(): ?string
    {
        return $this->relation_type;
    }

    public function getForeignKey(): ?string
    {
        return $this->foreign_key;
    }

    public function getLocalKey(): ?string
    {
        return $this->local_key;
    }

    public function getPivotTablename(): ?string
    {
        return $this->pivot_tablename;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'mutable' => $this->mutable,
            'null' => $this->null,
            'unique' => $this->unique,
            'required' => $this->required,
            'validate' => $this->validate,
            'default' => $this->default,
            'relation' => $this->relation,
            'relation_type' => $this->relation_type,
            'foreign_key' => $this->foreign_key,
            'local_key' => $this->local_key,
            'pivot_tablename' => $this->getPivotTablename(),
        ];
    }

    public function offsetExists($offset)
    {
        return property_exists($this, $offset) && $this->$offset !== null;
    }

    public function offsetGet($offset)
    {
        if (!property_exists($this, $offset)) {
            return null;
        }

        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException('Modifying a model property is not allowed.');
    }

    public function offsetUnset($offset)
    {
        throw new \RuntimeException('Modifying a model property is not allowed.');
    }
}
