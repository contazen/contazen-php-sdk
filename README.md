# Contazen PHP SDK

A modern PHP SDK for the Contazen API - Romanian invoicing and accounting platform.

> **AI-Assisted Development**: This project includes a `CLAUDE.md` file that provides comprehensive context and guidelines for AI assistants like Claude, GitHub Copilot, and other LLMs. This helps accelerate development, ensures consistency with project conventions, and provides instant context about the codebase structure and best practices.

## Features

- 🚀 Simple and intuitive API
- 📦 PSR-4 autoloading compliant  
- 🔒 Secure authentication with Bearer tokens
- 🔄 Automatic retry logic with exponential backoff
- 💾 Built-in caching support (PSR-16)
- 📊 Comprehensive error handling
- 🇷🇴 Romanian business validations (CUI, IBAN, CNP)
- 📚 Well documented with examples

## Requirements

- PHP 7.4 or higher
- Composer
- Guzzle HTTP Client

## Installation

Install the SDK via Composer:

```bash
composer require contazen/php-sdk
```

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use Contazen\Client;

// Initialize the client
$contazen = new Client('YOUR_API_TOKEN');

// Test connection
$settings = $contazen->settings->get();
echo "Connected to: " . $settings->getCompanyName();
```

## Usage Examples

### Creating an Invoice

```php
// Create a simple invoice
$invoice = $contazen->invoices->create([
    'client_data' => [
        'name' => 'ACME Corporation SRL',    // Required
        'email' => 'contact@acme.ro',        // Required  
        'cui' => '12345678',                 // Optional but recommended
        'address' => 'Str. Exemplu nr. 1',   // Optional
        'city' => 'București, Sector 1',     // Optional (sector required for București)
        'county' => 'București',              // Optional
        'country' => 'RO'                    // Optional
    ],
    'document_type' => 'fiscal',             // Required: fiscal or proforma
    'date' => date('Y-m-d'),                 // Required
    'items' => [
        [
            'description' => 'Servicii dezvoltare web',
            'quantity' => 1,
            'price' => 5000,                 // Price without VAT
            'vat_rate' => 19                 // VAT percentage
        ],
        [
            'description' => 'Hosting anual',
            'quantity' => 1,
            'price' => 500,
            'vat_rate' => 19
        ]
    ],
    'currency' => 'RON',
    'due_days' => 30,                        // Payment terms
    'observations' => 'Vă mulțumim pentru colaborare!'
]);

echo "Invoice created: {$invoice->getNumber()}\n";
echo "Total: {$invoice->getFormattedTotal()}\n";

// Download PDF
$pdf = $contazen->invoices->downloadPdf($invoice->cz_uid);
file_put_contents('invoice.pdf', $pdf);
```

### Working with Clients

```php
// Create a client
$client = $contazen->clients->create([
    'name' => 'Client Nou SRL',              // Required
    'email' => 'contact@clientnou.ro',       // Required
    'phone' => '0721234567',                 
    'cui' => '87654321',                     
    'address' => 'Str. Comercială nr. 10',
    'city' => 'Cluj-Napoca',
    'county' => 'Cluj',
    'country' => 'RO'
]);

// Search for clients
$clients = $contazen->clients->search('SRL');
foreach ($clients as $client) {
    echo $client->name . " - " . $client->cui . "\n";
}

// List clients with filters
$clients = $contazen->clients->list([
    'page' => 1,
    'per_page' => 20,
    'search' => 'ACME',
    'sort' => 'name',
    'order' => 'asc'
]);
```

### Managing Products

```php
// Create a product
$product = $contazen->products->create([
    'name' => 'Serviciu Standard',           // Required
    'price' => 100,                          // Required
    'sku' => 'SRV-001',
    'vat_rate' => 19,
    'unit_of_measure' => 'buc',
    'type' => 'service'
]);

// List products
$products = $contazen->products->list([
    'per_page' => 50
]);

