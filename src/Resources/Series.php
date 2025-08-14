<?php

declare(strict_types=1);

namespace Contazen\Resources;

use Contazen\Models\Series as SeriesModel;

/**
 * Invoice Series API resource
 * 
 * Manage invoice numbering series
 */
class Series extends Resource
{
    /**
     * List all invoice series
     * 
     * @return array
     */
    public function list(): array
    {
        $response = $this->http->get('/series');
        
        return array_map(
            fn($data) => SeriesModel::fromArray($data),
            $response->getData()
        );
    }
    
    /**
     * Get a specific series
     * 
     * @param string $czUid Series CzUid
     * @return SeriesModel
     */
    public function get(string $czUid): SeriesModel
    {
        $response = $this->http->get("/series/{$czUid}");
        return SeriesModel::fromArray($response->getData());
    }
    
    /**
     * Create a new series
     * 
     * @param array $data Series data:
     *   - name: string Series name/prefix (required)
     *   - document_type: string Document type (fiscal, proforma)
     *   - start_number: int Starting number (default: 1)
     *   - is_primary: bool Set as primary series for document type
     * 
     * @return SeriesModel
     */
    public function create(array $data): SeriesModel
    {
        $this->validateRequired($data, ['name', 'document_type']);
        
        // Auto-fill firm_id if configured
        if (!isset($data['firm_id']) && $this->http->getConfig()->getFirmId()) {
            $data['firm_id'] = $this->http->getConfig()->getFirmId();
        }
        
        $response = $this->http->post('/series', $data);
        return SeriesModel::fromArray($response->getData());
    }
    
    /**
     * Update a series
     * 
     * @param string $czUid Series CzUid
     * @param array $data Data to update
     * @return SeriesModel
     */
    public function update(string $czUid, array $data): SeriesModel
    {
        $response = $this->http->patch("/series/{$czUid}", $data);
        return SeriesModel::fromArray($response->getData());
    }
    
    /**
     * Delete a series
     * 
     * @param string $czUid Series CzUid
     * @return bool
     */
    public function delete(string $czUid): bool
    {
        $response = $this->http->delete("/series/{$czUid}");
        return $response->isSuccess();
    }
    
    /**
     * Set series as primary for its document type
     * 
     * @param string $czUid Series CzUid
     * @return SeriesModel
     */
    public function setPrimary(string $czUid): SeriesModel
    {
        $response = $this->http->post("/series/{$czUid}/set-primary");
        return SeriesModel::fromArray($response->getData());
    }
    
    /**
     * Get primary series for a document type
     * 
     * @param string $documentType Document type (fiscal, proforma)
     * @return SeriesModel|null
     */
    public function getPrimary(string $documentType): ?SeriesModel
    {
        $series = $this->list();
        
        foreach ($series as $item) {
            if ($item->document_type === $documentType && $item->is_primary) {
                return $item;
            }
        }
        
        return null;
    }
    
    /**
     * Get next number for a series
     * 
     * @param string $czUid Series CzUid
     * @return int
     */
    public function getNextNumber(string $czUid): int
    {
        $series = $this->get($czUid);
        return $series->next_number;
    }
}