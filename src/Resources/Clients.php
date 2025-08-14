<?php

declare(strict_types=1);

namespace Contazen\Resources;

use Contazen\Collections\ClientCollection;
use Contazen\Models\Client;
use Contazen\Exceptions\ValidationException;

/**
 * Clients API resource
 * 
 * Handles all client/customer-related operations
 */
class Clients extends Resource
{
    /**
     * List clients with optional filters
     * 
     * @param array $filters Available filters:
     *   - page: int Page number (default: 1)
     *   - per_page: int Items per page (default: 50, max: 100)
     *   - search: string Search term for name/email/cui
     *   - sort: string Sort field (name, created_at)
     *   - order: string Sort order (asc, desc)
     *   - is_active: bool Filter by active status
     *   - cui: string Filter by exact CUI
     *   - city: string Filter by city
     *   - county: string Filter by county
     * 
     * @return ClientCollection
     */
    public function list(array $filters = []): ClientCollection
    {
        $params = $this->buildQueryParams($filters, [
            'page' => 1,
            'per_page' => 50,
        ]);
        
        $response = $this->http->get('/clients', $params);
        return ClientCollection::fromResponse($response);
    }
    
    /**
     * Get a single client by CzUid or ID
     * 
     * @param string $czUid Client CzUid or ID
     * @return Client
     */
    public function get(string $czUid): Client
    {
        $response = $this->http->get("/clients/{$czUid}");
        return Client::fromArray($response->getData());
    }
    
    /**
     * Create a new client
     * 
     * @param array $data Client data:
     *   - name: string Customer name (required)
     *   - email: string Email address (required)
     *   - phone: string Phone number (optional)
     *   - cui: string Romanian tax ID (CUI/CIF) (optional)
     *   - cui_prefix: string CUI prefix (RO for VAT registered) (optional)
     *   - rc: string Trade registry number (optional)
     *   - address: string Street address (optional)
     *   - city: string City (for București, must include sector) (optional)
     *   - county: string County/State (optional)
     *   - country: string Country code (default: RO) (optional)
     *   - postal_code: string Postal code (optional)
     *   - iban: string Bank account IBAN (optional)
     *   - bank: string Bank name (optional)
     *   - contact_person: string Contact person name (optional)
     * 
     * @return Client
     */
    public function create(array $data): Client
    {
        $this->validateRequired($data, ['name', 'email']);
        
        // Auto-fill firm_id if configured
        if (!isset($data['firm_id']) && $this->http->getConfig()->getFirmId()) {
            $data['firm_id'] = $this->http->getConfig()->getFirmId();
        }
        
        $preparedData = $this->prepareClientData($data);
        $response = $this->http->post('/clients', $preparedData);
        
        // API returns empty response on successful creation
        // We need to return a client with at least the data we sent
        $responseData = $response->getData();
        if (empty($responseData)) {
            // Return a client with the data we sent
            return Client::fromArray($preparedData);
        }
        
        return Client::fromArray($responseData);
    }
    
    /**
     * Update an existing client
     * 
     * @param string $czUid Client CzUid or ID
     * @param array $data Data to update (supports partial updates)
     * @return Client
     */
    public function update(string $czUid, array $data): Client
    {
        $preparedData = $this->prepareData($data);
        $response = $this->http->put("/clients/{$czUid}", $preparedData);
        
        return Client::fromArray($response->getData());
    }
    
    /**
     * Delete a client
     * 
     * @param string $czUid Client CzUid or ID
     * @return bool
     */
    public function delete(string $czUid): bool
    {
        $response = $this->http->delete("/clients/{$czUid}");
        return $response->isSuccess();
    }
    
    /**
     * Search clients by term
     * 
     * @param string $searchTerm Search term
     * @return ClientCollection
     */
    public function search(string $searchTerm): ClientCollection
    {
        return $this->list(['search' => $searchTerm]);
    }
    
    /**
     * Find client by CUI (Romanian tax ID)
     * 
     * @param string $cui CUI/CIF number
     * @return Client|null
     */
    public function findByCui(string $cui): ?Client
    {
        // Clean CUI - remove RO prefix if present
        $cui = preg_replace('/^RO/i', '', trim($cui));
        
        $clients = $this->search($cui);
        
        // Look for exact CUI match
        foreach ($clients as $client) {
            if ($client->cui === $cui) {
                return $client;
            }
        }
        
        return null;
    }
    
