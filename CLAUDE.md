# CLAUDE.md - Contazen PHP SDK

This file provides guidance to AI assistants (like Claude, GitHub Copilot, and other LLMs) when working with the Contazen PHP SDK project. It helps accelerate development by providing context about project structure, conventions, and best practices.

## Project Overview

The Contazen PHP SDK is a modern, PSR-compliant PHP library that provides a clean interface to the Contazen API. It's designed to be used standalone or integrated into frameworks like Laravel, Symfony, or WordPress.

### Key Context
- **Purpose**: Official PHP SDK for Contazen.ro - Romanian invoicing and accounting platform
- **Architecture**: Object-oriented, PSR-compliant, framework-agnostic
- **Main API**: RESTful JSON API with Bearer token authentication
- **Target Users**: PHP developers integrating invoicing into their applications

## Development Standards

### PHP Version Support
- Minimum PHP 7.4 (for typed properties)
- Full PHP 8.0+ support with union types where applicable
- Always use strict typing: `declare(strict_types=1);`

### Coding Standards
- Follow PSR-12 coding standard
- Use PSR-4 autoloading
- Implement PSR-7 for HTTP messages where applicable
- Use PSR-3 for logging interfaces
- Follow PSR-18 for HTTP client abstraction

### Architecture Principles
1. **Separation of Concerns**: Keep HTTP, models, and business logic separate
2. **Dependency Injection**: Use constructor injection, avoid singletons
3. **Immutability**: Prefer immutable objects where possible
4. **Type Safety**: Use type hints and return types everywhere
5. **Fail Fast**: Validate early, throw specific exceptions
6. **Testability**: Design for testing, use interfaces for external dependencies

## Project Structure

```
contazen-php-sdk/
├── src/                    # Source code
│   ├── Client.php         # Main SDK client
│   ├── ClientBuilder.php  # Fluent client builder
│   ├── Config.php         # Configuration class
│   ├── Resources/         # API resource classes
│   │   ├── Resource.php   # Base resource class
│   │   ├── Invoices.php   # Invoice operations
│   │   ├── Clients.php    # Client operations
│   │   ├── Products.php   # Product operations
│   │   ├── EFactura.php   # E-Factura operations
│   │   └── ...
│   ├── Models/           # Data models
│   │   ├── Model.php     # Base model class
│   │   ├── Invoice.php   # Invoice model
│   │   ├── Client.php    # Client model
│   │   └── ...
│   ├── Collections/      # Collection classes
│   │   ├── Collection.php        # Base collection
│   │   ├── InvoiceCollection.php # Invoice collection
│   │   └── ...
│   ├── Http/             # HTTP layer
│   │   ├── HttpClient.php # HTTP client wrapper
│   │   └── Response.php   # Response wrapper
│   ├── Exceptions/       # Custom exceptions
│   │   ├── ContazenException.php    # Base exception
│   │   ├── ApiException.php         # API errors
│   │   ├── ValidationException.php  # Validation errors
│   │   └── ...
│   ├── Validators/       # Romanian business validators
│   │   ├── CuiValidator.php  # Company tax ID
│   │   ├── IbanValidator.php # Bank account
│   │   ├── CnpValidator.php  # Personal ID
│   │   └── ...
│   └── Utils/            # Utility classes
├── tests/                # Test suite
│   ├── Unit/            # Unit tests
│   ├── Integration/     # Integration tests
│   └── Fixtures/        # Test fixtures
├── examples/            # Usage examples
└── docs/               # Documentation
```

## API Integration Notes

### Authentication
- Uses Bearer token authentication
- Token passed in Authorization header: `Bearer {token}`
- Tokens obtained from Contazen dashboard

### Base URL
- Production: `https://api.contazen.ro/v1`
- Sandbox/Test: `https://api-test.contazen.ro/v1` (planned)

### Rate Limiting
- API allows 100 requests per minute
- Check X-RateLimit headers in responses
- Implement exponential backoff on 429 responses
- SDK should handle this automatically

### CzUid System
- **IMPORTANT**: Contazen uses CzUid instead of numeric IDs in public APIs
- Format: alphanumeric string (e.g., "AbC123XyZ")
- Always use CzUid for resource identification
- Never expose internal numeric IDs

### API Response Format
```json
{
    "success": true,
    "data": { ... },
    "message": "Success message",
    "errors": null
}
```

