<?php

declare(strict_types=1);

namespace Contazen\Resources;

use Contazen\Collections\InvoiceCollection;
use Contazen\Models\Invoice;
use Contazen\Http\Response;

/**
 * Invoices API resource
 * 
 * Handles all invoice-related operations
 */
class Invoices extends Resource
{
    /**
     * List invoices with optional filters
     * 
     * @param array $filters Available filters:
     *   - page: int Page number (default: 1)
     *   - per_page: int Items per page (default: 50, max: 100)
     *   - client_id: string Filter by client CzUid or ID
     *   - series_id: string Filter by series CzUid
     *   - document_type: string fiscal|proforma
     *   - status: string draft|sent|paid|overdue|cancelled
     *   - currency: string Filter by currency code (e.g., RON, EUR, USD)
     *   - is_paid: bool Filter by payment status
     *   - start_date: string Start date (Y-m-d)
     *   - end_date: string End date (Y-m-d)
     *   - sort: string created_at|date|due_date|total|number
     *   - order: string asc|desc
     *   - expand: string Comma-separated expand options (lines, payments)
     *   - search: string Search in invoice number or client name
     * 
     * @return InvoiceCollection
     */
    public function list(array $filters = []): InvoiceCollection
    {
        $params = $this->buildQueryParams($filters, [
            'page' => 1,
            'per_page' => 50,
        ]);
        
        $response = $this->http->get('/invoices', $params);
        return InvoiceCollection::fromResponse($response);
    }
    
    /**
     * Get a single invoice by CzUid or ID
     * 
     * @param string $czUidOrId
     * @return Invoice
     */
    public function get(string $czUidOrId): Invoice
    {
        $response = $this->http->get("/invoices/{$czUidOrId}");
        return Invoice::fromArray($response->getData());
    }
    
    /**
     * Create a new invoice
     * 
     * @param array $data Invoice data:
     *   - client_data: array Client information (required if client_id not provided)
     *     - type: string b2b|b2c|business|individual|person (optional)
     *     - name: string Client name (required)
     *     - email: string Email (required for new clients)
     *     - phone: string Phone (optional)
     *     - cui: string Tax ID (optional)
     *     - cui_prefix: string CUI prefix for foreign companies (optional)
     *     - rc: string Registration code (optional)
     *     - address: string Client address (optional)
     *     - city: string City (optional)
     *     - county: string County (optional)
     *     - country: string Country code (optional)
     *     - postal_code: string Postal code (optional)
     *     - iban: string Bank account IBAN (optional)
     *     - bank: string Bank name (optional)
     *     - contact_person: string Contact person name (optional)
     *   - client_id: string Existing client CzUid or ID (alternative to client_data)
     *   - items: array Invoice line items (required)
     *     - description: string Item description (required)
     *     - quantity: number Quantity (required)
     *     - price: number Unit price without VAT (required)
     *     - vat_rate: number VAT rate: 21, 19, 11, 9, 5, 0 (required)
     *     - unit_of_measure: string Unit of measure (optional)
     *   - series_id: string Invoice series ID (optional)
     *   - document_type: string fiscal|proforma (required)
     *   - currency: string Currency code (default: RON)
     *   - date: string Invoice date YYYY-MM-DD (required)
     *   - due_date: string Due date YYYY-MM-DD (optional)
     *   - due_days: int Payment terms in days (optional, alternative to due_date)
     *   - observations: string Additional notes (optional)
     *   - is_draft: bool Save as draft (default: false)
     *   - efactura_enabled: bool Enable E-Factura (optional)
     *   - send_email: bool Send email to client (default: false)
     *   - spv_environment: string SPV environment for E-Factura (optional)
     * 
     * @return Invoice
     */
    public function create(array $data): Invoice
    {
        // Handle both 'client' and 'client_data' fields for compatibility
        if (isset($data['client']) && !isset($data['client_data']) && !isset($data['client_id'])) {
            $data['client_data'] = $data['client'];
            unset($data['client']);
        }
        
        // Validate that we have either client_id or client_data
        if (!isset($data['client_id']) && !isset($data['client_data'])) {
            throw new \InvalidArgumentException('Either client_id or client_data is required');
        }
        
        // Validate items
        if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
            throw new \InvalidArgumentException('Items array is required and cannot be empty');
        }
        
