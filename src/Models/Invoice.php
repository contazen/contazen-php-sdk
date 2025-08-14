<?php

declare(strict_types=1);

namespace Contazen\Models;

use Carbon\Carbon;
use Money\Money;
use Money\Currency;

/**
 * Invoice model
 * 
 * @property string $cz_uid Unique identifier
 * @property string $number Invoice number
 * @property string $series Invoice series
 * @property string $document_type Document type (fiscal, proforma)
 * @property string $status Status (draft, sent, paid, overdue, cancelled)
 * @property Carbon $date Invoice date
 * @property Carbon $due_date Due date
 * @property Client $client Client information
 * @property array $items Invoice line items
 * @property float $subtotal Subtotal amount
 * @property float $tax Tax amount
 * @property float $total Total amount
 * @property string $currency Currency code
 * @property float $exchange_rate Exchange rate
 * @property string $payment_method Payment method
 * @property string $notes Notes
 * @property bool $is_paid Payment status
 * @property Carbon|null $paid_at Payment date
 * @property string|null $efactura_status E-Factura status
 * @property string|null $efactura_id E-Factura ID
 * @property array $metadata Additional metadata
 * @property Carbon $created_at Creation date
 * @property Carbon $updated_at Last update date
 */
class Invoice extends Model
{
    /**
     * Document types
     */
    public const TYPE_FISCAL = 'fiscal';
    public const TYPE_PROFORMA = 'proforma';
    public const TYPE_RECEIPT = 'receipt';
    public const TYPE_CREDIT_NOTE = 'credit_note';
    
    /**
     * Invoice statuses
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';
    
    /**
     * E-Factura statuses
     */
    public const EFACTURA_NOT_SENT = 'not_sent';
    public const EFACTURA_PENDING = 'pending';
    public const EFACTURA_SENT = 'sent';
    public const EFACTURA_ACCEPTED = 'accepted';
    public const EFACTURA_REJECTED = 'rejected';
    public const EFACTURA_ERROR = 'error';
    
    /**
     * Mutate date attribute to Carbon instance
     * 
     * @param mixed $value
     * @return Carbon|null
     */
    protected function mutateDateAttribute($value): ?Carbon
    {
        if ($value === null) {
            return null;
        }
        
        if ($value instanceof Carbon) {
            return $value;
        }
        
        return Carbon::parse($value);
    }
    
    /**
     * Mutate due_date attribute to Carbon instance
     * 
     * @param mixed $value
     * @return Carbon|null
     */
    protected function mutateDueDateAttribute($value): ?Carbon
    {
        return $this->mutateDateAttribute($value);
    }
    
    /**
     * Mutate paid_at attribute to Carbon instance
     * 
     * @param mixed $value
     * @return Carbon|null
     */
    protected function mutatePaidAtAttribute($value): ?Carbon
    {
        return $this->mutateDateAttribute($value);
    }
    
    /**
     * Mutate created_at attribute to Carbon instance
     * 
     * @param mixed $value
     * @return Carbon|null
     */
    protected function mutateCreatedAtAttribute($value): ?Carbon
    {
        return $this->mutateDateAttribute($value);
    }
    
    /**
     * Mutate updated_at attribute to Carbon instance
     * 
     * @param mixed $value
     * @return Carbon|null
     */
    protected function mutateUpdatedAtAttribute($value): ?Carbon
    {
        return $this->mutateDateAttribute($value);
    }
    
    /**
     * Mutate client attribute to Client model
     * 
     * @param mixed $value
     * @return Client|null
     */
    protected function mutateClientAttribute($value): ?Client
    {
        if ($value === null) {
            return null;
        }
        
        if ($value instanceof Client) {
            return $value;
        }
        
        if (is_array($value)) {
            return Client::fromArray($value);
        }
        
        return null;
    }
    
    /**
     * Mutate items attribute to InvoiceItem models
     * 
     * @param mixed $value
     * @return array
     */
    protected function mutateItemsAttribute($value): array
    {
        if (!is_array($value)) {
            return [];
        }
        
        return array_map(function ($item) {
            if ($item instanceof InvoiceItem) {
                return $item;
            }
            return InvoiceItem::fromArray($item);
        }, $value);
    }
    
    /**
     * Get CzUid
     * 
     * @return string|null
     */
    public function getCzUid(): ?string
    {
        // The API uses 'id' instead of 'cz_uid' for invoices
        return $this->getAttribute('id') ?? $this->getAttribute('cz_uid');
    }
    
    /**
     * Get invoice number
     * 
     * @return string|null
     */
    public function getNumber(): ?string
    {
        return $this->getAttribute('number');
    }
    
    /**
     * Get full invoice number (series + number)
     * 
     * @return string
     */
    public function getFullNumber(): string
    {
        $series = $this->getAttribute('series', '');
        $number = $this->getAttribute('number', '');
        
        if ($series && $number) {
            return "{$series}-{$number}";
        }
        
        return $number ?: '';
    }
    