Error response:
```json
{
    "success": false,
    "data": null,
    "message": "Error message",
    "errors": {
        "field_name": ["Error message 1", "Error message 2"]
    }
}
```

## Common Implementation Patterns

### Resource Class Pattern
```php
namespace Contazen\Resources;

class Invoices extends Resource
{
    protected string $endpoint = 'invoices';
    
    public function create(array $data): Invoice
    {
        $response = $this->client->post($this->endpoint, $data);
        return new Invoice($response['data']);
    }
    
    public function list(array $params = []): InvoiceCollection
    {
        $response = $this->client->get($this->endpoint, $params);
        return new InvoiceCollection($response['data'], $response['meta']);
    }
}
```

### Model Pattern
```php
namespace Contazen\Models;

class Invoice extends Model
{
    protected array $fillable = [
        'cz_uid', 'number', 'date', 'due_date',
        'client', 'items', 'total', 'currency'
    ];
    
    protected array $casts = [
        'date' => 'datetime',
        'due_date' => 'datetime',
        'items' => 'array',
        'total' => 'float'
    ];
    
    public function getFormattedTotal(): string
    {
        return number_format($this->total, 2) . ' ' . $this->currency;
    }
}
```

### Exception Handling
```php
try {
    $invoice = $contazen->invoices->create($data);
} catch (ValidationException $e) {
    // Handle validation errors - $e->getErrors() returns field errors
} catch (RateLimitException $e) {
    // Handle rate limiting - $e->getRetryAfter() returns seconds
} catch (ApiException $e) {
    // Handle general API errors
}
```

## Romanian Business Logic

### CUI (Company Tax ID) Validation
- Format: RO + 2-10 digits
- Must validate checksum algorithm
- API can validate with ANAF

### IBAN Validation
- Romanian IBANs start with RO
- 24 characters total
- Validate checksum

### CNP (Personal ID) Validation
- 13 digits
- First digit indicates century and gender
- Validate checksum

### E-Factura Integration
- XML format required by ANAF
- Must be signed digitally
- Async submission process
- Track submission status

## Testing Guidelines

### Unit Tests
```php
class InvoiceTest extends TestCase
{
    public function test_can_create_invoice(): void
    {
        $client = $this->mockClient([
            'success' => true,
            'data' => ['cz_uid' => 'ABC123', ...]
        ]);
        
        $invoice = $client->invoices->create([...]);
        
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals('ABC123', $invoice->cz_uid);
    }
}
```

### Integration Tests
- Test against real API (test environment)
- Use fixtures for consistent data
- Clean up after tests

## Code Generation Guidelines

When generating code for this SDK:

1. **Always use strict types**: Start files with `declare(strict_types=1);`
2. **Use type hints**: All parameters and return types
3. **Follow PSR-12**: Formatting and naming conventions
4. **Add PHPDoc blocks**: For all public methods
5. **Throw specific exceptions**: Not generic Exception class
6. **Validate input**: Check required fields, types, formats
7. **Return consistent types**: Models for single items, Collections for lists
8. **Use fluent interfaces**: Where it makes sense (builders, setters)
9. **Make it testable**: Inject dependencies, avoid static calls
10. **Handle null safely**: Use null coalescing operator (??)

## Common Tasks and Solutions

### Adding a New Resource
1. Create resource class in `src/Resources/`
2. Create model class in `src/Models/`
3. Create collection class in `src/Collections/`
4. Add resource method to Client class
5. Add tests in `tests/Unit/Resources/`
6. Update examples in `examples/`

### Adding a New Validator
1. Create validator class in `src/Validators/`
2. Implement static `validate()` method
3. Add unit tests with valid/invalid cases
4. Document validation rules

### Implementing Caching
```php
use Psr\SimpleCache\CacheInterface;

class CachedClient extends Client
{
    private CacheInterface $cache;
    private int $ttl = 300; // 5 minutes
    
    public function get(string $endpoint, array $params = []): array
    {
        $key = $this->getCacheKey($endpoint, $params);
        
        if ($this->cache->has($key)) {
            return $this->cache->get($key);
        }
        
        $response = parent::get($endpoint, $params);
        $this->cache->set($key, $response, $this->ttl);
        
        return $response;
    }
}
```

## API Endpoints Reference

### Main Endpoints
- `GET/POST /invoices` - Invoice management
- `GET/POST /clients` - Client management
- `GET/POST /products` - Product catalog
- `GET/POST /series` - Invoice series
- `GET /settings` - Account settings
- `POST /efactura/submit` - E-Factura submission
- `GET /efactura/status/{id}` - Submission status

