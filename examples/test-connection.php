<?php

/**
 * Contazen PHP SDK - Connection Test
 * 
 * Simple script to test your API connection
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Contazen\Client;

// Configuration
$apiToken = 'YOUR_API_TOKEN'; // Replace with your actual token
$apiUrl = 'https://api.contazen.ro/v1';

echo "<pre>\n";
echo "========================================\n";
echo "  Contazen API Connection Test\n";
echo "========================================\n\n";

// Check if token is set
if ($apiToken === 'YOUR_API_TOKEN') {
    echo "⚠️  WARNING: Please replace 'YOUR_API_TOKEN' with your actual API token\n";
    echo "   Get your token from: https://contazen.ro/settings/api\n\n";
}

echo "Configuration:\n";
echo "- API Token: " . substr($apiToken, 0, 10) . "..." . substr($apiToken, -4) . "\n";
echo "- API URL: {$apiUrl}\n\n";

try {
    // Initialize client
    echo "Initializing client...\n";
    $client = new Client($apiToken, [
        'api_url' => $apiUrl,
        'timeout' => 10,
        'debug' => true
    ]);
    
    echo "✅ Client initialized\n\n";
    
    // Test 1: Ping endpoint (if available)
    echo "Test 1: Ping API...\n";
    try {
        if ($client->ping()) {
            echo "✅ Ping successful\n\n";
        } else {
            echo "❌ Ping failed\n\n";
        }
    } catch (\Exception $e) {
        echo "⚠️  Ping endpoint not available: " . $e->getMessage() . "\n\n";
    }
    
    // Test 2: Get settings
    echo "Test 2: Get firm settings...\n";
    try {
        $settings = $client->settings->get();
        echo "✅ Settings retrieved successfully\n";
        echo "   Company: " . $settings->getCompanyName() . "\n";
        echo "   CUI: " . $settings->getCui() . "\n\n";
    } catch (\Exception $e) {
        echo "❌ Failed to get settings: " . $e->getMessage() . "\n\n";
    }
    
    // Test 3: List invoices
    echo "Test 3: List invoices...\n";
    try {
        $invoices = $client->invoices->list(['limit' => 5]);
        echo "✅ Invoices retrieved successfully\n";
        echo "   Found " . $invoices->count() . " invoices\n\n";
        
        if (!$invoices->isEmpty()) {
            echo "   Recent invoices:\n";
            foreach ($invoices as $invoice) {
                echo "   - " . $invoice->getNumber() . ": " . $invoice->getFormattedTotal() . "\n";
            }
        }
    } catch (\Exception $e) {
        echo "❌ Failed to list invoices: " . $e->getMessage() . "\n\n";
    }
    
    // Test 4: List clients
    echo "\nTest 4: List clients...\n";
    try {
        $clients = $client->clients->list(['limit' => 5]);
        echo "✅ Clients retrieved successfully\n";
        echo "   Found " . $clients->count() . " clients\n\n";
    } catch (\Exception $e) {
        echo "❌ Failed to list clients: " . $e->getMessage() . "\n\n";
    }
    
    // Summary
    echo "========================================\n";
    echo "✅ Connection test completed!\n";
    echo "========================================\n";
    
} catch (\Contazen\Exceptions\AuthenticationException $e) {
    echo "\n❌ Authentication Error\n";
    echo "========================================\n";
    echo "The API token is invalid or expired.\n";
    echo "Please check your token and try again.\n";
    echo "\nGet a new token from:\n";
    echo "https://contazen.ro/settings/api\n";
    
} catch (\Contazen\Exceptions\ApiException $e) {
    echo "\n❌ API Error\n";
    echo "========================================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    
    if ($e->getErrors()) {
        echo "Details:\n";
        print_r($e->getErrors());
    }
    
} catch (\Exception $e) {
    echo "\n❌ Unexpected Error\n";
    echo "========================================\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Type: " . get_class($e) . "\n";
    echo "\nThis might be a configuration issue.\n";
    echo "Please check:\n";
    echo "1. Composer autoload is working\n";
    echo "2. All required packages are installed\n";
    echo "3. PHP version is 7.4 or higher\n";
}

echo "</pre>\n";