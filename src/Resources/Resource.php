<?php

declare(strict_types=1);

namespace Contazen\Resources;

use Contazen\Http\HttpClient;

/**
 * Base class for all API resources
 */
abstract class Resource
{
    protected HttpClient $http;
    
    /**
     * @param HttpClient $http
     */
    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }
    
    /**
     * Prepare data for API request
     * Removes null values and empty arrays
     * 
     * @param array $data
     * @return array
     */
    protected function prepareData(array $data): array
    {
        return array_filter($data, function ($value) {
            return $value !== null && $value !== [];
        });
    }
    
    /**
     * Build query parameters
     * 
     * @param array $filters
     * @param array $defaults
     * @return array
     */
    protected function buildQueryParams(array $filters, array $defaults = []): array
    {
        $params = array_merge($defaults, $filters);
        
        // Remove null values
        $params = array_filter($params, function ($value) {
            return $value !== null;
        });
        
        // Convert boolean values to strings
        foreach ($params as $key => $value) {
            if (is_bool($value)) {
                $params[$key] = $value ? '1' : '0';
            }
        }
        
        return $params;
    }
    
    /**
     * Validate required fields
     * 
     * @param array $data
     * @param array $required
     * @throws \InvalidArgumentException
     */
    protected function validateRequired(array $data, array $required): void
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                throw new \InvalidArgumentException("Field '{$field}' is required");
            }
        }
    }
    
    /**
     * Get resource endpoint
     * Override in child classes if needed
     * 
     * @return string
     */
    protected function getEndpoint(): string
    {
        // Convert class name to endpoint
        // e.g., Invoices -> /invoices
        $className = (new \ReflectionClass($this))->getShortName();
        return '/' . strtolower($className);
    }
}