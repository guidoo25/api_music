<?php
namespace App\Interfaces;

interface PaymentServiceInterface {
    public function createPreAuthorization(array $data): array;
    public function createCharge(array $data): array;
    public function capturePaymentIntent(string $paymentIntentId, int $amount): array;
}