    /**
     * Get document type
     * 
     * @return string
     */
    public function getDocumentType(): string
    {
        return $this->getAttribute('document_type', self::TYPE_FISCAL);
    }
    
    /**
     * Check if invoice is fiscal
     * 
     * @return bool
     */
    public function isFiscal(): bool
    {
        return $this->getDocumentType() === self::TYPE_FISCAL;
    }
    
    /**
     * Check if invoice is proforma
     * 
     * @return bool
     */
    public function isProforma(): bool
    {
        return $this->getDocumentType() === self::TYPE_PROFORMA;
    }
    
    /**
     * Get status
     * 
     * @return string
     */
    public function getStatus(): string
    {
        return $this->getAttribute('status', self::STATUS_DRAFT);
    }
    
    /**
     * Check if invoice is paid
     * 
     * @return bool
     */
    public function isPaid(): bool
    {
        return $this->getAttribute('is_paid', false) || 
               $this->getStatus() === self::STATUS_PAID;
    }
    
    /**
     * Check if invoice is overdue
     * 
     * @return bool
     */
    public function isOverdue(): bool
    {
        if ($this->isPaid()) {
            return false;
        }
        
        $dueDate = $this->getAttribute('due_date');
        if (!$dueDate instanceof Carbon) {
            return false;
        }
        
        return $dueDate->isPast();
    }
    
    /**
     * Check if invoice is cancelled
     * 
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->getStatus() === self::STATUS_CANCELLED;
    }
    
    /**
     * Get total as Money object
     * 
     * @return Money
     */
    public function getTotalMoney(): Money
    {
        $amount = (int) (($this->getAttribute('total', 0) * 100));
        $currency = new Currency($this->getAttribute('currency', 'RON'));
        return new Money($amount, $currency);
    }
    
    /**
     * Get formatted total
     * 
     * @return string
     */
    public function getFormattedTotal(): string
    {
        $total = $this->getAttribute('total', 0);
        $currency = $this->getAttribute('currency', 'RON');
        return number_format($total, 2) . ' ' . $currency;
    }
    
    /**
     * Get client
     * 
     * @return Client|null
     */
    public function getClient(): ?Client
    {
        return $this->getAttribute('client');
    }
    
    /**
     * Get items
     * 
     * @return array
     */
    public function getItems(): array
    {
        return $this->getAttribute('items', []);
    }
    
    /**
     * Add item to invoice
     * 
     * @param InvoiceItem|array $item
     * @return self
     */
    public function addItem($item): self
    {
        if (is_array($item)) {
            $item = InvoiceItem::fromArray($item);
        }
        
        $items = $this->getItems();
        $items[] = $item;
        $this->setAttribute('items', $items);
        
        return $this;
    }
    
    /**
     * Calculate totals
     * 
     * @return self
     */
    public function calculateTotals(): self
    {
        $subtotal = 0;
        $tax = 0;
        
        foreach ($this->getItems() as $item) {
            if ($item instanceof InvoiceItem) {
                $subtotal += $item->getSubtotal();
                $tax += $item->getTax();
            }
        }
        
        $this->setAttribute('subtotal', $subtotal);
        $this->setAttribute('tax', $tax);
        $this->setAttribute('total', $subtotal + $tax);
        
        return $this;
    }
    
    /**
     * Get E-Factura status
     * 
     * @return string|null
     */
    public function getEfacturaStatus(): ?string
    {
        return $this->getAttribute('efactura_status');
    }
    
    /**
     * Check if invoice was sent to ANAF
     * 
     * @return bool
     */
    public function isSentToAnaf(): bool
    {
        $status = $this->getEfacturaStatus();
        return $status && $status !== self::EFACTURA_NOT_SENT;
    }
    
    /**
     * Get days until due
     * 
     * @return int|null
     */
    public function getDaysUntilDue(): ?int
    {
        $dueDate = $this->getAttribute('due_date');
        if (!$dueDate instanceof Carbon) {
            return null;
        }
        
        return Carbon::now()->diffInDays($dueDate, false);
    }
    
    /**
     * Get payment URL if available
     * 
     * @return string|null
     */
    public function getPaymentUrl(): ?string
    {
        return $this->getAttribute('payment_url');
    }
    
    /**
     * Convert to array for API
     * 
     * @return array
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        
        // Convert Carbon instances to strings
        foreach (['date', 'due_date', 'paid_at', 'created_at', 'updated_at'] as $field) {
            if (isset($data[$field]) && $data[$field] instanceof Carbon) {
                $data[$field] = $data[$field]->format('Y-m-d');
            }
        }
        
        // Convert Client model to array
        if (isset($data['client']) && $data['client'] instanceof Client) {
            $data['client'] = $data['client']->toArray();
        }
        
        // Convert InvoiceItem models to arrays
        if (isset($data['items']) && is_array($data['items'])) {
            $data['items'] = array_map(function ($item) {
                return $item instanceof InvoiceItem ? $item->toArray() : $item;
            }, $data['items']);
        }
        
        return $data;
    }
}