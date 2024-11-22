<?php
if (!defined('WHMCS')) {
    die('This file cannot be accessed directly.');
}

use WHMCS\Database\Capsule;

function paymaya_MetaData()
{
    return [
        'DisplayName' => 'PayMaya Payment Gateway',
        'APIVersion' => '1.1', // Current WHMCS API version
    ];
}

function paymaya_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'PayMaya Payment Gateway',
        ],
        'apiKey' => [
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Size' => '50',
            'Description' => 'Enter your PayMaya API Key.',
        ],
        'secretKey' => [
            'FriendlyName' => 'Secret Key',
            'Type' => 'password',
            'Size' => '50',
            'Description' => 'Enter your PayMaya Secret Key.',
        ],
        'environment' => [
            'FriendlyName' => 'Environment',
            'Type' => 'dropdown',
            'Options' => [
                'sandbox' => 'Sandbox',
                'production' => 'Production',
            ],
            'Description' => 'Choose between sandbox and production environments.',
        ],
    ];
}

function paymaya_link($params)
{
    require_once __DIR__ . '/paymaya_client.php';

    // Generate payment details
    $invoiceId = $params['invoiceid'];
    $amount = $params['amount'];
    $currency = $params['currency'];
    $returnUrl = $params['returnurl'];
    $callbackUrl = $params['systemurl'] . '/modules/gateways/callback/paymaya_callback.php';

    $clientEmail = $params['clientdetails']['email'];
    $clientName = $params['clientdetails']['fullname'];

    // Initialize PayMaya API client
    $paymaya = new PayMayaClient(
        $params['apiKey'],
        $params['secretKey'],
        $params['environment']
    );

    try {
        $paymentUrl = $paymaya->createPayment([
            'amount' => $amount,
            'currency' => $currency,
            'description' => 'Invoice #' . $invoiceId,
            'redirectUrl' => $returnUrl,
            'callbackUrl' => $callbackUrl,
            'customerEmail' => $clientEmail,
            'customerName' => $clientName,
        ]);

        return '<a href="' . $paymentUrl . '" target="_blank" class="btn btn-primary">Pay Now with PayMaya</a>';
    } catch (Exception $e) {
        logTransaction('PayMaya', ['error' => $e->getMessage()], 'Error');
        return '<div class="error">Error: Unable to generate payment link.</div>';
    }
}
