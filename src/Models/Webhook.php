<?php

declare(strict_types=1);

namespace Contazen\Models;

/**
 * Webhook configuration model
 * 
 * @property string $cz_uid Unique identifier
 * @property string $url Webhook endpoint URL
 * @property array $events List of subscribed events
 * @property bool $is_active Active status
 * @property string|null $secret Webhook secret for signature validation
 * @property array|null $headers Custom headers to send
 * @property int $retry_count Number of retries on failure
 * @property array|null $last_error Last error details
 * @property string|null $last_triggered_at Last trigger timestamp
 * @property string $created_at Creation timestamp
 * @property string|null $updated_at Last update timestamp
 */
class Webhook extends Model
{
    // Common webhook events
    const EVENT_INVOICE_CREATED = 'invoice.created';
    const EVENT_INVOICE_UPDATED = 'invoice.updated';
    const EVENT_INVOICE_PAID = 'invoice.paid';
    const EVENT_INVOICE_CANCELLED = 'invoice.cancelled';
    const EVENT_INVOICE_SENT = 'invoice.sent';
    
    const EVENT_CLIENT_CREATED = 'client.created';
    const EVENT_CLIENT_UPDATED = 'client.updated';
    const EVENT_CLIENT_DELETED = 'client.deleted';
    
    const EVENT_PRODUCT_CREATED = 'product.created';
    const EVENT_PRODUCT_UPDATED = 'product.updated';
    const EVENT_PRODUCT_DELETED = 'product.deleted';
    const EVENT_PRODUCT_STOCK_LOW = 'product.stock_low';
    
    const EVENT_PAYMENT_RECEIVED = 'payment.received';
    const EVENT_PAYMENT_FAILED = 'payment.failed';
    const EVENT_PAYMENT_REFUNDED = 'payment.refunded';
    
    const EVENT_EFACTURA_SENT = 'efactura.sent';
    const EVENT_EFACTURA_ACCEPTED = 'efactura.accepted';
    const EVENT_EFACTURA_REJECTED = 'efactura.rejected';
    const EVENT_EFACTURA_ERROR = 'efactura.error';
    
    /**
     * Get webhook URL
     * 
     * @return string
     */
    public function getUrl(): string
    {
        return $this->getAttribute('url', '');
    }
    
    /**
     * Get subscribed events
     * 
     * @return array
     */
    public function getEvents(): array
    {
        return $this->getAttribute('events', []);
    }
    
    /**
     * Check if webhook listens to specific event
     * 
     * @param string $event
     * @return bool
     */
    public function hasEvent(string $event): bool
    {
        return in_array($event, $this->getEvents(), true);
    }
    
    /**
     * Check if webhook listens to all events
     * 
     * @return bool
     */
    public function listenToAllEvents(): bool
    {
        return in_array('*', $this->getEvents(), true);
    }
    
    /**
     * Check if webhook is active
     * 
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->getAttribute('is_active', true);
    }
    
    /**
     * Get webhook secret
     * 
     * @return string|null
     */
    public function getSecret(): ?string
    {
        return $this->getAttribute('secret');
    }
    
    /**
     * Get custom headers
     * 
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->getAttribute('headers', []);
    }
    
    /**
     * Get retry count
     * 
     * @return int
     */
    public function getRetryCount(): int
    {
        return (int) $this->getAttribute('retry_count', 3);
    }
    
    /**
     * Check if webhook has recent errors
     * 
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->getAttribute('last_error'));
    }
    
    /**
     * Get last error details
     * 
     * @return array|null
     */
    public function getLastError(): ?array
    {
        return $this->getAttribute('last_error');
    }
    
    /**
     * Get last error message
     * 
     * @return string|null
     */
    public function getLastErrorMessage(): ?string
    {
        $error = $this->getLastError();
        return $error['message'] ?? null;
    }
    
    /**
     * Get last triggered timestamp
     * 
     * @return string|null
     */
    public function getLastTriggeredAt(): ?string
    {
        return $this->getAttribute('last_triggered_at');
    }
    
    /**
     * Check if webhook was recently triggered
     * 
     * @param int $seconds Seconds to consider as recent
     * @return bool
     */
    public function wasRecentlyTriggered(int $seconds = 60): bool
    {
        $lastTriggered = $this->getLastTriggeredAt();
        
        if (!$lastTriggered) {
            return false;
        }
        
        $timestamp = strtotime($lastTriggered);
        return $timestamp && (time() - $timestamp) < $seconds;
    }
    
    /**
     * Generate signature for payload
     * 
     * @param string $payload
     * @return string|null
     */
    public function generateSignature(string $payload): ?string
    {
        $secret = $this->getSecret();
        
        if (!$secret) {
            return null;
        }
        
        return hash_hmac('sha256', $payload, $secret);
    }
    
    /**
     * Validate signature
     * 
     * @param string $payload
     * @param string $signature
     * @return bool
     */
    public function validateSignature(string $payload, string $signature): bool
    {
        $expected = $this->generateSignature($payload);
        
        if (!$expected) {
            return false;
        }
        
        return hash_equals($expected, $signature);
    }
    
    /**
     * Get webhook description
     * 
     * @return string
     */
    public function getDescription(): string
    {
        $events = $this->getEvents();
        
        if ($this->listenToAllEvents()) {
            return 'All events';
        }
        
        if (count($events) === 1) {
            return $events[0];
        }
        
        return count($events) . ' events';
    }
    
    /**
     * Get status label
     * 
     * @return string
     */
    public function getStatusLabel(): string
    {
        if (!$this->isActive()) {
            return 'Inactive';
        }
        
        if ($this->hasErrors()) {
            return 'Error';
        }
        
        if ($this->wasRecentlyTriggered()) {
            return 'Active';
        }
        
        return 'Idle';
    }
}