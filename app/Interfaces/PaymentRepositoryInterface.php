<?php
namespace App\Interfaces;

interface PaymentRepositoryInterface {
    public function savePreAuthorization(array $data): int;
    public function saveCharge(array $data): int;
    public function getContractId(string $did, string $cliente): ?int;
}