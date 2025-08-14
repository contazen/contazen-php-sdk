<?php

declare(strict_types=1);

namespace Contazen\Resources;

/**
 * Documents API resource
 * 
 * Manage document templates and attachments
 */
class Documents extends Resource
{
    /**
     * List document templates
     * 
     * @return array
     */
    public function listTemplates(): array
    {
        $response = $this->http->get('/documents/templates');
        return $response->getData();
    }
    
    /**
     * Get a document template
     * 
     * @param string $id Template ID
     * @return array
     */
    public function getTemplate(string $id): array
    {
        $response = $this->http->get("/documents/templates/{$id}");
        return $response->getData();
    }
    
    /**
     * Create a custom document template
     * 
     * @param array $data Template data:
     *   - name: string Template name
     *   - type: string Document type (invoice, proforma, receipt)
     *   - content: string HTML template content
     *   - is_default: bool Set as default template
     * 
     * @return array
     */
    public function createTemplate(array $data): array
    {
        $this->validateRequired($data, ['name', 'type', 'content']);
        
        $response = $this->http->post('/documents/templates', $data);
        return $response->getData();
    }
    
    /**
     * Update a document template
     * 
     * @param string $id Template ID
     * @param array $data Data to update
     * @return array
     */
    public function updateTemplate(string $id, array $data): array
    {
        $response = $this->http->patch("/documents/templates/{$id}", $data);
        return $response->getData();
    }
    
    /**
     * Delete a document template
     * 
     * @param string $id Template ID
     * @return bool
     */
    public function deleteTemplate(string $id): bool
    {
        $response = $this->http->delete("/documents/templates/{$id}");
        return $response->isSuccess();
    }
    
    /**
     * Upload an attachment for a document
     * 
     * @param string $documentType Document type (invoice, client, product)
     * @param string $documentId Document ID
     * @param string $filePath Path to file to upload
     * @param array $metadata Optional metadata
     * @return array
     */
    public function uploadAttachment(
        string $documentType,
        string $documentId,
        string $filePath,
        array $metadata = []
    ): array {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }
        
        $multipart = [
            [
                'name' => 'file',
                'contents' => fopen($filePath, 'r'),
                'filename' => basename($filePath),
            ],
            [
                'name' => 'document_type',
                'contents' => $documentType,
            ],
            [
                'name' => 'document_id',
                'contents' => $documentId,
            ],
        ];
        
        foreach ($metadata as $key => $value) {
            $multipart[] = [
                'name' => $key,
                'contents' => $value,
            ];
        }
        
        // Note: File upload requires special handling in HTTP client
        // This is a simplified example
        $response = $this->http->post('/documents/attachments', [
            'multipart' => $multipart,
        ]);
        
        return $response->getData();
    }
    
    /**
     * List attachments for a document
     * 
     * @param string $documentType Document type
     * @param string $documentId Document ID
     * @return array
     */
    public function listAttachments(string $documentType, string $documentId): array
    {
        $response = $this->http->get('/documents/attachments', [
            'document_type' => $documentType,
            'document_id' => $documentId,
        ]);
        
        return $response->getData();
    }
    
    /**
     * Download an attachment
     * 
     * @param string $attachmentId Attachment ID
     * @return string File content
     */
    public function downloadAttachment(string $attachmentId): string
    {
        $response = $this->http->get("/documents/attachments/{$attachmentId}/download");
        return $response->getBody();
    }
    
    /**
     * Delete an attachment
     * 
     * @param string $attachmentId Attachment ID
     * @return bool
     */
    public function deleteAttachment(string $attachmentId): bool
    {
        $response = $this->http->delete("/documents/attachments/{$attachmentId}");
        return $response->isSuccess();
    }
    
    /**
     * Generate a document preview
     * 
     * @param string $type Document type
     * @param array $data Document data
     * @return string HTML preview
     */
    public function generatePreview(string $type, array $data): string
    {
        $response = $this->http->post('/documents/preview', [
            'type' => $type,
            'data' => $data,
        ]);
        
        return $response->getBody();
    }
    
    /**
     * Export documents
     * 
     * @param array $filters Export filters:
     *   - type: string Document type
     *   - start_date: string Start date
     *   - end_date: string End date
     *   - format: string Export format (pdf, excel, csv)
     * 
     * @return string Export file content
     */
    public function export(array $filters): string
    {
        $response = $this->http->post('/documents/export', $filters);
        return $response->getBody();
    }
}