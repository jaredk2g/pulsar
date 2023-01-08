<?php

namespace Pulsar;

use ICanBoogie\Inflector;

final class Property
{
    const IMMUTABLE = 'immutable';
    const MUTABLE_CREATE_ONLY = 'mutable_create_only';
    const MUTABLE = 'mutable';

    private const RELATIONSHIP_SHORTCUTS = ['belongs_to', 'belongs_to_many', 'has_one', 'has_many'];
    private const MISSING_DEFAULT = '_____missing_default_____';

    public readonly mixed $default;
    public readonly bool $persisted;
    public readonly bool $in_array;
    public readonly ?string $relation;
    public readonly ?string $relation_type;
    public readonly bool $hasDefault;

    public function __construct(
        public readonly string $name = '',
        public readonly ?string $type = null,
        public readonly string $mutable = self::MUTABLE,
        public readonly bool $null = false,
        public readonly bool $required = false,
        public readonly array|string|null $validate = null,
        mixed $default = self::MISSING_DEFAULT,
        public readonly bool $encrypted = false,
        bool $persisted = true,
        bool $in_array = true,
        ?string $relation = null,
        ?string $relation_type = null,
        public readonly ?string $foreign_key = null,
        public readonly ?string $local_key = null,
        public readonly ?string $pivot_tablename = null,
        public readonly ?array $morphs_to = null,
        ?string $belongs_to = null,
        ?string $belongs_to_many = null,
        ?string $has_one = null,
        ?string $has_many = null,
        public readonly ?string $enum_class = null,
    )
    {
        $this->hasDefault = $default !== self::MISSING_DEFAULT;
        $this->default = $this->hasDefault ? $default : null;

        // Relationship shortcuts
        foreach (self::RELATIONSHIP_SHORTCUTS as $k) {
            if ($$k) {
                $persisted = false;
                $in_array = false;
                $relation = $$k;
                $relation_type = $k;
            }
        }

        $this->persisted = $persisted;
        $this->in_array = $in_array;
        $this->relation = $relation;
        $this->relation_type = $relation_type;
    }

    /**
     * Gets the humanized name of this property.
     */
    public function getTitle(Model $model): string
    {
        // look up the property from the translator first
        if ($translator = $model->getErrors()->getTranslator()) {
            $k = 'pulsar.properties.'.$model::modelName().'.'.$this->name;
            $title = $translator->translate($k);
            if ($title != $k) {
                return $title;
            }
        }

        // otherwise just attempt to title-ize the property name
        return Inflector::get()->titleize($this->name);
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

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'mutable' => $this->mutable,
            'null' => $this->null,
            'required' => $this->required,
            'validate' => $this->validate,
            'default' => $this->default,
            'persisted' => $this->persisted,
            'encrypted' => $this->encrypted,
            'in_array' => $this->in_array,
            'relation' => $this->relation,
            'relation_type' => $this->relation_type,
            'foreign_key' => $this->foreign_key,
            'local_key' => $this->local_key,
            'pivot_tablename' => $this->pivot_tablename,
            'morphs_to' => $this->morphs_to,
            'enum_class' => $this->enum_class,
        ];
    }
}
