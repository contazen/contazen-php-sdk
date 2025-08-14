<?php

declare(strict_types=1);

namespace Contazen\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * HTTP Response wrapper
 */
class Response
{
    private ?ResponseInterface $response;
    private array $data;
    private bool $fromCache;
    
    /**
     * @param ResponseInterface|null $response
     */
    public function __construct(?ResponseInterface $response = null)
    {
        $this->response = $response;
        $this->fromCache = false;
        
        if ($response) {
            $body = (string) $response->getBody();
            $this->data = !empty($body) ? json_decode($body, true) : [];
        } else {
            $this->data = [];
        }
    }
    
    /**
     * Create response from cached data
     * 
     * @param array $data
     * @return self
     */
    public static function fromCache(array $data): self
    {
        $response = new self(null);
        $response->data = $data;
        $response->fromCache = true;
        return $response;
    }
    
    /**
     * Check if request was successful
     * 
     * @return bool
     */
    public function isSuccess(): bool
    {
        if ($this->fromCache) {
            return true;
        }
        
        if (!$this->response) {
            return false;
        }
        
        $statusCode = $this->response->getStatusCode();
        return $statusCode >= 200 && $statusCode < 300;
    }
    
    /**
     * Get response data
     * 
     * @return array
     */
    public function getData(): array
    {
        return $this->data['data'] ?? $this->data;
    }
    
    /**
     * Get full response array
     * 
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }
    
    /**
     * Get response body as string
     * 
     * @return string
     */
    public function getBody(): string
    {
        if ($this->fromCache) {
            return json_encode($this->data);
        }
        
        return $this->response ? (string) $this->response->getBody() : '';
    }
    
    /**
     * Get status code
     * 
     * @return int
     */
    public function getStatusCode(): int
    {
        if ($this->fromCache) {
            return 200;
        }
        
        return $this->response ? $this->response->getStatusCode() : 0;
    }
    
    /**
     * Get header value
     * 
     * @param string $name
     * @return string|null
     */
    public function getHeader(string $name): ?string
    {
        if (!$this->response) {
            return null;
        }
        
        $header = $this->response->getHeader($name);
        return $header[0] ?? null;
    }
    
    /**
     * Check if response is from cache
     * 
     * @return bool
     */
    public function isFromCache(): bool
    {
        return $this->fromCache;
    }
    
    /**
     * Get pagination metadata if available
     * 
     * @return array|null
     */
    public function getPagination(): ?array
    {
        return $this->data['meta'] ?? $this->data['pagination'] ?? null;
    }
    
    /**
     * Check if response has more pages
     * 
     * @return bool
     */
    public function hasMorePages(): bool
    {
        $pagination = $this->getPagination();
        if (!$pagination) {
            return false;
        }
        
        return ($pagination['current_page'] ?? 0) < ($pagination['last_page'] ?? 0);
    }
}