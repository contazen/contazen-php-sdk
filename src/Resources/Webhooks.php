<?php

declare(strict_types=1);

namespace Contazen\Resources;

use Contazen\Models\Webhook;

/**
 * Webhooks API resource
 * 
 * Manage webhook subscriptions for real-time events
 */
class Webhooks extends Resource
{
    /**
     * Available webhook events
     */
    public const EVENT_INVOICE_CREATED = 'invoice.created';
    public const EVENT_INVOICE_UPDATED = 'invoice.updated';
    public const EVENT_INVOICE_PAID = 'invoice.paid';
    public const EVENT_INVOICE_CANCELLED = 'invoice.cancelled';
    public const EVENT_INVOICE_SENT = 'invoice.sent';
    
    public const EVENT_CLIENT_CREATED = 'client.created';
    public const EVENT_CLIENT_UPDATED = 'client.updated';
    public const EVENT_CLIENT_DELETED = 'client.deleted';
    
    public const EVENT_PRODUCT_CREATED = 'product.created';
    public const EVENT_PRODUCT_UPDATED = 'product.updated';
    public const EVENT_PRODUCT_DELETED = 'product.deleted';
    public const EVENT_STOCK_UPDATED = 'stock.updated';
    
    public const EVENT_EFACTURA_SENT = 'efactura.sent';
    public const EVENT_EFACTURA_ACCEPTED = 'efactura.accepted';
    public const EVENT_EFACTURA_REJECTED = 'efactura.rejected';
    public const EVENT_EFACTURA_ERROR = 'efactura.error';
    
    public const EVENT_PAYMENT_RECEIVED = 'payment.received';
    public const EVENT_PAYMENT_CANCELLED = 'payment.cancelled';
    
    /**
     * List all registered webhooks
     * 
     * @return array
     */
    public function list(): array
    {
        $response = $this->http->get('/webhooks');
        
        return array_map(
            fn($data) => Webhook::fromArray($data),
            $response->getData()
        );
    }
    
    /**
     * Get a specific webhook
     * 
     * @param string $id Webhook ID
     * @return Webhook
     */
    public function get(string $id): Webhook
    {
        $response = $this->http->get("/webhooks/{$id}");
        return Webhook::fromArray($response->getData());
    }
    
    /**
     * Create a new webhook
     * 
     * @param string $url Webhook endpoint URL
     * @param array $events Events to subscribe to (use EVENT_* constants)
     * @param array $options Additional options:
     *   - secret: string Webhook secret for signature verification
     *   - active: bool Whether webhook is active (default: true)
     *   - description: string Webhook description
     * 
     * @return Webhook
     */
    public function create(string $url, array $events = ['*'], array $options = []): Webhook
    {
        $data = array_merge($options, [
            'url' => $url,
            'events' => $events,
            'active' => $options['active'] ?? true,
        ]);
        
        // Auto-fill firm_id if configured
        if (!isset($data['firm_id']) && $this->http->getConfig()->getFirmId()) {
            $data['firm_id'] = $this->http->getConfig()->getFirmId();
        }
        
        $response = $this->http->post('/webhooks', $data);
        return Webhook::fromArray($response->getData());
    }
    
    /**
     * Update a webhook
     * 
     * @param string $id Webhook ID
     * @param array $data Data to update:
     *   - url: string New URL
     *   - events: array New events list
     *   - active: bool Active status
     *   - secret: string New secret
     *   - description: string New description
     * 
     * @return Webhook
     */
    public function update(string $id, array $data): Webhook
    {
        $response = $this->http->patch("/webhooks/{$id}", $data);
        return Webhook::fromArray($response->getData());
    }
    
    /**
     * Delete a webhook
     * 
     * @param string $id Webhook ID
     * @return bool
     */
    public function delete(string $id): bool
    {
        $response = $this->http->delete("/webhooks/{$id}");
        return $response->isSuccess();
    }
    
    /**
     * Test a webhook by sending a test event
     * 
     * @param string $id Webhook ID
     * @return array Test results
     */
    public function test(string $id): array
    {
        $response = $this->http->post("/webhooks/{$id}/test");
        return $response->getData();
    }
    
    /**
     * Enable a webhook
     * 
     * @param string $id Webhook ID
     * @return Webhook
     */
    public function enable(string $id): Webhook
    {
        return $this->update($id, ['active' => true]);
    }
    
    /**
     * Disable a webhook
     * 
     * @param string $id Webhook ID
     * @return Webhook
     */
    public function disable(string $id): Webhook
    {
        return $this->update($id, ['active' => false]);
    }
    
    /**
     * Get webhook delivery logs
     * 
     * @param string $id Webhook ID
     * @param array $filters Filters:
     *   - page: int Page number
     *   - per_page: int Items per page
     *   - status: string Filter by status (success, failed)
     * 
     * @return array
     */
    public function getLogs(string $id, array $filters = []): array
    {
        $params = $this->buildQueryParams($filters, [
            'page' => 1,
            'per_page' => 50,
        ]);
        
        $response = $this->http->get("/webhooks/{$id}/logs", $params);
        return $response->getData();
    }
    
    /**
     * Retry a failed webhook delivery
     * 
     * @param string $id Webhook ID
     * @param string $logId Delivery log ID
     * @return array
     */
    public function retryDelivery(string $id, string $logId): array
    {
        $response = $this->http->post("/webhooks/{$id}/logs/{$logId}/retry");
        return $response->getData();
    }
    
    /**
     * Verify webhook signature
     * 
     * @param string $payload Webhook payload (raw body)
     * @param string $signature Signature from webhook header
     * @param string $secret Webhook secret
     * @return bool
     */
    public static function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
    
    /**
     * Parse webhook payload
     * 
     * @param string $payload JSON payload
     * @return array
     */
    public static function parsePayload(string $payload): array
    {
        $data = json_decode($payload, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid webhook payload: ' . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Get all available webhook events
     * 
     * @return array
     */
    public static function getAvailableEvents(): array
    {
        return [
            self::EVENT_INVOICE_CREATED,
            self::EVENT_INVOICE_UPDATED,
            self::EVENT_INVOICE_PAID,
            self::EVENT_INVOICE_CANCELLED,
            self::EVENT_INVOICE_SENT,
            self::EVENT_CLIENT_CREATED,
            self::EVENT_CLIENT_UPDATED,
            self::EVENT_CLIENT_DELETED,
            self::EVENT_PRODUCT_CREATED,
            self::EVENT_PRODUCT_UPDATED,
            self::EVENT_PRODUCT_DELETED,
            self::EVENT_STOCK_UPDATED,
            self::EVENT_EFACTURA_SENT,
            self::EVENT_EFACTURA_ACCEPTED,
            self::EVENT_EFACTURA_REJECTED,
            self::EVENT_EFACTURA_ERROR,
            self::EVENT_PAYMENT_RECEIVED,
            self::EVENT_PAYMENT_CANCELLED,
        ];
    }
}