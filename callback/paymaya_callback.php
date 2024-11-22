<?php

// Callback Handler for PayMaya Payment Gateway

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['requestReferenceNumber']) && isset($input['paymentStatus'])) {
        $invoiceId = $input['requestReferenceNumber'];
        $paymentStatus = $input['paymentStatus'];

        // Load WHMCS framework
        require_once '../../../init.php';
        require_once '../../../includes/gatewayfunctions.php';
        require_once '../../../includes/invoicefunctions.php';

        $gatewayModule = "paymaya";
        $gatewayParams = getGatewayVariables($gatewayModule);

        if (!$gatewayParams['type']) {
            die("Module Not Activated");
        }

        // Verify Signature to Enhance Security
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            die("Unauthorized: Missing Signature");
        }
        $providedSignature = $headers['Authorization'];
        $computedSignature = hash_hmac('sha256', json_encode($input), $gatewayParams['apiSecretKey']);

        if ($providedSignature !== $computedSignature) {
            logTransaction($gatewayParams['name'], $input, "Signature Mismatch - Possible Fake Payment");
            die("Unauthorized: Invalid Signature");
        }

        $invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

        if ($paymentStatus == 'PAYMENT_SUCCESS') {
            $transactionId = $input['paymentId'];
            $paymentAmount = $input['totalAmount']['value'];
            $paymentFee = 0; // Adjust if needed

            // Prevent Duplicate Transaction Processing
            checkCbTransID($transactionId);

            addInvoicePayment($invoiceId, $transactionId, $paymentAmount, $paymentFee, $gatewayModule);
            logTransaction($gatewayParams['name'], $input, "Successful");
        } else {
            logTransaction($gatewayParams['name'], $input, "Unsuccessful");
        }
    }
}

?>
