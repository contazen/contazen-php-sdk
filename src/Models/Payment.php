<?php

declare(strict_types=1);

namespace Contazen\Models;

/**
 * Payment model
 * 
 * @property string $cz_uid Unique identifier
 * @property string $invoice_cz_uid Invoice reference
 * @property float $amount Payment amount
 * @property string $currency Currency code
 * @property string $payment_method Payment method
 * @property string $status Payment status
 * @property string $payment_date Payment date
 * @property string|null $transaction_id External transaction ID
 * @property string|null $reference Payment reference
 * @property string|null $description Payment description
 * @property array|null $metadata Additional metadata
 * @property string|null $gateway Payment gateway used
 * @property array|null $gateway_response Gateway response data
 * @property string $created_at Creation timestamp
 * @property string|null $updated_at Last update timestamp
 */
class Payment extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
    
    const METHOD_CASH = 'cash';
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_CARD = 'card';
    const METHOD_ONLINE = 'online';
    const METHOD_CHECK = 'check';
    const METHOD_OTHER = 'other';
    
    /**
     * Get payment amount
     * 
     * @return float
     */
    public function getAmount(): float
    {
        return (float) $this->getAttribute('amount', 0);
    }
    
    /**
     * Get currency code
     * 
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->getAttribute('currency', 'RON');
    }
    
    /**
     * Get formatted amount with currency
     * 
     * @return string
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->getAmount(), 2) . ' ' . $this->getCurrency();
    }
    
    /**
     * Get payment method
     * 
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->getAttribute('payment_method', self::METHOD_OTHER);
    }
    
    /**
     * Get payment method label
     * 
     * @return string
     */
    public function getPaymentMethodLabel(): string
    {
        $labels = [
            self::METHOD_CASH => 'Cash',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            self::METHOD_CARD => 'Card',
            self::METHOD_ONLINE => 'Online Payment',
            self::METHOD_CHECK => 'Check',
            self::METHOD_OTHER => 'Other',
        ];
        
        return $labels[$this->getPaymentMethod()] ?? 'Unknown';
    }
    
    /**
     * Get payment status
     * 
     * @return string
     */
    public function getStatus(): string
    {
        return $this->getAttribute('status', self::STATUS_PENDING);
    }
    
    /**
     * Check if payment is pending
     * 
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->getStatus() === self::STATUS_PENDING;
    }
    
    /**
     * Check if payment is processing
     * 
     * @return bool
     */
    public function isProcessing(): bool
    {
        return $this->getStatus() === self::STATUS_PROCESSING;
    }
    
    /**
     * Check if payment is successful
     * 
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->getStatus() === self::STATUS_SUCCESS;
    }
    
    /**
     * Check if payment failed
     * 
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->getStatus() === self::STATUS_FAILED;
    }
    
    /**
     * Check if payment was cancelled
     * 
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->getStatus() === self::STATUS_CANCELLED;
    }
    
    /**
     * Check if payment was refunded
     * 
     * @return bool
     */
    public function isRefunded(): bool
    {
        return in_array($this->getStatus(), [
            self::STATUS_REFUNDED,
            self::STATUS_PARTIALLY_REFUNDED
        ], true);
    }
    
    /**
     * Check if payment was fully refunded
     * 
     * @return bool
     */
    public function isFullyRefunded(): bool
    {
        return $this->getStatus() === self::STATUS_REFUNDED;
    }
    
    /**
     * Check if payment was partially refunded
     * 
     * @return bool
     */
    public function isPartiallyRefunded(): bool
    {
        return $this->getStatus() === self::STATUS_PARTIALLY_REFUNDED;
    }
    
    /**
     * Get payment date
     * 
     * @return string
     */
    public function getPaymentDate(): string
    {
        return $this->getAttribute('payment_date', $this->getAttribute('created_at', ''));
    }
    
    /**
     * Get transaction ID
     * 
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->getAttribute('transaction_id');
    }
    
    /**
     * Get payment reference
     * 
     * @return string|null
     */
    public function getReference(): ?string
    {
        return $this->getAttribute('reference');
    }
    
    /**
     * Get description
     * 
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getAttribute('description');
    }
    
    /**
     * Get invoice CzUid
     * 
     * @return string|null
     */
    public function getInvoiceCzUid(): ?string
    {
        return $this->getAttribute('invoice_cz_uid');
    }
    
    /**
     * Get payment gateway
     * 
     * @return string|null
     */
    public function getGateway(): ?string
    {
        return $this->getAttribute('gateway');
    }
    
    /**
     * Get gateway response data
     * 
     * @return array
     */
    public function getGatewayResponse(): array
    {
        return $this->getAttribute('gateway_response', []);
    }
    
    /**
     * Get metadata
     * 
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->getAttribute('metadata', []);
    }
    
    /**
     * Get metadata value by key
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetadataValue(string $key, $default = null)
    {
        $metadata = $this->getMetadata();
        return $metadata[$key] ?? $default;
    }
    
    /**
     * Check if payment can be refunded
     * 
     * @return bool
     */
    public function canBeRefunded(): bool
    {
        return $this->isSuccessful() && !$this->isFullyRefunded();
    }
    
    /**
     * Check if payment can be cancelled
     * 
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->getStatus(), [
            self::STATUS_PENDING,
            self::STATUS_PROCESSING
        ], true);
    }
    
    /**
     * Get status badge color
     * 
     * @return string
     */
    public function getStatusColor(): string
    {
        $colors = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_PROCESSING => 'info',
            self::STATUS_SUCCESS => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            self::STATUS_REFUNDED => 'dark',
            self::STATUS_PARTIALLY_REFUNDED => 'warning',
        ];
        
        return $colors[$this->getStatus()] ?? 'secondary';
    }
}