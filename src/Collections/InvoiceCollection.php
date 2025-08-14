<?php

declare(strict_types=1);

namespace Contazen\Collections;

use Contazen\Models\Invoice;

/**
 * Collection of Invoice models
 */
class InvoiceCollection extends Collection
{
    /**
     * Create Invoice instance from data
     * 
     * @param array $data
     * @return Invoice
     */
    protected static function createItem(array $data): Invoice
    {
        return Invoice::fromArray($data);
    }
    
    /**
     * Get total amount of all invoices
     * 
     * @return float
     */
    public function getTotalAmount(): float
    {
        return array_reduce($this->items, function ($total, Invoice $invoice) {
            return $total + $invoice->getAttribute('total', 0);
        }, 0.0);
    }
    
    /**
     * Get paid invoices
     * 
     * @return array
     */
    public function getPaid(): array
    {
        return $this->filter(fn(Invoice $invoice) => $invoice->isPaid());
    }
    
    /**
     * Get unpaid invoices
     * 
     * @return array
     */
    public function getUnpaid(): array
    {
        return $this->filter(fn(Invoice $invoice) => !$invoice->isPaid());
    }
    
    /**
     * Get overdue invoices
     * 
     * @return array
     */
    public function getOverdue(): array
    {
        return $this->filter(fn(Invoice $invoice) => $invoice->isOverdue());
    }
    
    /**
     * Get invoices by status
     * 
     * @param string $status
     * @return array
     */
    public function getByStatus(string $status): array
    {
        return $this->filter(fn(Invoice $invoice) => $invoice->getStatus() === $status);
    }
    
    /**
     * Get invoices by client
     * 
     * @param string $clientCzUid
     * @return array
     */
    public function getByClient(string $clientCzUid): array
    {
        return $this->filter(function (Invoice $invoice) use ($clientCzUid) {
            $client = $invoice->getClient();
            return $client && $client->cz_uid === $clientCzUid;
        });
    }
    
    /**
     * Sort by date
     * 
     * @param string $order asc|desc
     * @return array
     */
    public function sortByDate(string $order = 'desc'): array
    {
        $items = $this->items;
        
        usort($items, function (Invoice $a, Invoice $b) use ($order) {
            $dateA = $a->getAttribute('date');
            $dateB = $b->getAttribute('date');
            
            if ($order === 'asc') {
                return $dateA <=> $dateB;
            }
            
            return $dateB <=> $dateA;
        });
        
        return $items;
    }
    
    /**
     * Sort by total amount
     * 
     * @param string $order asc|desc
     * @return array
     */
    public function sortByTotal(string $order = 'desc'): array
    {
        $items = $this->items;
        
        usort($items, function (Invoice $a, Invoice $b) use ($order) {
            $totalA = $a->getAttribute('total', 0);
            $totalB = $b->getAttribute('total', 0);
            
            if ($order === 'asc') {
                return $totalA <=> $totalB;
            }
            
            return $totalB <=> $totalA;
        });
        
        return $items;
    }
}