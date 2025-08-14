<?php

declare(strict_types=1);

namespace Contazen\Resources;

use Contazen\Collections\ProductCollection;
use Contazen\Models\Product;

/**
 * Products API resource
 * 
 * Handles all product catalog operations
 */
class Products extends Resource
{
    /**
     * List products with optional filters
     * 
     * @param array $filters Available filters:
     *   - page: int Page number (default: 1)
     *   - per_page: int Items per page (default: 50, max: 100)
     *   - search: string Search term (name, code, barcode)
     *   - category_id: string Filter by category
     *   - is_active: bool Filter by active status
     *   - sort: string Sort field (name, price, created_at)
     *   - order: string Sort order (asc, desc)
     * 
     * @return ProductCollection
     */
    public function list(array $filters = []): ProductCollection
    {
        $params = $this->buildQueryParams($filters, [
            'page' => 1,
            'per_page' => 50,
        ]);
        
        $response = $this->http->get('/products', $params);
        return ProductCollection::fromResponse($response);
    }
    
    /**
     * Get a single product by CzUid
     * 
     * @param string $czUid Product CzUid
     * @return Product
     */
    public function get(string $czUid): Product
    {
        $response = $this->http->get("/products/{$czUid}");
        return Product::fromArray($response->getData());
    }
    
    /**
     * Create a new product
     * 
     * @param array $data Product data:
     *   - name: string Product name (required)
     *   - price: float Product price (required)
     *   - description: string Product description
     *   - currency: string Currency code (default: RON)
     *   - vat_rate: float VAT rate percentage (default: 19)
     *   - unit_of_measure: string Unit of measure (default: buc)
     *   - sku: string Stock keeping unit
     *   - barcode: string Barcode
     *   - category_id: string Category ID
     *   - is_active: bool Active status
     *   - is_service: bool Is service (not physical product)
     *   - track_stock: bool Track stock levels
     *   - stock_quantity: int Current stock quantity
     *   - ubl_um: string UBL unit of measure code
     *   - ubl_nc: string UBL nomenclature code
     *   - ubl_cpv: string UBL CPV code
     * 
     * @return Product
     */
    public function create(array $data): Product
    {
        $this->validateRequired($data, ['name', 'price']);
        
        // Auto-fill firm_id if configured
        if (!isset($data['firm_id']) && $this->http->getConfig()->getFirmId()) {
            $data['firm_id'] = $this->http->getConfig()->getFirmId();
        }
        
        $preparedData = $this->prepareProductData($data);
        $response = $this->http->post('/products', $preparedData);
        
        // API might return empty response on successful creation
        $responseData = $response->getData();
        if (empty($responseData)) {
            // Return a product with the data we sent
            return Product::fromArray($preparedData);
        }
        
        return Product::fromArray($responseData);
    }
    
    /**
     * Update an existing product
     * 
     * @param string $czUid Product CzUid
     * @param array $data Data to update
     * @return Product
     */
    public function update(string $czUid, array $data): Product
    {
        $preparedData = $this->prepareData($data);
        $response = $this->http->patch("/products/{$czUid}", $preparedData);
        
        return Product::fromArray($response->getData());
    }
    
    /**
     * Delete a product
     * 
     * @param string $czUid Product CzUid
     * @return bool
     */
    public function delete(string $czUid): bool
    {
        $response = $this->http->delete("/products/{$czUid}");
        return $response->isSuccess();
    }
    
    /**
     * Search products by term
     * 
     * @param string $searchTerm Search term
     * @return ProductCollection
     */
    public function search(string $searchTerm): ProductCollection
    {
        return $this->list(['search' => $searchTerm]);
    }
    
    /**
     * Find product by SKU
     * 
     * @param string $sku Product SKU
     * @return Product|null
     */
    public function findBySku(string $sku): ?Product
    {
        $products = $this->search($sku);
        
        // Look for exact SKU match
        foreach ($products as $product) {
            if ($product->sku === $sku) {
                return $product;
            }
        }
        
        return null;
    }
    
