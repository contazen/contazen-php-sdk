<?php

declare(strict_types=1);

namespace Contazen;

/**
 * Fluent builder for creating Contazen API client instances
 * 
 * Example:
 * ```php
 * $client = Contazen\Client::create('your-api-token')
 *     ->withFirmId(123)
 *     ->withDebug()
 *     ->withRetries(3, 2000)
 *     ->build();
 * ```
 */
class ClientBuilder
{
    private string $apiToken;
    private array $options = [];
    
    /**
     * @param string $apiToken Contazen API token
     */
    public function __construct(string $apiToken)
    {
        $this->apiToken = $apiToken;
    }
    
    /**
     * Set the work point ID for multi-tenant access
     * 
     * @param string $workPointId
     * @return self
     */
    public function withWorkPointId(string $workPointId): self
    {
        $this->options['work_point_id'] = $workPointId;
        return $this;
    }
    
    /**
     * Set the firm ID for all requests
     * 
     * @param int $firmId
     * @return self
     * @deprecated Use withWorkPointId() instead
     */
    public function withFirmId(int $firmId): self
    {
        $this->options['firm_id'] = $firmId;
        return $this;
    }
    
    /**
     * Set custom API URL (for testing or self-hosted instances)
     * 
     * @param string $url
     * @return self
     */
    public function withApiUrl(string $url): self
    {
        $this->options['api_url'] = rtrim($url, '/');
        return $this;
    }
    
    /**
     * Set request timeout
     * 
     * @param int $seconds
     * @return self
     */
    public function withTimeout(int $seconds): self
    {
        $this->options['timeout'] = $seconds;
        return $this;
    }
    
    /**
     * Configure retry behavior
     * 
     * @param int $attempts Number of retry attempts
     * @param int $delayMs Delay between retries in milliseconds
     * @return self
     */
    public function withRetries(int $attempts, int $delayMs = 1000): self
    {
        $this->options['retry_attempts'] = $attempts;
        $this->options['retry_delay'] = $delayMs;
        return $this;
    }
    
    /**
     * Enable debug mode
     * 
     * @param bool $debug
     * @return self
     */
    public function withDebug(bool $debug = true): self
    {
        $this->options['debug'] = $debug;
        return $this;
    }
    
    /**
     * Disable SSL verification (not recommended for production)
     * 
     * @return self
     */
    public function withoutSslVerification(): self
    {
        $this->options['verify_ssl'] = false;
        return $this;
    }
    
    /**
     * Set custom user agent
     * 
     * @param string $userAgent
     * @return self
     */
    public function withUserAgent(string $userAgent): self
    {
        $this->options['user_agent'] = $userAgent;
        return $this;
    }
    
    /**
     * Build the client instance
     * 
     * @return Client
     */
    public function build(): Client
    {
        return new Client($this->apiToken, $this->options);
    }
}