        // Auto-fill firm_id if configured
        if (!isset($data['firm_id']) && $this->http->getConfig()->getFirmId()) {
            $data['firm_id'] = $this->http->getConfig()->getFirmId();
        }
        
        $preparedData = $this->prepareInvoiceData($data);
        $response = $this->http->post('/invoices', $preparedData);
        
        return Invoice::fromArray($response->getData());
    }
    
    /**
     * Update an existing invoice
     * 
     * @param string $czUid Invoice CzUid
     * @param array $data Data to update
     * @return Invoice
     */
    public function update(string $czUid, array $data): Invoice
    {
        $preparedData = $this->prepareData($data);
        // API uses PUT for updates, not PATCH
        $response = $this->http->put("/invoices/{$czUid}", $preparedData);
        
        return Invoice::fromArray($response->getData());
    }
    
    /**
     * Delete/void an invoice
     * 
     * @param string $czUid Invoice CzUid
     * @return bool
     */
    public function delete(string $czUid): bool
    {
        $response = $this->http->delete("/invoices/{$czUid}");
        return $response->isSuccess();
    }
    
    /**
     * Mark invoice as paid
     * 
     * @param string $czUid Invoice CzUid
     * @param array $paymentData Optional payment information:
     *   - amount: float Payment amount
     *   - date: string Payment date (Y-m-d)
     *   - method: string Payment method
     *   - reference: string Payment reference
     * @return Invoice
     */
    public function markAsPaid(string $czUid, array $paymentData = []): Invoice
    {
        $response = $this->http->post("/invoices/{$czUid}/pay", $paymentData);
        return Invoice::fromArray($response->getData());
    }
    
    /**
     * Void an invoice
     * 
     * @param string $czUid Invoice CzUid
     * @param array $data Optional void data:
     *   - reason: string Reason for voiding
     * @return Invoice
     */
    public function void(string $czUid, array $data = []): Invoice
    {
        $response = $this->http->post("/invoices/{$czUid}/void", $data);
        return Invoice::fromArray($response->getData());
    }
    
    /**
     * Mark invoice as sent
     * 
     * @param string $czUid Invoice CzUid
     * @return Invoice
     */
    public function markAsSent(string $czUid): Invoice
    {
        $response = $this->http->post("/invoices/{$czUid}/mark-sent");
        return Invoice::fromArray($response->getData());
    }
    
    /**
     * Send invoice by email
     * 
     * @param string $czUid Invoice CzUid
     * @param array $options Email options:
     *   - recipients: array Email addresses
     *   - cc: array CC email addresses
     *   - bcc: array BCC email addresses
     *   - subject: string Custom subject
     *   - message: string Custom message
     * @return bool
     */
    public function sendByEmail(string $czUid, array $options = []): bool
    {
        $response = $this->http->post("/invoices/{$czUid}/send", $options);
        return $response->isSuccess();
    }
    
    /**
     * Download invoice as PDF
     * 
     * @param string $czUid Invoice CzUid
     * @return string PDF content
     */
    public function downloadPdf(string $czUid): string
    {
        $response = $this->http->get("/invoices/{$czUid}/pdf", [], [
            'Accept' => 'application/pdf',
        ]);
        return $response->getBody();
    }
    
    /**
     * Get invoice PDF download URL
     * 
     * @param string $czUid Invoice CzUid
     * @return string
     */
    public function getPdfUrl(string $czUid): string
    {
        $response = $this->http->get("/invoices/{$czUid}/pdf-url");
        $data = $response->getData();
        return $data['url'] ?? '';
    }
    
    /**
     * Duplicate an invoice
     * 
     * @param string $czUid Invoice CzUid to duplicate
     * @param array $overrides Data to override in the duplicate
     * @return Invoice
     */
    public function duplicate(string $czUid, array $overrides = []): Invoice
    {
        $response = $this->http->post("/invoices/{$czUid}/duplicate", $overrides);
        return Invoice::fromArray($response->getData());
    }
    
    /**
     * Convert proforma to fiscal invoice
     * 
     * @param string $czUid Proforma CzUid
     * @return Invoice
     */
    public function convertToFiscal(string $czUid): Invoice
    {
        $response = $this->http->post("/invoices/{$czUid}/convert-to-fiscal");
        return Invoice::fromArray($response->getData());
    }
    
    /**
     * Create invoice from order data (helper method)
     * 
     * @param array $orderData Order data with customer and items
     * @return Invoice
     */
    public function createFromOrder(array $orderData): Invoice
    {
        $invoiceData = [
            'client' => $this->prepareClientData($orderData['customer'] ?? []),
            'items' => $this->prepareOrderItems($orderData['items'] ?? []),
            'currency' => $orderData['currency'] ?? 'RON',
            'series_id' => $orderData['series_id'] ?? null,
            'notes' => $orderData['notes'] ?? '',
            'document_type' => $orderData['document_type'] ?? 'fiscal',
            'payment_method' => $orderData['payment_method'] ?? null,
        ];
        
        // Add optional fields
        if (isset($orderData['shipping'])) {
            $invoiceData['shipping'] = $orderData['shipping'];
        }
        
        if (isset($orderData['discount'])) {
            $invoiceData['discount'] = $orderData['discount'];
        }
        
        return $this->create($invoiceData);
    }
    
    /**
     * Bulk create invoices
     * 
     * @param array $invoices Array of invoice data
     * @return array Array of Invoice objects
     */
    public function createBulk(array $invoices): array
    {
        $response = $this->http->post('/invoices/bulk', ['invoices' => $invoices]);
        
        return array_map(
            fn($data) => Invoice::fromArray($data),
            $response->getData()
        );
    }
    
    /**
     * Search invoices by number or reference
     * 
     * @param string $query Search query
     * @return InvoiceCollection
     */
    public function search(string $query): InvoiceCollection
    {
        return $this->list(['q' => $query]);
    }
    
    /**
     * Get invoice statistics
     * 
     * @param array $filters Optional filters
     * @return array
     */
    public function stats(array $filters = []): array
    {
        $response = $this->http->get('/invoices/stats', $filters);
        return $response->getData();
    }
    
    /**
     * Prepare invoice data for API
     * 
     * @param array $data
     * @return array
     */
    private function prepareInvoiceData(array $data): array
    {
        // Ensure items have required fields
        if (isset($data['items'])) {
            $data['items'] = array_map(function ($item) {
                return array_merge([
                    'quantity' => 1,
                    'vat_rate' => 19,
                ], $item);
            }, $data['items']);
        }
        
        // Document type is required by API
        if (!isset($data['document_type'])) {
            $data['document_type'] = 'fiscal';
        }
        
        // Date is required by API
        if (!isset($data['date'])) {
            $data['date'] = date('Y-m-d');
        }
        
        // Set default currency
        if (!isset($data['currency'])) {
            $data['currency'] = 'RON';
        }
        
        return $this->prepareData($data);
    }
    
    /**
     * Prepare client data from customer information
     * 
     * @param array $customer
     * @return array
     */
    private function prepareClientData(array $customer): array
    {
        $client = [];
        
        // Map common field names
        $fieldMap = [
            'name' => 'name',
            'company' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'tax_id' => 'cui',
            'cui' => 'cui',
            'vat_id' => 'cui',
            'reg_com' => 'rc',
            'rc' => 'rc',
            'address' => 'address',
            'city' => 'city',
            'state' => 'county',
            'country' => 'country',
            'postal_code' => 'postal_code',
            'bank_account' => 'bank_account',
            'iban' => 'bank_account',
        ];
        
        foreach ($fieldMap as $from => $to) {
            if (isset($customer[$from])) {
                $client[$to] = $customer[$from];
            }
        }
        
        return $client;
    }
    
    /**
     * Prepare order items for invoice
     * 
     * @param array $items
     * @return array
     */
    private function prepareOrderItems(array $items): array
    {
        return array_map(function ($item) {
            $prepared = [
                'name' => $item['name'] ?? $item['description'] ?? 'Item',
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'] ?? 0,
            ];
            
            if (isset($item['vat_rate'])) {
                $prepared['vat_rate'] = $item['vat_rate'];
            } elseif (isset($item['tax_rate'])) {
                $prepared['vat_rate'] = $item['tax_rate'];
            }
            
            if (isset($item['unit'])) {
                $prepared['unit'] = $item['unit'];
            }
            
            if (isset($item['sku'])) {
                $prepared['sku'] = $item['sku'];
            }
            
            return $prepared;
        }, $items);
    }
}