### Pagination Parameters
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20, max: 100)
- `sort` - Sort field
- `order` - Sort order (asc/desc)

### Common Filters
- `search` - Text search
- `date_from` - Start date (Y-m-d)
- `date_to` - End date (Y-m-d)
- `status` - Document status
- `client_id` - Filter by client CzUid

## Security Considerations

1. **Never log sensitive data**: Tokens, passwords, personal data
2. **Validate all input**: Type, format, business rules
3. **Sanitize for API**: Remove unexpected fields
4. **Use HTTPS only**: Reject non-HTTPS in production
5. **Implement request signing**: For webhooks
6. **Store credentials securely**: Use environment variables
7. **Mask sensitive data in exceptions**: Don't expose tokens in error messages

## Performance Guidelines

1. **Use lazy loading**: Don't fetch until needed
2. **Implement caching**: Cache GET requests
3. **Batch operations**: Group multiple operations
4. **Stream large files**: Don't load entirely in memory
5. **Connection pooling**: Reuse HTTP connections
6. **Pagination**: Always paginate list operations
7. **Selective fields**: Request only needed fields (when API supports)

## Version Management

- Follow Semantic Versioning (SemVer)
- Maintain backward compatibility in minor/patch releases
- Document breaking changes in CHANGELOG.md
- Tag releases in git: `v1.0.0`
- Update version constant in `Client::VERSION`

## Framework Integration Notes

### Laravel
- Create service provider
- Publish config file
- Use Laravel's cache and log implementations
- Integrate with Laravel's validation

### Symfony
- Create bundle
- Use Symfony's HTTP client
- Integrate with Symfony's cache
- Use Symfony's event dispatcher

### WordPress
- Create wrapper plugin class
- Use WordPress options for config
- Integrate with WooCommerce if present
- Handle WordPress coding standards differences

## Common Pitfalls to Avoid

1. **Don't expose numeric IDs**: Always use CzUid
2. **Don't ignore rate limits**: Implement proper backoff
3. **Don't assume field presence**: Check isset() before access
4. **Don't hardcode URLs**: Use config for base URL
5. **Don't mix concerns**: Keep HTTP separate from business logic
6. **Don't ignore timezones**: Use UTC for API, convert for display
7. **Don't trust user input**: Always validate and sanitize
8. **Don't catch all exceptions**: Be specific in catch blocks
9. **Don't use floats for money**: Use Money library or integers (cents)
10. **Don't forget to close resources**: Files, curl handles, etc.

## Development Workflow

1. **Branch from main**: Create feature branch
2. **Write tests first**: TDD approach
3. **Implement feature**: Follow patterns
4. **Run checks**: `composer check`
5. **Update docs**: README, examples, PHPDoc
6. **Create PR**: With description and tests
7. **Address review**: Fix issues
8. **Merge**: Squash commits if needed

## Useful Commands

```bash
# Install dependencies
composer install

# Run tests
composer test

# Run tests with coverage
composer test:coverage

# Check code style
composer cs

# Fix code style
composer cs-fix

# Run static analysis
composer phpstan

# Run all checks
composer check

# Update autoloader
composer dump-autoload
```

## External Dependencies

- **guzzlehttp/guzzle**: HTTP client
- **nesbot/carbon**: Date handling
- **moneyphp/money**: Money handling
- **psr/log**: Logging interface
- **psr/simple-cache**: Cache interface

## Resources

- [Contazen API Documentation](https://docs.contazen.ro/api-reference)
- [PSR Standards](https://www.php-fig.org/psr/)
- [PHP The Right Way](https://phptherightway.com/)
- [Guzzle Documentation](https://docs.guzzlephp.org/)
- [Romanian CUI Validation Algorithm](https://ro.wikipedia.org/wiki/Cod_de_identificare_fiscal%C4%83)

## Contributing Guidelines

When contributing to this SDK:

1. Follow existing patterns and conventions
2. Write tests for new features
3. Update documentation and examples
4. Ensure backward compatibility
5. Use descriptive commit messages
6. Keep PRs focused and small
7. Respond to review feedback promptly

## Support and Contact

- GitHub Issues: Bug reports and feature requests
- Email: support@contazen.ro
- Documentation: https://docs.contazen.ro