<?php

declare(strict_types=1);

namespace Contazen;

/**
 * Configuration container for the Contazen API client
 */
class Config
{
    private array $options;
    
    /**
     * @param array $options Configuration options
     */
    public function __construct(array $options)
    {
        // Set defaults
        $this->options = array_merge([
            'api_url' => 'https://api.contazen.ro/v1',
            'timeout' => 30,
            'retry_attempts' => 3,
            'retry_delay' => 1000,
            'verify_ssl' => true,
            'debug' => false,
            'user_agent' => 'Contazen-PHP-SDK/1.0.0',
        ], $options);
        
        $this->validate();
    }
    
    /**
     * Validate configuration
     * 
     * @throws \InvalidArgumentException
     */
    private function validate(): void
    {
        if (empty($this->options['api_token'])) {
            throw new \InvalidArgumentException('API token is required');
        }
        
        if (!empty($this->options['api_url']) && !filter_var($this->options['api_url'], FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Invalid API URL');
        }
        
        if ($this->options['timeout'] < 1) {
            throw new \InvalidArgumentException('Timeout must be at least 1 second');
        }
        
        if ($this->options['retry_attempts'] < 0) {
            throw new \InvalidArgumentException('Retry attempts cannot be negative');
        }
    }
    
    /**
     * Get API token
     * 
     * @return string
     */
    public function getApiToken(): string
    {
        return $this->options['api_token'];
    }
    
    /**
     * Get API base URL
     * 
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->options['api_url'];
    }
    
    /**
     * Get firm ID
     * 
     * @return int|null
     * @deprecated Use getWorkPointId() instead
     */
    public function getFirmId(): ?int
    {
        return $this->options['firm_id'] ?? null;
    }
    
    /**
     * Get work point ID
     * 
     * @return string|null
     */
    public function getWorkPointId(): ?string
    {
        return $this->options['work_point_id'] ?? null;
    }
    
    /**
     * Get request timeout
     * 
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->options['timeout'];
    }
    
    /**
     * Get retry attempts
     * 
     * @return int
     */
    public function getRetryAttempts(): int
    {
        return $this->options['retry_attempts'];
    }
    
    /**
     * Get retry delay in milliseconds
     * 
     * @return int
     */
    public function getRetryDelay(): int
    {
        return $this->options['retry_delay'];
    }
    
    /**
     * Should verify SSL certificates
     * 
     * @return bool
     */
    public function shouldVerifySsl(): bool
    {
        return $this->options['verify_ssl'];
    }
    
    /**
     * Is debug mode enabled
     * 
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->options['debug'];
    }
    
    /**
     * Get user agent string
     * 
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->options['user_agent'];
    }
    
    /**
     * Get all options
     * 
     * @return array
     */
    public function toArray(): array
    {
        return $this->options;
    }
}