foreach ($products as $product) {
    echo $product->name . " - " . $product->price . " RON\n";
}
```

### Invoice Operations

```php
// Get an invoice
$invoice = $contazen->invoices->get('invoice_id');

// Update invoice (limited fields)
$updated = $contazen->invoices->update($invoice->cz_uid, [
    'observations' => 'Updated notes'
]);

// Mark as paid
$contazen->invoices->markAsPaid($invoice->cz_uid, [
    'amount' => $invoice->total,
    'date' => date('Y-m-d'),
    'method' => 'bank_transfer'
]);

// Void an invoice
$contazen->invoices->void($invoice->cz_uid, [
    'reason' => 'Customer request'
]);

// Convert proforma to fiscal
$fiscal = $contazen->invoices->convertToFiscal($proforma->cz_uid);
```

### Pagination

```php
// List invoices with pagination
$invoices = $contazen->invoices->list([
    'page' => 1,
    'per_page' => 50,
    'sort' => 'date',
    'order' => 'desc'
]);

// Access pagination info
echo "Total invoices: " . $invoices->getTotal() . "\n";
echo "Current page: " . $invoices->getCurrentPage() . "\n";
echo "Total pages: " . $invoices->getTotalPages() . "\n";

// Iterate through results
foreach ($invoices as $invoice) {
    echo "{$invoice->getNumber()} - {$invoice->getFormattedTotal()}\n";
}

// Get next page
if ($invoices->hasNextPage()) {
    $nextPage = $contazen->invoices->list(['page' => 2]);
}
```

### Error Handling

```php
use Contazen\Exceptions\{
    ValidationException,
    NotFoundException,
    RateLimitException,
    AuthenticationException,
    ApiException
};

try {
    $invoice = $contazen->invoices->create($data);
} catch (ValidationException $e) {
    // Handle validation errors
    echo "Validation failed: " . $e->getMessage() . "\n";
    foreach ($e->getErrors() as $field => $errors) {
        echo "  {$field}: " . implode(', ', $errors) . "\n";
    }
} catch (RateLimitException $e) {
    // Handle rate limiting
    $info = $e->getRateLimitInfo();
    echo "Rate limit exceeded. Retry after {$info['reset']} seconds\n";
} catch (AuthenticationException $e) {
    // Handle authentication errors
    echo "Authentication failed. Check your API token.\n";
} catch (NotFoundException $e) {
    // Handle not found errors
    echo "Resource not found.\n";
} catch (ApiException $e) {
    // Handle general API errors
    echo "API Error: " . $e->getMessage() . "\n";
}
```

## Romanian Business Validations

The SDK includes validators for Romanian business data:

```php
use Contazen\Validators\{
    CuiValidator,
    IbanValidator,
    CnpValidator,
    PhoneValidator
};

// Validate CUI (Company Tax ID)
if (CuiValidator::validate('12345678')) {
    echo "Valid CUI\n";
}

// Validate IBAN
if (IbanValidator::validate('RO49AAAA1B31007593840000')) {
    echo "Valid IBAN\n";
}

// Validate CNP (Personal ID)
if (CnpValidator::validate('1234567890123')) {
    echo "Valid CNP\n";
}

// Validate Romanian phone
if (PhoneValidator::validate('0721234567')) {
    echo "Valid phone number\n";
}
```

## Advanced Configuration

### Custom Configuration

```php
$contazen = new Client('YOUR_API_TOKEN', [
    'timeout' => 30,                    // Request timeout in seconds
    'retry_attempts' => 3,              // Number of retries
    'retry_delay' => 1000,              // Delay between retries (ms)
    'verify_ssl' => true,               // SSL verification
    'debug' => false,                   // Debug mode
    'user_agent' => 'MyApp/1.0'        // Custom user agent
]);
```

### Logging (PSR-3)

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('contazen');
$logger->pushHandler(new StreamHandler('contazen.log', Logger::DEBUG));

$client = new Client('YOUR_API_TOKEN', [
    'logger' => $logger
]);
```