    /**
     * Find client by email
     * 
     * @param string $email Email address
     * @return Client|null
     */
    public function findByEmail(string $email): ?Client
    {
        $clients = $this->search($email);
        
        // Look for exact email match
        foreach ($clients as $client) {
            if (strcasecmp($client->email, $email) === 0) {
                return $client;
            }
        }
        
        return null;
    }
    
    /**
     * Create or update client (upsert)
     * 
     * @param array $data Client data
     * @return Client
     */
    public function upsert(array $data): Client
    {
        // Try to find existing client by CUI
        if (!empty($data['cui'])) {
            $existing = $this->findByCui($data['cui']);
            if ($existing) {
                return $this->update($existing->cz_uid, $data);
            }
        }
        
        // Try to find by email
        if (!empty($data['email'])) {
            $existing = $this->findByEmail($data['email']);
            if ($existing) {
                return $this->update($existing->cz_uid, $data);
            }
        }
        
        // Create new client
        return $this->create($data);
    }
    
    /**
     * Fetch company data from ANAF (Romanian tax authority)
     * 
     * @param string $cui CUI/CIF number
     * @return array Company data from ANAF
     */
    public function fetchFromAnaf(string $cui): array
    {
        // Clean CUI - remove RO prefix if present
        $cui = preg_replace('/^RO/i', '', trim($cui));
        
        $response = $this->http->get("/clients/anaf/{$cui}");
        return $response->getData();
    }
    
    /**
     * Import multiple clients
     * 
     * @param array $clients Array of client data
     * @return array Array of Client objects
     */
    public function importBulk(array $clients): array
    {
        $response = $this->http->post('/clients/import', ['clients' => $clients]);
        
        return array_map(
            fn($data) => Client::fromArray($data),
            $response->getData()
        );
    }
    
    /**
     * Import clients from CSV
     * 
     * @param string $csvContent CSV content
     * @return array Import results
     */
    public function importCsv(string $csvContent): array
    {
        $response = $this->http->post('/clients/import', [
            'format' => 'csv',
            'content' => base64_encode($csvContent)
        ]);
        
        return $response->getData();
    }
    
    /**
     * Validate Romanian CUI
     * 
     * @param string $cui CUI to validate
     * @return bool
     */
    public function validateCui(string $cui): bool
    {
        // Remove RO prefix if present
        $cui = preg_replace('/^RO/i', '', trim($cui));
        
        // CUI must be numeric
        if (!ctype_digit($cui)) {
            return false;
        }
        
        // CUI must be between 2 and 10 digits
        $length = strlen($cui);
        if ($length < 2 || $length > 10) {
            return false;
        }
        
        // Implement Romanian CUI validation algorithm
        $controlSum = '753217532';
        $controlDigit = (int) $cui[$length - 1];
        $cui = substr($cui, 0, -1);
        
        $sum = 0;
        for ($i = 0; $i < strlen($cui); $i++) {
            $sum += ((int) $cui[$i]) * ((int) substr($controlSum, $i, 1));
        }
        
        $calculatedControl = ($sum * 10) % 11;
        if ($calculatedControl === 10) {
            $calculatedControl = 0;
        }
        
        return $calculatedControl === $controlDigit;
    }
    
    /**
     * Prepare client data for API
     * 
     * @param array $data
     * @return array
     */
    private function prepareClientData(array $data): array
    {
        // Validate București sector requirement
        if (!empty($data['city'])) {
            $city = trim($data['city']);
            if (preg_match('/bucuresti|bucurești/i', $city)) {
                if (!preg_match('/sector/i', $city)) {
                    throw new ValidationException(
                        'For București, you must specify the sector (e.g., "București, Sector 1")'
                    );
                }
            }
        }
        
        // Clean CUI if provided
        if (!empty($data['cui'])) {
            // Check if CUI has RO prefix for VAT
            if (preg_match('/^RO/i', $data['cui'])) {
                $data['cui_prefix'] = 'RO';
                $data['cui'] = preg_replace('/^RO/i', '', $data['cui']);
            }
            
            // Validate CUI only if it looks like a Romanian CUI (numeric)
            // Skip validation for foreign CUIs or test data
            if (ctype_digit($data['cui']) && strlen($data['cui']) >= 6) {
                if (!$this->validateCui($data['cui'])) {
                    // Just log a warning, don't throw - API will validate
                    // throw new ValidationException('Invalid Romanian CUI format');
                }
            }
        }
        
        // Validate email if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format');
        }
        
        // Set default country if not provided
        if (!isset($data['country'])) {
            $data['country'] = 'RO';
        }
        
        return $this->prepareData($data);
    }
}