<?php

declare(strict_types=1);

namespace Contazen\Resources;

use Contazen\Models\EFacturaResponse;

/**
 * E-Factura/ANAF Integration API resource
 * 
 * Handle Romanian e-invoicing compliance
 */
class EFactura extends Resource
{
    /**
     * Submit an invoice to ANAF E-Factura system
     * 
     * @param string $invoiceCzUid Invoice CzUid to submit
     * @return EFacturaResponse
     */
    public function submit(string $invoiceCzUid): EFacturaResponse
    {
        $response = $this->http->post("/efactura/submit/{$invoiceCzUid}");
        return EFacturaResponse::fromArray($response->getData());
    }
    
    /**
     * Check E-Factura submission status
     * 
     * @param string $uploadId ANAF upload ID
     * @return EFacturaResponse
     */
    public function getStatus(string $uploadId): EFacturaResponse
    {
        $response = $this->http->get("/efactura/status/{$uploadId}");
        return EFacturaResponse::fromArray($response->getData());
    }
    
    /**
     * Download E-Factura XML for an invoice
     * 
     * @param string $invoiceCzUid Invoice CzUid
     * @return string XML content
     */
    public function downloadXml(string $invoiceCzUid): string
    {
        $response = $this->http->get("/efactura/xml/{$invoiceCzUid}", [], [
            'Accept' => 'application/xml',
        ]);
        return $response->getBody();
    }
    
    /**
     * Validate invoice for E-Factura compliance
     * 
     * @param string $invoiceCzUid Invoice CzUid to validate
     * @return array Validation results
     */
    public function validate(string $invoiceCzUid): array
    {
        $response = $this->http->post("/efactura/validate/{$invoiceCzUid}");
        return $response->getData();
    }
    
    /**
     * Get E-Factura messages from ANAF
     * 
     * @param array $filters Filters:
     *   - days: int Number of days to retrieve (default: 7)
     *   - type: string Message type filter
     *   - status: string Status filter
     * 
     * @return array
     */
    public function getMessages(array $filters = []): array
    {
        $params = $this->buildQueryParams($filters, [
            'days' => 7,
        ]);
        
        $response = $this->http->get('/efactura/messages', $params);
        return $response->getData();
    }
    
    /**
     * Sync E-Factura messages from ANAF
     * Fetches new messages and updates invoice statuses
     * 
     * @return array Sync results
     */
    public function syncMessages(): array
    {
        $response = $this->http->post('/efactura/sync-messages');
        return $response->getData();
    }
    
    /**
     * Get E-Factura settings
     * 
     * @return array
     */
    public function getSettings(): array
    {
        $response = $this->http->get('/efactura/settings');
        return $response->getData();
    }
    
    /**
     * Update E-Factura settings
     * 
     * @param array $settings Settings to update:
     *   - environment: string Environment (test, live)
     *   - auto_send: bool Auto-send invoices to ANAF
     *   - auto_send_timing: string When to auto-send (immediate, daily)
     *   - send_to_public_institutions: bool Auto-send for public institutions
     * 
     * @return array
     */
    public function updateSettings(array $settings): array
    {
        $response = $this->http->patch('/efactura/settings', $settings);
        return $response->getData();
    }
    
    /**
     * Initialize OAuth connection with ANAF
     * 
     * @param string $environment Environment (test, live)
     * @return array OAuth URL and state
     */
    public function initializeOAuth(string $environment = 'test'): array
    {
        $response = $this->http->post('/efactura/oauth/initialize', [
            'environment' => $environment,
        ]);
        return $response->getData();
    }
    
    /**
     * Complete OAuth callback from ANAF
     * 
     * @param string $code Authorization code from ANAF
     * @param string $state State parameter for verification
     * @return array
     */
    public function completeOAuth(string $code, string $state): array
    {
        $response = $this->http->post('/efactura/oauth/callback', [
            'code' => $code,
            'state' => $state,
        ]);
        return $response->getData();
    }
    
    /**
     * Revoke OAuth authorization
     * 
     * @param string $environment Environment (test, live)
     * @return bool
     */
    public function revokeOAuth(string $environment = 'test'): bool
    {
        $response = $this->http->post('/efactura/oauth/revoke', [
            'environment' => $environment,
        ]);
        return $response->isSuccess();
    }
    
    /**
     * Get OAuth status
     * 
     * @return array
     */
    public function getOAuthStatus(): array
    {
        $response = $this->http->get('/efactura/oauth/status');
        return $response->getData();
    }
    
    /**
     * Check if invoice was sent to ANAF
     * 
     * @param string $invoiceCzUid Invoice CzUid
     * @return bool
     */
    public function isInvoiceSent(string $invoiceCzUid): bool
    {
        $response = $this->http->get("/efactura/invoice/{$invoiceCzUid}/status");
        $data = $response->getData();
        
        return isset($data['sent_to_anaf']) && $data['sent_to_anaf'] === true;
    }
    
    /**
     * Get E-Factura submission history for an invoice
     * 
     * @param string $invoiceCzUid Invoice CzUid
     * @return array
     */
    public function getInvoiceHistory(string $invoiceCzUid): array
    {
        $response = $this->http->get("/efactura/invoice/{$invoiceCzUid}/history");
        return $response->getData();
    }
    
    /**
     * Retry failed E-Factura submission
     * 
     * @param string $invoiceCzUid Invoice CzUid
     * @return EFacturaResponse
     */
    public function retry(string $invoiceCzUid): EFacturaResponse
    {
        $response = $this->http->post("/efactura/retry/{$invoiceCzUid}");
        return EFacturaResponse::fromArray($response->getData());
    }
    
    /**
     * Cancel E-Factura submission
     * 
     * @param string $invoiceCzUid Invoice CzUid
     * @return bool
     */
    public function cancel(string $invoiceCzUid): bool
    {
        $response = $this->http->post("/efactura/cancel/{$invoiceCzUid}");
        return $response->isSuccess();
    }
}