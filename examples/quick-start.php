<?php

/**
 * Contazen PHP SDK - Quick Start Guide
 *
 * This example shows the most common operations to get started quickly
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Contazen\Client;
use Contazen\Exceptions\ApiException;

// 1. Initialize the SDK
// Replace with your actual API token from Contazen dashboard
$contazen = new Client( 'YOUR_API_TOKEN', [
	'timeout'        => 30,             // Request timeout in seconds
	'retry_attempts' => 3,       // Number of retry attempts
	'debug'          => true,             // Enable debug mode to see requests
] );

try {
	// 2. Test connection by getting settings
	echo "<pre>\n";
	echo "Testing connection...\n";
	echo "API URL: " . $contazen->getConfig()->getApiUrl() . "\n\n";

	try {
		$settings = $contazen->settings->get();
		echo "✅ Connected successfully!\n";
		echo "Company: {$settings->getCompanyName()}\n";
		echo "CUI: {$settings->getCui()}\n\n";
	} catch ( \Exception $e ) {
		echo "❌ Connection failed: " . $e->getMessage() . "\n";
		echo "Make sure to replace 'YOUR_API_TOKEN' with your actual API token from Contazen dashboard.\n\n";
		// Continue with mock data for demonstration
		echo "Continuing with example data for demonstration...\n\n";
	}

	// 4. List recent invoices
	echo "Recent invoices:\n";
	$invoices = $contazen->invoices->list( [ 'limit' => 5 ] );

	foreach ( $invoices as $inv ) {
		echo "- {$inv->getNumber()}: {$inv->getClient()->name} - {$inv->getFormattedTotal()}\n";
	}

	// 5. Create or update a client
	echo "\nManaging clients...\n";
	$client = $contazen->clients->create( [
		'name'    => 'Client Nou SRL', // Required
		'email'   => 'contact@clientnou.ro', // Required
		'phone'   => '0721234567',
		'cui'     => '12345678', // Romanian CUI
		'address' => 'Str. Client nr. 10',
		'city'    => 'Cluj-Napoca',
		'county'  => 'Cluj',
		'country' => 'RO'
	] );

	echo "Client created: {$client->getName()}\n";

	// 6. Add a product to catalog
	echo "\nAdding product to catalog...\n";
	$product = $contazen->products->create( [
		'name'            => 'Serviciu Standard',
		'sku'             => 'SRV-001',
		'price'           => 500.00,
		'vat_rate'        => 19,
		'unit_of_measure' => 'buc',
		'type'            => 'service'
	] );

	echo "Product added: {$product->getName()} (SKU: {$product->getSku()})\n";

	// 7. Download invoice PDF
	echo "\nDownloading invoice PDF...\n";
	$pdfContent = $contazen->invoices->pdf( $invoice->cz_uid );
	file_put_contents( 'invoice_' . $invoice->getNumber() . '.pdf', $pdfContent );
	echo "PDF saved as: invoice_{$invoice->getNumber()}.pdf\n";

	// 8. Mark invoice as paid
	echo "\nMarking invoice as paid...\n";
	$payment = $contazen->payments->create( [
		'invoice_cz_uid' => $invoice->cz_uid,
		'amount'         => $invoice->getTotal(),
		'payment_date'   => date( 'Y-m-d' ),
		'payment_method' => 'bank_transfer'
	] );

	echo "Payment recorded successfully\n";

	// 9. Search functionality
	echo "\nSearching...\n";
	$searchResults = $contazen->clients->search( 'Client' );
	echo "Found {$searchResults->count()} clients matching 'Client'\n";

	// 10. Error handling example
	echo "\nError handling example...\n";
	try {
		// Try to get a non-existent invoice
		$contazen->invoices->get( 'invalid_id' );
	} catch ( ApiException $e ) {
		echo "Caught expected error: {$e->getMessage()}\n";
	}

	echo "\n✅ Quick start completed successfully!\n";

	echo "</pre>\n";

} catch ( ApiException $e ) {
	echo "<pre>\n";
	echo "❌ API Error: {$e->getMessage()}\n";
	echo "Status Code: {$e->getCode()}\n\n";

	if ( $e->getCode() === 401 ) {
		echo "Authentication failed. Please check your API token.\n";
		echo "Get your API token from: https://contazen.ro/settings/api\n";
	} elseif ( $e->getCode() === 404 ) {
		echo "Endpoint not found. The API structure might have changed.\n";
	} elseif ( $e->getCode() === 500 ) {
		echo "Server error. Please try again later.\n";
	}
	echo "</pre>\n";
} catch ( \Exception $e ) {
	echo "<pre>\n";
	echo "❌ Error: {$e->getMessage()}\n";
	echo "Type: " . get_class( $e ) . "\n";
	if ( method_exists( $e, 'getResponse' ) ) {
		echo "Response: " . $e->getResponse() . "\n";
	}
	echo "</pre>\n";
}

// Pro tips
echo "\n=== Pro Tips ===\n";
echo "1. Use environment variables for API tokens:\n";
echo "   \$contazen = new Client(getenv('CONTAZEN_API_TOKEN'));\n\n";

echo "2. Enable logging for debugging:\n";
echo "   \$contazen = new Client(\$token, ['logger' => \$psrLogger]);\n\n";

echo "3. Use caching for better performance:\n";
echo "   \$contazen = new Client(\$token, ['cache' => \$psrCache]);\n\n";

echo "4. Handle rate limiting:\n";
echo "   The SDK automatically handles rate limits with exponential backoff\n\n";

echo "5. Validate data before sending:\n";
echo "   use Contazen\\Validators\\CuiValidator;\n";
echo "   if (!CuiValidator::validate(\$cui)) { ... }\n";