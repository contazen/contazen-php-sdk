<?php

declare(strict_types=1);

namespace Contazen\Http;

use Contazen\Config;
use Contazen\Exceptions\ApiException;
use Contazen\Exceptions\AuthenticationException;
use Contazen\Exceptions\NetworkException;
use Contazen\Exceptions\NotFoundException;
use Contazen\Exceptions\RateLimitException;
use Contazen\Exceptions\ValidationException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * HTTP client wrapper for making API requests
 */
class HttpClient
{
    private Config $config;
    private GuzzleClient $guzzle;
    private ?LoggerInterface $logger = null;
    private ?CacheInterface $cache = null;
    private int $cacheTtl = 300;
    private array $rateLimitInfo = [];
    
    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->initializeGuzzle();
    }
    
    /**
     * Initialize Guzzle client with middleware
     */
    private function initializeGuzzle(): void
    {
        $stack = HandlerStack::create();
        
        // Add retry middleware
        if ($this->config->getRetryAttempts() > 0) {
            $stack->push(Middleware::retry(
                $this->retryDecider(),
                $this->retryDelay()
            ));
        }
        
        // Add logging middleware
        if ($this->config->isDebug() && $this->logger) {
            $stack->push(Middleware::log(
                $this->logger,
                new \GuzzleHttp\MessageFormatter('{method} {uri} {code}')
            ));
        }
        
        // Ensure base_uri ends with a slash
        $baseUri = rtrim($this->config->getApiUrl(), '/') . '/';
        
        $this->guzzle = new GuzzleClient([
            'base_uri' => $baseUri,
            'timeout' => $this->config->getTimeout(),
            'verify' => $this->config->shouldVerifySsl(),
            'handler' => $stack,
            'headers' => [
                'User-Agent' => $this->config->getUserAgent(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }
    
    /**
     * Make GET request
     * 
     * @param string $endpoint
     * @param array $params Query parameters
     * @param array $headers Additional headers
     * @return Response
     */
    public function get(string $endpoint, array $params = [], array $headers = []): Response
    {
        // Check cache for GET requests
        if ($this->cache && empty($headers)) {
            $cacheKey = $this->getCacheKey('GET', $endpoint, $params);
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return Response::fromCache($cached);
            }
        }
        
        $response = $this->request('GET', $endpoint, [
            'query' => $params,
            'headers' => $headers,
        ]);
        
        // Cache successful GET responses
        if ($this->cache && $response->isSuccess() && empty($headers)) {
            $cacheKey = $this->getCacheKey('GET', $endpoint, $params);
            $this->cache->set($cacheKey, $response->toArray(), $this->cacheTtl);
        }
        
        return $response;
    }
    
    /**
     * Make POST request
     * 
     * @param string $endpoint
     * @param array $data Request body
     * @param array $headers Additional headers
     * @return Response
     */
    public function post(string $endpoint, array $data = [], array $headers = []): Response
    {
        return $this->request('POST', $endpoint, [
            'json' => $data,
            'headers' => $headers,
        ]);
    }
    
    /**
     * Make PATCH request
     * 
     * @param string $endpoint
     * @param array $data Request body
     * @param array $headers Additional headers
     * @return Response
     */
    public function patch(string $endpoint, array $data = [], array $headers = []): Response
    {
        return $this->request('PATCH', $endpoint, [
            'json' => $data,
            'headers' => $headers,
        ]);
    }
    
    /**
     * Make PUT request
     * 
     * @param string $endpoint
     * @param array $data Request body
     * @param array $headers Additional headers
     * @return Response
     */
    public function put(string $endpoint, array $data = [], array $headers = []): Response
    {
        return $this->request('PUT', $endpoint, [
            'json' => $data,
            'headers' => $headers,
        ]);
    }
    
    /**
     * Make DELETE request
     * 
     * @param string $endpoint
     * @param array $headers Additional headers
     * @return Response
     */
    public function delete(string $endpoint, array $headers = []): Response
    {
        return $this->request('DELETE', $endpoint, [
            'headers' => $headers,
        ]);
    }
    
    /**
     * Make HTTP request
     * 
     * @param string $method
     * @param string $endpoint
     * @param array $options
     * @return Response
     * @throws ApiException
     * @throws AuthenticationException
     * @throws NetworkException
     * @throws RateLimitException
     * @throws ValidationException
     */
    private function request(string $method, string $endpoint, array $options = []): Response
    {
        // Remove leading slash from endpoint to work with base_uri
        $endpoint = ltrim($endpoint, '/');
        
        // Add authentication header
        $options['headers']['Authorization'] = 'Bearer ' . $this->config->getApiToken();
        $options['headers']['Accept'] = 'application/json';
        $options['headers']['Content-Type'] = 'application/json';
        
        // Add firm_id if configured
        if ($this->config->getFirmId() !== null) {
            if ($method === 'GET') {
                $options['query']['firm_id'] = $this->config->getFirmId();
            } else {
                $options['json']['firm_id'] = $options['json']['firm_id'] ?? $this->config->getFirmId();
            }
        }
        
        try {
            $response = $this->guzzle->request($method, $endpoint, $options);
            $this->extractRateLimitInfo($response);
            return new Response($response);
            
        } catch (ClientException $e) {
            $response = $e->getResponse();
            $this->extractRateLimitInfo($response);
            
            $statusCode = $response->getStatusCode();
            $responseBody = (string) $response->getBody();
            $body = json_decode($responseBody, true);
            
            // If JSON decode failed, create a simple error structure
            if ($body === null && !empty($responseBody)) {
                $body = ['message' => 'Invalid response format', 'raw' => substr($responseBody, 0, 500)];
            }
            
            // Extract error message - handle various formats
            $message = 'Unknown error';
            if (isset($body['message'])) {
                $message = is_string($body['message']) ? $body['message'] : json_encode($body['message']);
            } elseif (isset($body['error'])) {
                $message = is_string($body['error']) ? $body['error'] : json_encode($body['error']);
            } elseif (isset($body['errors'])) {
                // If errors is an array, format it nicely
                if (is_array($body['errors'])) {
                    $errorMessages = [];
                    foreach ($body['errors'] as $field => $fieldErrors) {
                        if (is_array($fieldErrors)) {
                            $errorMessages[] = $field . ': ' . implode(', ', $fieldErrors);
                        } else {
                            $errorMessages[] = $fieldErrors;
                        }
                    }
                    $message = implode('; ', $errorMessages);
                } else {
                    $message = is_string($body['errors']) ? $body['errors'] : json_encode($body['errors']);
                }
            }
            
            switch ($statusCode) {
                case 400:
                    // Bad Request - usually validation errors
                    if (isset($body['errors']) && is_array($body['errors'])) {
                        throw new ValidationException($message, $statusCode, $body['errors']);
                    }
                    throw new ApiException($message, $statusCode, $body);
                case 401:
                    throw new AuthenticationException($message, $statusCode);
                case 404:
                    throw new NotFoundException($message ?: 'Resource not found', $statusCode);
                case 422:
                    throw new ValidationException($message, $statusCode, $body['errors'] ?? []);
                case 429:
                    throw new RateLimitException($message, $statusCode, $this->rateLimitInfo);
                default:
                    throw new ApiException($message, $statusCode, $body);
            }
            
        } catch (ServerException $e) {
            $response = $e->getResponse();
            $responseBody = (string) $response->getBody();
            $body = json_decode($responseBody, true);
            
            // If JSON decode failed, create a simple error structure
            if ($body === null && !empty($responseBody)) {
                $body = ['message' => 'Server error', 'raw' => substr($responseBody, 0, 500)];
            }
            
            // Extract error message - ensure it's a string
            $message = 'Server error';
            if (isset($body['message'])) {
                $message = is_string($body['message']) ? $body['message'] : json_encode($body['message']);
            } elseif (isset($body['error'])) {
                $message = is_string($body['error']) ? $body['error'] : json_encode($body['error']);
            }
            throw new ApiException($message, $response->getStatusCode(), $body);
            
        } catch (ConnectException $e) {
            throw new NetworkException('Network error: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Extract rate limit information from response headers
     * 
     * @param ResponseInterface|null $response
     */
    private function extractRateLimitInfo(?ResponseInterface $response): void
    {
        if (!$response) {
            return;
        }
        
        $this->rateLimitInfo = [
            'limit' => (int) ($response->getHeader('X-RateLimit-Limit')[0] ?? 0),
            'remaining' => (int) ($response->getHeader('X-RateLimit-Remaining')[0] ?? 0),
            'reset' => (int) ($response->getHeader('X-RateLimit-Reset')[0] ?? 0),
        ];
    }
    
    /**
     * Get retry decider function
     * 
     * @return callable
     */
    private function retryDecider(): callable
    {
        return function (
            int $retries,
            \Psr\Http\Message\RequestInterface $request,
            ?ResponseInterface $response = null,
            ?\Exception $exception = null
        ) {
            // Don't retry if we've exceeded max retries
            if ($retries >= $this->config->getRetryAttempts()) {
                return false;
            }
            
            // Retry on network errors
            if ($exception instanceof ConnectException) {
                return true;
            }
            
            // Retry on server errors (5xx) and rate limits (429)
            if ($response) {
                $statusCode = $response->getStatusCode();
                return $statusCode === 429 || ($statusCode >= 500 && $statusCode < 600);
            }
            
            return false;
        };
    }
    
    /**
     * Get retry delay function
     * 
     * @return callable
     */
    private function retryDelay(): callable
    {
        return function (int $retries) {
            // Exponential backoff with jitter
            $delay = $this->config->getRetryDelay() * (2 ** ($retries - 1));
            $jitter = rand(0, 1000);
            return $delay + $jitter;
        };
    }
    
    /**
     * Generate cache key
     * 
     * @param string $method
     * @param string $endpoint
     * @param array $params
     * @return string
     */
    private function getCacheKey(string $method, string $endpoint, array $params): string
    {
        return sprintf(
            'contazen:%s:%s:%s',
            $method,
            $endpoint,
            md5(json_encode($params))
        );
    }
    
    /**
     * Set logger
     * 
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
        $this->initializeGuzzle(); // Reinitialize with logger
    }
    
    /**
     * Set cache
     * 
     * @param CacheInterface $cache
     * @param int $ttl
     */
    public function setCache(CacheInterface $cache, int $ttl = 300): void
    {
        $this->cache = $cache;
        $this->cacheTtl = $ttl;
    }
    
    /**
     * Get rate limit information
     * 
     * @return array
     */
    public function getRateLimitInfo(): array
    {
        return $this->rateLimitInfo;
    }
    
    /**
     * Get config
     * 
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}