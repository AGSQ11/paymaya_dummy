<?php
if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

require_once __DIR__ . '/../paymaya_client.php';

use WHMCS\Database\Capsule;

// Retrieve the raw POST data
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Log callback data for debugging
logTransaction('PayMaya', $data, 'Callback Received');

if (!isset($data['referenceNumber']) || !isset($data['status'])) {
    logTransaction('PayMaya', $data, 'Invalid Callback Data');
    die('Invalid callback data');
}

$invoiceId = $data['referenceNumber'];
$status = strtoupper($data['status']);
$paymentId = $data['id'];
$amount = $data['amount']['value'] ?? 0;

try {
    $invoice = Capsule::table('tblinvoices')->where('id', $invoiceId)->first();
    if (!$invoice) {
        logTransaction('PayMaya', ['error' => 'Invoice not found'], 'Error');
        die('Invoice not found');
    }

    if ($status === 'COMPLETED') {
        addInvoicePayment($invoiceId, $paymentId, $amount, 0, 'paymaya');
        logTransaction('PayMaya', $data, 'Payment Successful');
    } elseif ($status === 'FAILED') {
        logTransaction('PayMaya', $data, 'Payment Failed');
    } else {
        logTransaction('PayMaya', $data, 'Payment Pending or Unknown Status');
    }
} catch (Exception $e) {
    logTransaction('PayMaya', ['error' => $e->getMessage()], 'Error');
}
