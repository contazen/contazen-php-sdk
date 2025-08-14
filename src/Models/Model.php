<?php

declare(strict_types=1);

namespace Contazen\Models;

/**
 * Base model class for all API models
 */
abstract class Model
{
    /**
     * Model attributes
     * 
     * @var array
     */
    protected array $attributes = [];
    
    /**
     * Original attributes (for tracking changes)
     * 
     * @var array
     */
    protected array $original = [];
    
    /**
     * Create model instance from array
     * 
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        // Map 'id' to 'cz_uid' for consistency across the SDK
        // The Contazen API uses 'id' but we use 'cz_uid' for consistency
        if (isset($data['id']) && !isset($data['cz_uid'])) {
            $data['cz_uid'] = $data['id'];
        }
        
        $model = new static();
        $model->fill($data);
        $model->syncOriginal();
        return $model;
    }
    
    /**
     * Fill model with data
     * 
     * @param array $data
     * @return self
     */
    public function fill(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }
    
    /**
     * Get attribute value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute(string $key, $default = null)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
        
        // Check for getter method
        $getter = 'get' . $this->studly($key);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        
        return $default;
    }
    
    /**
     * Set attribute value
     * 
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setAttribute(string $key, $value): self
    {
        // Check for setter method
        $setter = 'set' . $this->studly($key);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
            return $this;
        }
        
        // Check for mutator
        $mutator = 'mutate' . $this->studly($key) . 'Attribute';
        if (method_exists($this, $mutator)) {
            $value = $this->$mutator($value);
        }
        
        $this->attributes[$key] = $value;
        return $this;
    }
    
    /**
     * Check if attribute exists
     * 
     * @param string $key
     * @return bool
     */
    public function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }
    
    /**
     * Get all attributes
     * 
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    
    /**
     * Sync original attributes with current
     * 
     * @return self
     */
    public function syncOriginal(): self
    {
        $this->original = $this->attributes;
        return $this;
    }
    
    /**
     * Get original attribute value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getOriginal(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->original;
        }
        
        return $this->original[$key] ?? $default;
    }
    
    /**
     * Check if model has been modified
     * 
     * @param string|null $key Check specific attribute
     * @return bool
     */
    public function isDirty(?string $key = null): bool
    {
        if ($key === null) {
            return $this->attributes !== $this->original;
        }
        
        return ($this->attributes[$key] ?? null) !== ($this->original[$key] ?? null);
    }
    
    /**
     * Get changed attributes
     * 
     * @return array
     */
    public function getChanges(): array
    {
        $changes = [];
        
        foreach ($this->attributes as $key => $value) {
            if ($this->isDirty($key)) {
                $changes[$key] = $value;
            }
        }
        
        return $changes;
    }
    
    /**
     * Convert model to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
    
    /**
     * Convert model to JSON
     * 
     * @param int $options
     * @return string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
    
    /**
     * Magic getter
     * 
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }
    
    /**
     * Magic setter
     * 
     * @param string $key
     * @param mixed $value
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Magic isset
     * 
     * @param string $key
     * @return bool
     */
    public function __isset(string $key): bool
    {
        return $this->hasAttribute($key);
    }
    
    /**
     * Convert to string
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
    
    /**
     * Convert string to studly case
     * 
     * @param string $value
     * @return string
     */
    protected function studly(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return str_replace(' ', '', $value);
    }
    
    /**
     * Convert string to snake case
     * 
     * @param string $value
     * @return string
     */
    protected function snake(string $value): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }
}