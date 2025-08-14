# Contazen PHP SDK Examples

This directory contains practical examples of using the Contazen PHP SDK.

## Getting Started

1. Install the SDK:
```bash
composer require contazen/php-sdk
```

2. Get your API token from Contazen dashboard

3. Run the examples:
```bash
php quick-start.php
```

## Diagnostic Tools

### diagnose.php
Check your PHP environment and SDK installation:
```bash
php diagnose.php
```
This will verify:
- PHP version compatibility
- Composer autoload configuration
- Required PHP extensions
- Network connectivity
- SDK initialization

### test-connection.php
Test your API connection:
```bash
php test-connection.php
```
This will:
- Verify API authentication
- Test basic API endpoints
- Show rate limit information
- List recent invoices and clients

## Available Examples

### quick-start.php
A comprehensive introduction covering:
- SDK initialization
- Creating invoices
- Managing clients
- Product catalog
- Payment recording
- Error handling

### invoice-list.php
Working with invoice lists:
- Basic listing with pagination
- Filtering by status, date, client
- Using collection methods
- Sorting and searching
- Exporting to CSV
- Monthly statistics

### invoice-create.php
Various invoice creation scenarios:
- Simple invoice
- Using existing clients
- Products from catalog
- Proforma invoices
- Recurring invoices
- E-Factura compliance
- Custom numbering
- Bulk creation
- Email sending

## Common Patterns

### Authentication
```php
$contazen = new Client('YOUR_API_TOKEN');
```

### Error Handling
```php
try {
    $invoice = $contazen->invoices->create($data);
} catch (ValidationException $e) {
    // Handle validation errors
    foreach ($e->getErrors() as $field => $errors) {
        echo "$field: " . implode(', ', $errors) . "\n";
    }
} catch (ApiException $e) {
    // Handle API errors
    echo "API Error: " . $e->getMessage();
}
```

### Working with Collections
```php
$invoices = $contazen->invoices->list();

// Get statistics
$total = $invoices->getTotalAmount();
$unpaid = $invoices->getUnpaid();
$overdue = $invoices->getOverdue();

// Sort and filter
$sorted = $invoices->sortByDate('desc');
$filtered = $invoices->getByStatus('paid');
```

### Pagination
```php
$page = 1;
do {
    $batch = $contazen->invoices->list([
        'page' => $page++,
        'per_page' => 100
    ]);
    
    foreach ($batch as $invoice) {
        // Process invoice
    }
} while ($batch->hasNextPage());
```

### Romanian Business Rules
```php
use Contazen\Validators\CuiValidator;
use Contazen\Validators\IbanValidator;

// Validate CUI
if (!CuiValidator::validate($cui)) {
    throw new Exception('Invalid CUI');
}

// Validate IBAN
if (!IbanValidator::validateRomanian($iban)) {
    throw new Exception('Invalid Romanian IBAN');
}
```

## E-Factura Integration

```php
// Create invoice with E-Factura
$invoice = $contazen->invoices->create([
    'client' => [
        'name' => 'Instituție Publică',
        'cui' => '12345678',
        'require_efactura' => true
    ],
    'items' => [...],
    'enable_efactura' => true
]);

// Submit to ANAF
$response = $contazen->efactura->submit($invoice->cz_uid);

// Check status
$status = $contazen->efactura->getStatus($response->cz_uid);
echo "E-Factura status: " . $status->getStatusLabel();
```

## Environment Variables

For production use, store credentials in environment variables:

```bash
export CONTAZEN_API_TOKEN="your_token_here"
export CONTAZEN_FIRM_ID="123"
```

```php
$contazen = new Client(
    getenv('CONTAZEN_API_TOKEN'),
    ['firm_id' => getenv('CONTAZEN_FIRM_ID')]
);
```

## Performance Tips

1. **Use caching** for frequently accessed data:
```php
$contazen = new Client($token, [
    'cache' => $psrCacheImplementation
]);
```

2. **Batch operations** when possible:
```php
// Instead of multiple API calls
foreach ($items as $item) {
    $contazen->products->create($item);
}

// Use bulk creation
$contazen->products->bulkCreate($items);
```

3. **Selective field loading**:
```php
// Only get necessary fields
$invoices = $contazen->invoices->list([
    'fields' => ['number', 'date', 'total', 'status']
]);
```

## Support

For more information:
- [SDK Documentation](https://github.com/contazen/php-sdk)
- [API Reference](https://api.contazen.ro/docs)
- [Support](https://contazen.ro/support)