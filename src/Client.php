<?php

declare(strict_types=1);

namespace Contazen;

use Contazen\Http\HttpClient;
use Contazen\Resources\Invoices;
use Contazen\Resources\Clients;
use Contazen\Resources\Products;
use Contazen\Resources\EFactura;
use Contazen\Resources\Settings;
use Contazen\Resources\Webhooks;
use Contazen\Resources\Series;
use Contazen\Resources\Payments;
use Contazen\Resources\Documents;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Contazen API Client
 * 
 * Main entry point for interacting with the Contazen API.
 * 
 * @property-read Invoices $invoices Invoice management
 * @property-read Clients $clients Client/Customer management
 * @property-read Products $products Product catalog management
 * @property-read EFactura $efactura E-Factura/ANAF integration
 * @property-read Settings $settings Firm settings
 * @property-read Webhooks $webhooks Webhook management
 * @property-read Series $series Invoice series management
 * @property-read Payments $payments Payment management
 * @property-read Documents $documents Document management
 */
class Client
{
    public const VERSION = '1.0.0';
    public const DEFAULT_API_URL = 'https://api.contazen.ro/v1';
    
    private HttpClient $http;
    private Config $config;
    
    // Resource instances
    private Invoices $invoices;
    private Clients $clients;
    private Products $products;
    private EFactura $efactura;
    private Settings $settings;
    private Webhooks $webhooks;
    private Series $series;
    private Payments $payments;
    private Documents $documents;
    
    /**
     * Create a new Contazen API client
     * 
     * @param string $apiToken Your Contazen API token
     * @param array $options Configuration options
     * 
     * Options:
     * - api_url: API base URL (default: https://api.contazen.ro/v1)
     * - work_point_id: Default work point ID for multi-tenant access (optional)
     * - firm_id: Default firm ID for requests (deprecated, use work_point_id)
     * - timeout: Request timeout in seconds (default: 30)
     * - retry_attempts: Number of retry attempts (default: 3)
     * - retry_delay: Delay between retries in milliseconds (default: 1000)
     * - verify_ssl: Verify SSL certificates (default: true)
     * - debug: Enable debug mode (default: false)
     * - user_agent: Custom user agent string
     */
    public function __construct(string $apiToken, array $options = [])
    {
        $this->config = new Config(array_merge([
            'api_token' => $apiToken,
            'api_url' => self::DEFAULT_API_URL,
            'firm_id' => null,
            'timeout' => 30,
            'retry_attempts' => 3,
            'retry_delay' => 1000,
            'verify_ssl' => true,
            'debug' => false,
            'user_agent' => 'Contazen-PHP-SDK/' . self::VERSION,
        ], $options));
        
        $this->http = new HttpClient($this->config);
        $this->initializeResources();
    }
    
    /**
     * Create client using fluent builder pattern
     * 
     * @param string $apiToken
     * @return ClientBuilder
     */
    public static function create(string $apiToken): ClientBuilder
    {
        return new ClientBuilder($apiToken);
    }
    
    /**
     * Initialize all resource instances
     */
    private function initializeResources(): void
    {
        $this->invoices = new Invoices($this->http);
        $this->clients = new Clients($this->http);
        $this->products = new Products($this->http);
        $this->efactura = new EFactura($this->http);
        $this->settings = new Settings($this->http);
        $this->webhooks = new Webhooks($this->http);
        $this->series = new Series($this->http);
        $this->payments = new Payments($this->http);
        $this->documents = new Documents($this->http);
    }
    
    /**
     * Magic getter for resource access
     * 
     * @param string $name
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        
        throw new \InvalidArgumentException("Property {$name} does not exist");
    }
    
    /**
     * Set PSR-3 logger for debugging
     * 
     * @param LoggerInterface $logger
     * @return self
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->http->setLogger($logger);
        return $this;
    }
    
    /**
     * Set PSR-16 cache implementation
     * 
     * @param CacheInterface $cache
     * @param int $ttl Default TTL in seconds
     * @return self
     */
    public function setCache(CacheInterface $cache, int $ttl = 300): self
    {
        $this->http->setCache($cache, $ttl);
        return $this;
    }
    
    /**
     * Get configuration
     * 
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
    
    /**
     * Get HTTP client instance
     * 
     * @return HttpClient
     */
    public function getHttpClient(): HttpClient
    {
        return $this->http;
    }
    
    /**
     * Get rate limit information from last request
     * 
     * @return array{limit: int, remaining: int, reset: int}
     */
    public function getRateLimitInfo(): array
    {
        return $this->http->getRateLimitInfo();
    }
    
    /**
     * Test API connection
     * 
     * @return bool
     */
    public function ping(): bool
    {
        try {
            $response = $this->http->get('/ping');
            return $response->isSuccess();
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get API version information
     * 
     * @return array
     */
    public function version(): array
    {
        $response = $this->http->get('/version');
        return $response->toArray();
    }
}