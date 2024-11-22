<?php

class PayMayaClient
{
    private $apiKey;
    private $secretKey;
    private $baseUrl;

    public function __construct($apiKey, $secretKey, $environment)
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        $this->baseUrl = $environment === 'production'
            ? 'https://pg.paymaya.com'
            : 'https://pg-sandbox.paymaya.com';
    }

    public function createPayment($data)
    {
        $endpoint = $this->baseUrl . '/payments/v1/payment-tokens';

        $payload = [
            'totalAmount' => [
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ],
            'redirectUrl' => [
                'success' => $data['redirectUrl'],
                'failure' => $data['redirectUrl'],
                'cancel' => $data['redirectUrl'],
            ],
            'requestReferenceNumber' => $data['description'],
            'metadata' => [
                'email' => $data['customerEmail'],
                'name' => $data['customerName'],
            ],
        ];

        return $this->sendRequest($endpoint, $payload)['redirectUrl'];
    }

    private function sendRequest($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        if (!$response) {
            throw new Exception('PayMaya API request failed: ' . curl_error($ch));
        }

        $decoded = json_decode($response, true);
        if (isset($decoded['error'])) {
            throw new Exception('PayMaya API error: ' . $decoded['error']['message']);
        }

        return $decoded;
    }
}
