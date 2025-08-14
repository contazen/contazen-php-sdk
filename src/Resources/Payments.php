<?php

declare(strict_types=1);

namespace Contazen\Resources;

use Contazen\Models\Payment;

/**
 * Payments API resource
 * 
 * Manage invoice payments and payment tracking
 */
class Payments extends Resource
{
    /**
     * List payments with optional filters
     * 
     * @param array $filters Available filters:
     *   - page: int Page number (default: 1)
     *   - per_page: int Items per page (default: 50, max: 100)
     *   - invoice_id: string Filter by invoice CzUid
     *   - client_id: string Filter by client CzUid
     *   - start_date: string Start date (Y-m-d)
     *   - end_date: string End date (Y-m-d)
     *   - method: string Payment method
     *   - sort: string Sort field (date, amount)
     *   - order: string Sort order (asc, desc)
     * 
     * @return array
     */
    public function list(array $filters = []): array
    {
        $params = $this->buildQueryParams($filters, [
            'page' => 1,
            'per_page' => 50,
        ]);
        
        $response = $this->http->get('/payments', $params);
        
        return array_map(
            fn($data) => Payment::fromArray($data),
            $response->getData()
        );
    }
    
    /**
     * Get a specific payment
     * 
     * @param string $id Payment ID
     * @return Payment
     */
    public function get(string $id): Payment
    {
        $response = $this->http->get("/payments/{$id}");
        return Payment::fromArray($response->getData());
    }
    
    /**
     * Record a payment for an invoice
     * 
     * @param string $invoiceCzUid Invoice CzUid
     * @param array $data Payment data:
     *   - amount: float Payment amount (required)
     *   - date: string Payment date Y-m-d (default: today)
     *   - method: string Payment method (cash, bank_transfer, card, etc.)
     *   - reference: string Payment reference/transaction ID
     *   - notes: string Additional notes
     * 
     * @return Payment
     */
    public function create(string $invoiceCzUid, array $data): Payment
    {
        $this->validateRequired($data, ['amount']);
        
        // Set default date if not provided
        if (!isset($data['date'])) {
            $data['date'] = date('Y-m-d');
        }
        
        // Set default method if not provided
        if (!isset($data['method'])) {
            $data['method'] = 'bank_transfer';
        }
        
        $response = $this->http->post("/invoices/{$invoiceCzUid}/payments", $data);
        return Payment::fromArray($response->getData());
    }
    
    /**
     * Update a payment
     * 
     * @param string $id Payment ID
     * @param array $data Data to update
     * @return Payment
     */
    public function update(string $id, array $data): Payment
    {
        $response = $this->http->patch("/payments/{$id}", $data);
        return Payment::fromArray($response->getData());
    }
    
    /**
     * Delete/cancel a payment
     * 
     * @param string $id Payment ID
     * @return bool
     */
    public function delete(string $id): bool
    {
        $response = $this->http->delete("/payments/{$id}");
        return $response->isSuccess();
    }
    
    /**
     * Get payments for an invoice
     * 
     * @param string $invoiceCzUid Invoice CzUid
     * @return array
     */
    public function getForInvoice(string $invoiceCzUid): array
    {
        return $this->list(['invoice_id' => $invoiceCzUid]);
    }
    
    /**
     * Get payments for a client
     * 
     * @param string $clientCzUid Client CzUid
     * @return array
     */
    public function getForClient(string $clientCzUid): array
    {
        return $this->list(['client_id' => $clientCzUid]);
    }
    
    /**
     * Get payment statistics
     * 
     * @param array $filters Optional filters
     * @return array
     */
    public function getStatistics(array $filters = []): array
    {
        $response = $this->http->get('/payments/stats', $filters);
        return $response->getData();
    }
    
    /**
     * Mark invoice as fully paid
     * Convenience method that records a payment for the full invoice amount
     * 
     * @param string $invoiceCzUid Invoice CzUid
     * @param array $options Payment options
     * @return Payment
     */
    public function markInvoiceAsPaid(string $invoiceCzUid, array $options = []): Payment
    {
        // Get invoice to determine amount
        $invoiceResponse = $this->http->get("/invoices/{$invoiceCzUid}");
        $invoice = $invoiceResponse->getData();
        
        $data = array_merge($options, [
            'amount' => $invoice['total'] ?? 0,
            'date' => $options['date'] ?? date('Y-m-d'),
            'method' => $options['method'] ?? 'bank_transfer',
        ]);
        
        return $this->create($invoiceCzUid, $data);
    }
    
    /**
     * Record a partial payment for an invoice
     * 
     * @param string $invoiceCzUid Invoice CzUid
     * @param float $amount Payment amount
     * @param array $options Additional payment options
     * @return Payment
     */
    public function recordPartialPayment(string $invoiceCzUid, float $amount, array $options = []): Payment
    {
        $data = array_merge($options, [
            'amount' => $amount,
        ]);
        
        return $this->create($invoiceCzUid, $data);
    }
    
    /**
     * Get available payment methods
     * 
     * @return array
     */
    public static function getAvailableMethods(): array
    {
        return [
            'cash' => 'Numerar',
            'bank_transfer' => 'Transfer bancar',
            'card' => 'Card',
            'check' => 'CEC',
            'promissory_note' => 'Bilet la ordin',
            'compensation' => 'Compensare',
            'other' => 'Altele',
        ];
    }
}