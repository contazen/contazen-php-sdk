<?php

declare(strict_types=1);

namespace Contazen\Collections;

use Contazen\Http\Response;
use Iterator;
use Countable;
use ArrayAccess;

/**
 * Base collection class for handling lists of models
 */
abstract class Collection implements Iterator, Countable, ArrayAccess
{
    protected array $items = [];
    protected array $metadata = [];
    protected int $position = 0;
    
    /**
     * Create collection from API response
     * 
     * @param Response $response
     * @return static
     */
    public static function fromResponse(Response $response): self
    {
        $collection = new static();
        
        $data = $response->toArray();
        
        // Handle Contazen API response structure
        // The API returns data in data.data structure for lists
        if (isset($data['data']) && is_array($data['data'])) {
            $responseData = $data['data'];
            
            // Check if this is a paginated list response
            if (isset($responseData['data']) && is_array($responseData['data'])) {
                // This is a list response with nested data.data structure
                $collection->items = array_map(
                    fn($item) => static::createItem($item),
                    $responseData['data']
                );
                
                // Store pagination metadata from the nested response
                $collection->metadata = [
                    'has_more' => $responseData['has_more'] ?? false,
                    'total' => $responseData['total'] ?? count($collection->items),
                    'page' => $responseData['page'] ?? 1,
                    'per_page' => $responseData['per_page'] ?? 50,
                    'total_pages' => $responseData['total_pages'] ?? 1,
                ];
            } else {
                // This is a single response wrapped in data
                $collection->items = array_map(
                    fn($item) => static::createItem($item),
                    $data['data']
                );
            }
        } else {
            // Handle simple array response (shouldn't happen with Contazen API)
            $collection->items = array_map(
                fn($item) => static::createItem($item),
                $response->getData()
            );
        }
        
        return $collection;
    }
    
    /**
     * Create item instance from data
     * Override in child classes
     * 
     * @param array $data
     * @return mixed
     */
    abstract protected static function createItem(array $data);
    
    /**
     * Get all items
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }
    
    /**
     * Get first item
     * 
     * @return mixed|null
     */
    public function first()
    {
        return $this->items[0] ?? null;
    }
    
    /**
     * Get last item
     * 
     * @return mixed|null
     */
    public function last()
    {
        if (empty($this->items)) {
            return null;
        }
        
        return $this->items[count($this->items) - 1];
    }
    
    /**
     * Check if collection is empty
     * 
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }
    
    /**
     * Check if collection has more pages
     * 
     * @return bool
     */
    public function hasMore(): bool
    {
        return $this->metadata['has_more'] ?? false;
    }
    
    /**
     * Check if collection has next page
     * 
     * @return bool
     */
    public function hasNextPage(): bool
    {
        return $this->hasMore();
    }
    
    /**
     * Get total count (including items not in current page)
     * 
     * @return int
     */
    public function getTotalCount(): int
    {
        return $this->metadata['total'] ?? count($this->items);
    }
    
    /**
     * Get current page number
     * 
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->metadata['page'] ?? 1;
    }
    
    /**
     * Get items per page
     * 
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->metadata['per_page'] ?? count($this->items);
    }
    
    /**
     * Get total pages
     * 
     * @return int
     */
    public function getTotalPages(): int
    {
        return $this->metadata['total_pages'] ?? 1;
    }
    
    /**
     * Get metadata
     * 
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
    
    /**
     * Map over items
     * 
     * @param callable $callback
     * @return array
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }
    
    /**
     * Filter items
     * 
     * @param callable $callback
     * @return array
     */
    public function filter(callable $callback): array
    {
        return array_filter($this->items, $callback);
    }
    
    /**
     * Find item by callback
     * 
     * @param callable $callback
     * @return mixed|null
     */
    public function find(callable $callback)
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }
        
        return null;
    }
    
    /**
     * Convert collection to array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return array_map(function ($item) {
            if (method_exists($item, 'toArray')) {
                return $item->toArray();
            }
            return $item;
        }, $this->items);
    }
    
    // Iterator interface implementation
    
    public function rewind(): void
    {
        $this->position = 0;
    }
    
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->items[$this->position];
    }
    
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->position;
    }
    
    public function next(): void
    {
        ++$this->position;
    }
    
    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }
    
    // Countable interface implementation
    
    public function count(): int
    {
        return count($this->items);
    }
    
    // ArrayAccess interface implementation
    
    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }
    
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->items[$offset] ?? null;
    }
    
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }
    
    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }
}