    /**
     * Find product by barcode
     * 
     * @param string $barcode Product barcode
     * @return Product|null
     */
    public function findByBarcode(string $barcode): ?Product
    {
        $products = $this->search($barcode);
        
        // Look for exact barcode match
        foreach ($products as $product) {
            if ($product->barcode === $barcode) {
                return $product;
            }
        }
        
        return null;
    }
    
    /**
     * Sync product catalog
     * Efficiently creates or updates multiple products
     * 
     * @param array $products Array of product data
     * @return array Sync results
     */
    public function sync(array $products): array
    {
        $response = $this->http->post('/products/sync', ['products' => $products]);
        return $response->getData();
    }
    
    /**
     * Update stock level for a product
     * Note: This endpoint may not be available as the API indicates
     * stock management is not currently supported
     * 
     * @param string $czUidOrSku Product CzUid or SKU
     * @param int $quantity New stock quantity
     * @return Product
     */
    public function updateStock(string $czUidOrSku, int $quantity): Product
    {
        $response = $this->http->post("/products/{$czUidOrSku}/stock", [
            'quantity' => $quantity
        ]);
        
        return Product::fromArray($response->getData());
    }
    
    /**
     * Bulk update stock levels
     * 
     * @param array $updates Array of stock updates [['sku' => 'SKU1', 'quantity' => 10], ...]
     * @return array Update results
     */
    public function updateStockBulk(array $updates): array
    {
        $response = $this->http->post('/products/stock-bulk', ['updates' => $updates]);
        return $response->getData();
    }
    
    /**
     * Get stock levels for multiple products
     * 
     * @param array $skus Array of SKUs
     * @return array Stock levels by SKU
     */
    public function getStockLevels(array $skus): array
    {
        $response = $this->http->post('/products/stock-levels', ['skus' => $skus]);
        return $response->getData();
    }
    
    /**
     * Import products from CSV
     * 
     * @param string $csvContent CSV content
     * @return array Import results
     */
    public function importCsv(string $csvContent): array
    {
        $response = $this->http->post('/products/import', [
            'format' => 'csv',
            'content' => base64_encode($csvContent)
        ]);
        
        return $response->getData();
    }
    
    /**
     * Create or update product (upsert by SKU)
     * 
     * @param array $data Product data
     * @return Product
     */
    public function upsert(array $data): Product
    {
        // Try to find existing product by SKU
        if (!empty($data['sku'])) {
            $existing = $this->findBySku($data['sku']);
            if ($existing) {
                return $this->update($existing->cz_uid, $data);
            }
        }
        
        // Create new product
        return $this->create($data);
    }
    
    /**
     * Prepare product data for API
     * 
     * @param array $data
     * @return array
     */
    private function prepareProductData(array $data): array
    {
        // Set default values
        if (!isset($data['currency'])) {
            $data['currency'] = 'RON';
        }
        
        if (!isset($data['vat_rate'])) {
            $data['vat_rate'] = 19; // Default Romanian VAT rate
        }
        
        if (!isset($data['unit_of_measure'])) {
            $data['unit_of_measure'] = 'buc'; // Default unit (piece)
        }
        
        // Map common UBL unit codes
        if (!isset($data['ubl_um']) && isset($data['unit_of_measure'])) {
            $ublMap = [
                'buc' => 'H87',  // Piece
                'ore' => 'HUR',  // Hour
                'kg' => 'KGM',   // Kilogram
                'l' => 'LTR',    // Liter
                'm' => 'MTR',    // Meter
                'mp' => 'MTK',   // Square meter
                'mc' => 'MTQ',   // Cubic meter
                'set' => 'SET',  // Set
                'zi' => 'DAY',   // Day
                'luna' => 'MON', // Month
            ];
            
            $unit = strtolower($data['unit_of_measure']);
            if (isset($ublMap[$unit])) {
                $data['ubl_um'] = $ublMap[$unit];
            } else {
                $data['ubl_um'] = 'H87'; // Default to piece
            }
        }
        
        // Handle price field mapping
        if (isset($data['price']) && !isset($data['sell_price'])) {
            $data['sell_price'] = $data['price'];
        }
        
        return $this->prepareData($data);
    }
}