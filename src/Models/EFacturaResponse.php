<?php

declare(strict_types=1);

namespace Contazen\Models;

/**
 * E-Factura ANAF response model
 * 
 * @property string $cz_uid Unique identifier
 * @property string $invoice_cz_uid Invoice reference
 * @property string $upload_id ANAF upload ID
 * @property string $message_id ANAF message ID
 * @property string $status Submission status
 * @property string $environment Environment (test/production)
 * @property array|null $anaf_response Full ANAF response
 * @property array|null $errors Validation errors
 * @property string|null $download_id Download ID for signed XML
 * @property string|null $xml_content Original XML content
 * @property string|null $signed_xml_content Signed XML from ANAF
 * @property string|null $submitted_at Submission timestamp
 * @property string|null $validated_at Validation timestamp
 * @property string|null $processed_at Processing timestamp
 * @property string $created_at Creation timestamp
 * @property string|null $updated_at Last update timestamp
 */
class EFacturaResponse extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_UPLOADING = 'uploading';
    const STATUS_UPLOADED = 'uploaded';
    const STATUS_VALIDATING = 'validating';
    const STATUS_VALID = 'valid';
    const STATUS_INVALID = 'invalid';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PROCESSED = 'processed';
    const STATUS_ERROR = 'error';
    const STATUS_REJECTED = 'rejected';
    
    const ENVIRONMENT_TEST = 'test';
    const ENVIRONMENT_PRODUCTION = 'production';
    
    /**
     * Get invoice CzUid
     * 
     * @return string
     */
    public function getInvoiceCzUid(): string
    {
        return $this->getAttribute('invoice_cz_uid', '');
    }
    
    /**
     * Get ANAF upload ID
     * 
     * @return string|null
     */
    public function getUploadId(): ?string
    {
        return $this->getAttribute('upload_id');
    }
    
    /**
     * Get ANAF message ID
     * 
     * @return string|null
     */
    public function getMessageId(): ?string
    {
        return $this->getAttribute('message_id');
    }
    
    /**
     * Get status
     * 
     * @return string
     */
    public function getStatus(): string
    {
        return $this->getAttribute('status', self::STATUS_PENDING);
    }
    
    /**
     * Get status label
     * 
     * @return string
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => 'În așteptare',
            self::STATUS_UPLOADING => 'Se încarcă',
            self::STATUS_UPLOADED => 'Încărcat',
            self::STATUS_VALIDATING => 'Se validează',
            self::STATUS_VALID => 'Valid',
            self::STATUS_INVALID => 'Invalid',
            self::STATUS_PROCESSING => 'Se procesează',
            self::STATUS_PROCESSED => 'Procesat',
            self::STATUS_ERROR => 'Eroare',
            self::STATUS_REJECTED => 'Respins',
        ];
        
        return $labels[$this->getStatus()] ?? 'Necunoscut';
    }
    
    /**
     * Check if submission is pending
     * 
     * @return bool
     */
    public function isPending(): bool
    {
        return in_array($this->getStatus(), [
            self::STATUS_PENDING,
            self::STATUS_UPLOADING,
            self::STATUS_VALIDATING,
            self::STATUS_PROCESSING
        ], true);
    }
    
    /**
     * Check if submission is successful
     * 
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return in_array($this->getStatus(), [
            self::STATUS_VALID,
            self::STATUS_PROCESSED
        ], true);
    }
    
    /**
     * Check if submission failed
     * 
     * @return bool
     */
    public function isFailed(): bool
    {
        return in_array($this->getStatus(), [
            self::STATUS_INVALID,
            self::STATUS_ERROR,
            self::STATUS_REJECTED
        ], true);
    }
    
    /**
     * Get environment
     * 
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->getAttribute('environment', self::ENVIRONMENT_TEST);
    }
    
    /**
     * Check if test environment
     * 
     * @return bool
     */
    public function isTestEnvironment(): bool
    {
        return $this->getEnvironment() === self::ENVIRONMENT_TEST;
    }
    
    /**
     * Check if production environment
     * 
     * @return bool
     */
    public function isProductionEnvironment(): bool
    {
        return $this->getEnvironment() === self::ENVIRONMENT_PRODUCTION;
    }
    
    /**
     * Get ANAF response
     * 
     * @return array
     */
    public function getAnafResponse(): array
    {
        return $this->getAttribute('anaf_response', []);
    }
    
    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->getAttribute('errors', []);
    }
    
    /**
     * Check if has errors
     * 
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->getErrors());
    }
    
    /**
     * Get error messages as string
     * 
     * @return string
     */
    public function getErrorMessages(): string
    {
        $errors = $this->getErrors();
        
        if (empty($errors)) {
            return '';
        }
        
        // Handle different error formats
        if (isset($errors['message'])) {
            return $errors['message'];
        }
        
        if (isset($errors['errors']) && is_array($errors['errors'])) {
            return implode('; ', array_map(function ($error) {
                return is_array($error) ? ($error['message'] ?? json_encode($error)) : $error;
            }, $errors['errors']));
        }
        
        return json_encode($errors);
    }
    
    /**
     * Get download ID for signed XML
     * 
     * @return string|null
     */
    public function getDownloadId(): ?string
    {
        return $this->getAttribute('download_id');
    }
    
    /**
     * Check if signed XML is available
     * 
     * @return bool
     */
    public function hasSignedXml(): bool
    {
        return !empty($this->getDownloadId()) || !empty($this->getAttribute('signed_xml_content'));
    }
    
    /**
     * Get submission timestamp
     * 
     * @return string|null
     */
    public function getSubmittedAt(): ?string
    {
        return $this->getAttribute('submitted_at');
    }
    
    /**
     * Get validation timestamp
     * 
     * @return string|null
     */
    public function getValidatedAt(): ?string
    {
        return $this->getAttribute('validated_at');
    }
    
    /**
     * Get processing timestamp
     * 
     * @return string|null
     */
    public function getProcessedAt(): ?string
    {
        return $this->getAttribute('processed_at');
    }
    
    /**
     * Get processing duration in seconds
     * 
     * @return int|null
     */
    public function getProcessingDuration(): ?int
    {
        $submitted = $this->getSubmittedAt();
        $processed = $this->getProcessedAt();
        
        if (!$submitted || !$processed) {
            return null;
        }
        
        return strtotime($processed) - strtotime($submitted);
    }
    
    /**
     * Get status color for UI
     * 
     * @return string
     */
    public function getStatusColor(): string
    {
        if ($this->isPending()) {
            return 'warning';
        }
        
        if ($this->isSuccessful()) {
            return 'success';
        }
        
        if ($this->isFailed()) {
            return 'danger';
        }
        
        return 'secondary';
    }
}