<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function paymaya_MetaData()
{
    return [
        'DisplayName' => 'PayMaya Payment Gateway',
        'APIVersion' => '1.1', // Use API version 1.1 or later
    ];
}

function paymaya_config()
{
    return [
        "FriendlyName" => [
            "Type" => "System",
            "Value" => "PayMaya Payment Gateway",
        ],
        "apiPublicKey" => [
            "FriendlyName" => "API Public Key",
            "Type" => "text",
            "Size" => "50",
            "Default" => "",
            "Description" => "Enter your PayMaya API Public Key here",
        ],
        "apiSecretKey" => [
            "FriendlyName" => "API Secret Key",
            "Type" => "password",
            "Size" => "50",
            "Default" => "",
            "Description" => "Enter your PayMaya API Secret Key here",
        ],
        "environment" => [
            "FriendlyName" => "Environment",
            "Type" => "dropdown",
            "Options" => "sandbox,production",
            "Description" => "Select whether to use Sandbox or Production environment",
        ],
    ];
}

function paymaya_link($params)
{
    // Gateway Configuration Parameters
    $apiPublicKey = $params['apiPublicKey'];
    $apiSecretKey = $params['apiSecretKey'];
    $environment = $params['environment'];

    // Determine PayMaya Endpoint based on environment
    $paymayaUrl = ($environment === 'sandbox')
        ? "https://pg-sandbox.paymaya.com/checkout/v1/checkouts"
        : "https://pg.maya.ph/checkout/v1/checkouts";

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params['description'];
    $amount = $params['amount']; // Format: XX.XX
    $currency = $params['currency'];

    // Client Parameters
    $clientEmail = $params['clientdetails']['email'];

    // System Parameters
    $callbackUrl = $params['systemurl'] . '/modules/gateways/callback/paymaya_callback.php';
    $successUrl = $params['returnurl'];

    // Prepare the payload
    $payload = [
        "totalAmount" => [
            "value" => $amount,
            "currency" => $currency,
        ],
        "buyer" => [
            "email" => $clientEmail,
        ],
        "redirectUrl" => [
            "success" => $successUrl,
            "failure" => $callbackUrl,
            "cancel" => $callbackUrl,
        ],
        "requestReferenceNumber" => $invoiceId,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $paymayaUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($apiSecretKey),
    ]);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = 'Error:' . curl_error($ch);
        curl_close($ch);
        return "<p>Payment could not be initiated. Please contact support. ($error)</p>";
    }
    curl_close($ch);

    $response = json_decode($result, true);
    if (isset($response['redirectUrl'])) {
        $form = '<form method="get" action="' . $response['redirectUrl'] . '">
                    <input type="submit" value="Pay with PayMaya" />
                 </form>';
        return $form;
    } else {
        return "<p>Payment could not be initiated. Please contact support.</p>";
    }
}

function paymaya_refund($params)
{
    // Gateway Configuration Parameters
    $apiSecretKey = $params['apiSecretKey'];
    $environment = $params['environment'];

    // Determine PayMaya Endpoint for Refund based on environment
    $paymayaUrl = ($environment === 'sandbox')
        ? "https://pg-sandbox.paymaya.com/payments/v1/payment-rrn/"
        : "https://pg.maya.ph/payments/v1/payment-rrn/";

    // Invoice Parameters
    $transactionId = $params['transid'];
    $amount = $params['amount']; // Format: XX.XX
    $currency = $params['currency'];

    $paymayaUrl .= $transactionId . "/refund";

    // Prepare the payload
    $payload = [
        "totalAmount" => [
            "value" => $amount,
            "currency" => $currency,
        ],
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $paymayaUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($apiSecretKey),
    ]);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        $error = 'Error:' . curl_error($ch);
        curl_close($ch);
        return [
            'status' => 'error',
            'rawdata' => $error,
        ];
    }
    curl_close($ch);

    $response = json_decode($result, true);
    if (isset($response['status']) && $response['status'] == 'SUCCESS') {
        return [
            'status' => 'success',
            'rawdata' => $response,
        ];
    } else {
        return [
            'status' => 'error',
            'rawdata' => $response,
        ];
    }
}

?>
