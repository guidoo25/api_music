<?php
namespace App\Interfaces;

interface ClientServiceInterface {
    public function findByPhone(string $phone): ?array;
    public function createClient(array $data): array;
}