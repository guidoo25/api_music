<?php
namespace App\Interfaces;

interface ContractServiceInterface {
    public function createContract(array $data): array;
    public function getActiveContract(string $did, string $cliente): ?array;
    public function updateTimeAvailable(int $contractId, int $seconds): bool;
    public function checkRenewal(int $contractId): array;
    public function processAutoRenewal(int $contractId): array;
}