### Caching (PSR-16)

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

$cache = new Psr16Cache(new FilesystemAdapter());

$client = new Client('YOUR_API_TOKEN', [
    'cache' => $cache,
    'cache_ttl' => 300  // Cache for 5 minutes
]);
```

## Using with Laravel

```php
// In config/services.php
'contazen' => [
    'token' => env('CONTAZEN_API_TOKEN'),
],

// In AppServiceProvider.php
use Contazen\Client;

public function register()
{
    $this->app->singleton(Client::class, function ($app) {
        return new Client(config('services.contazen.token'));
    });
}

// In your controller
public function createInvoice(Request $request, Client $contazen)
{
    $invoice = $contazen->invoices->create($request->validated());
    return response()->json($invoice);
}
```

## Using with WordPress

```php
// In your plugin
class My_Contazen_Plugin {
    private $contazen;
    
    public function __construct() {
        $this->contazen = new \Contazen\Client(
            get_option('contazen_api_token')
        );
    }
    
    public function create_invoice_from_order($order_id) {
        $order = wc_get_order($order_id);
        
        $invoice = $this->contazen->invoices->create([
            'client_data' => [
                'name' => $order->get_billing_company() ?: 
                         $order->get_billing_first_name() . ' ' . 
                         $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'address' => $order->get_billing_address_1(),
                'city' => $order->get_billing_city(),
                'county' => $order->get_billing_state(),
                'postal_code' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country()
            ],
            'document_type' => 'fiscal',
            'date' => date('Y-m-d'),
            'items' => array_map(function($item) {
                return [
                    'description' => $item->get_name(),
                    'quantity' => $item->get_quantity(),
                    'price' => $item->get_subtotal() / $item->get_quantity(),
                    'vat_rate' => 19
                ];
            }, $order->get_items()),
            'currency' => $order->get_currency()
        ]);
        
        // Save invoice number to order
        $order->update_meta_data('_contazen_invoice', $invoice->getNumber());
        $order->save();
        
        return $invoice;
    }
}
```

## API Resources

### Available Resources

- **Invoices** (`$contazen->invoices`) - Create, read, update, void invoices
- **Clients** (`$contazen->clients`) - Manage clients/customers
- **Products** (`$contazen->products`) - Product catalog management
- **Settings** (`$contazen->settings`) - Account settings
- **Series** (`$contazen->series`) - Invoice numbering series
- **Payments** (`$contazen->payments`) - Payment tracking
- **Bank Accounts** (`$contazen->bankAccounts`) - Bank account management
- **Expenses** (`$contazen->expenses`) - Expense tracking

### Rate Limiting

The SDK automatically handles rate limiting with exponential backoff. The API allows:
- 100 requests per minute
- Automatic retry on rate limit errors

## Examples

See the `examples/` directory for more detailed examples:
- `quick-start.php` - Getting started guide
- `invoice-create.php` - Various invoice creation scenarios
- `invoice-list.php` - Listing and filtering invoices
- `client-management.php` - Client CRUD operations
- `product-catalog.php` - Product management
- `error-handling.php` - Proper error handling
- `wordpress-integration.php` - WordPress/WooCommerce integration

## Testing

```bash
# Install dependencies
composer install

# Run tests (when available)
composer test
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

- 📧 Email: support@contazen.ro
- 🐛 Issues: [GitHub Issues](https://github.com/contazen/contazen-php-sdk/issues)
- 📖 API Docs: [https://docs.contazen.ro/api-reference](https://docs.contazen.ro/api-reference)

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) file for more information.

## Links

- [Contazen Website](https://contazen.ro)
- [API Documentation](https://docs.contazen.ro/api-reference)
- [Packagist Package](https://packagist.org/packages/contazen/php-sdk)