<?php
namespace App\Services;

use App\Interfaces\PaymentServiceInterface;

class StripePaymentService implements PaymentServiceInterface {
    private string $apiKey;
    private string $apiUrl = 'https://api.stripe.com/v1';

    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
    }

    public function createPreAuthorization(array $data): array {
        // Crear token de tarjeta
        $token = $this->createCardToken($data);

        // Crear PaymentIntent
        return $this->makeRequest('/payment_intents', [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'eur',
            'payment_method_data' => [
                'type' => 'card',
                'card' => ['token' => $token['id']]
            ],
            'capture_method' => 'manual',
            'description' => $data['description'],
            'metadata' => [
                'did' => $data['did'],
                'cliente' => $data['cliente'],
                'tpv' => $data['tpv'],
                'terminal' => $data['terminal']
            ]
        ]);
    }

    public function createCharge(array $data): array {
        $token = $this->createCardToken($data);

        return $this->makeRequest('/charges', [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'eur',
            'source' => $token['id'],
            'description' => $data['description'],
            'metadata' => [
                'did' => $data['did'],
                'cliente' => $data['cliente'],
                'tpv' => $data['tpv'],
                'terminal' => $data['terminal']
            ]
        ]);
    }

    public function capturePaymentIntent(string $paymentIntentId, int $amount): array {
        return $this->makeRequest("/payment_intents/{$paymentIntentId}/capture", [
            'amount_to_capture' => $amount
        ]);
    }

    private function createCardToken(array $data): array {
        return $this->makeRequest('/tokens', [
            'card' => [
                'number' => $data['pan'],
                'exp_month' => substr($data['expiration'], 0, 2),
                'exp_year' => substr($data['expiration'], 2),
                'cvc' => $data['cvv']
            ]
        ]);
    }

    private function makeRequest(string $endpoint, array $data = [], string $method = 'POST'): array {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/x-www-form-urlencoded'
            ],
            CURLOPT_POSTFIELDS => http_build_query($data)
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('Error en la petición: ' . $error);
        }

        return json_decode($response, true);
    }
}