<?php

/**
 * Contazen PHP SDK - Invoice List Example
 * 
 * This example demonstrates how to retrieve and work with invoices
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Contazen\Client;
use Contazen\Exceptions\ApiException;

// Initialize the client
$contazen = new Client('YOUR_API_TOKEN');

try {
    // Example 1: Get all invoices with default pagination
    echo "=== Example 1: Basic Invoice List ===\n";
    $invoices = $contazen->invoices->list();
    
    echo "Found {$invoices->count()} invoices on this page\n";
    echo "Total invoices: {$invoices->getTotalCount()}\n";
    echo "Current page: {$invoices->getCurrentPage()} of {$invoices->getTotalPages()}\n\n";
    
    // Iterate through invoices
    foreach ($invoices as $invoice) {
        echo "Invoice #{$invoice->getNumber()}: ";
        echo "{$invoice->getClient()->name} - ";
        echo "{$invoice->getFormattedTotal()}\n";
    }
    
    // Example 2: Filter invoices
    echo "\n=== Example 2: Filtered Invoice List ===\n";
    $filteredInvoices = $contazen->invoices->list([
        'status' => 'unpaid',
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
        'client_cz_uid' => 'AbC123XyZ', // Filter by specific client
        'page' => 1,
        'per_page' => 50
    ]);
    
    echo "Found {$filteredInvoices->count()} unpaid invoices\n\n";
    
    // Example 3: Working with collections
    echo "=== Example 3: Collection Methods ===\n";
    
    // Get unpaid invoices from collection
    $unpaidInvoices = $invoices->getUnpaid();
    echo "Unpaid invoices: " . count($unpaidInvoices) . "\n";
    
    // Get overdue invoices
    $overdueInvoices = $invoices->getOverdue();
    echo "Overdue invoices: " . count($overdueInvoices) . "\n";
    
    // Calculate total amount
    $totalAmount = $invoices->getTotalAmount();
    echo "Total amount: " . number_format($totalAmount, 2) . " RON\n";
    
    // Sort by date (newest first)
    $sortedByDate = $invoices->sortByDate('desc');
    echo "\nMost recent invoice: ";
    if ($mostRecent = $sortedByDate[0] ?? null) {
        echo "{$mostRecent->getNumber()} from {$mostRecent->getDate()}\n";
    }
    
    // Example 4: Search for specific invoice
    echo "\n=== Example 4: Search Specific Invoice ===\n";
    $searchResults = $contazen->invoices->list([
        'search' => 'INV-2024-001', // Search by invoice number
    ]);
    
    if (!$searchResults->isEmpty()) {
        $invoice = $searchResults->first();
        echo "Found invoice: {$invoice->getNumber()}\n";
        echo "Client: {$invoice->getClient()->name}\n";
        echo "Date: {$invoice->getDate()}\n";
        echo "Due date: {$invoice->getDueDate()}\n";
        echo "Status: {$invoice->getStatus()}\n";
        echo "Total: {$invoice->getFormattedTotal()}\n";
    }
    
    // Example 5: Pagination
    echo "\n=== Example 5: Pagination ===\n";
    $page = 1;
    $allInvoices = [];
    
    do {
        $batch = $contazen->invoices->list([
            'page' => $page,
            'per_page' => 100
        ]);
        
        foreach ($batch as $invoice) {
            $allInvoices[] = $invoice;
        }
        
        echo "Page {$page}: Retrieved {$batch->count()} invoices\n";
        $page++;
        
    } while ($batch->hasNextPage() && $page <= 5); // Limit to 5 pages for example
    
    echo "Total invoices retrieved: " . count($allInvoices) . "\n";
    
    // Example 6: Get invoices by status
    echo "\n=== Example 6: Group by Status ===\n";
    $statuses = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
    
    foreach ($statuses as $status) {
        $statusInvoices = $contazen->invoices->list(['status' => $status]);
        echo ucfirst($status) . ": {$statusInvoices->getTotalCount()} invoices\n";
    }
    
    // Example 7: Export invoice data
    echo "\n=== Example 7: Export Data ===\n";
    $exportData = [];
    
    foreach ($invoices as $invoice) {
        $exportData[] = [
            'number' => $invoice->getNumber(),
            'date' => $invoice->getDate(),
            'client' => $invoice->getClient()->name,
            'subtotal' => $invoice->getSubtotal(),
            'tax' => $invoice->getTax(),
            'total' => $invoice->getTotal(),
            'status' => $invoice->getStatus(),
            'paid' => $invoice->isPaid() ? 'Yes' : 'No'
        ];
    }
    
    // Save to CSV
    $csvFile = __DIR__ . '/invoices_export.csv';
    $fp = fopen($csvFile, 'w');
    
    // Write headers
    fputcsv($fp, array_keys($exportData[0] ?? []));
    
    // Write data
    foreach ($exportData as $row) {
        fputcsv($fp, $row);
    }
    
    fclose($fp);
    echo "Exported to: {$csvFile}\n";
    
    // Example 8: Statistics
    echo "\n=== Example 8: Statistics ===\n";
    $currentMonth = date('Y-m');
    $monthlyInvoices = $contazen->invoices->list([
        'start_date' => $currentMonth . '-01',
        'end_date' => date('Y-m-t') // Last day of month
    ]);
    
    $stats = [
        'total_invoices' => $monthlyInvoices->getTotalCount(),
        'total_amount' => $monthlyInvoices->getTotalAmount(),
        'paid_count' => count($monthlyInvoices->getPaid()),
        'unpaid_count' => count($monthlyInvoices->getUnpaid()),
        'overdue_count' => count($monthlyInvoices->getOverdue())
    ];
    
    echo "Monthly Statistics for {$currentMonth}:\n";
    echo "- Total invoices: {$stats['total_invoices']}\n";
    echo "- Total amount: " . number_format($stats['total_amount'], 2) . " RON\n";
    echo "- Paid: {$stats['paid_count']}\n";
    echo "- Unpaid: {$stats['unpaid_count']}\n";
    echo "- Overdue: {$stats['overdue_count']}\n";
    
    // Calculate collection rate
    if ($stats['total_invoices'] > 0) {
        $collectionRate = ($stats['paid_count'] / $stats['total_invoices']) * 100;
        echo "- Collection rate: " . number_format($collectionRate, 1) . "%\n";
    }
    
} catch (ApiException $e) {
    echo "API Error: " . $e->getMessage() . "\n";
    echo "Status Code: " . $e->getCode() . "\n";
    
    if ($errors = $e->getErrors()) {
        echo "Errors:\n";
        print_r($errors);
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}