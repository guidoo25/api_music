<?php
namespace App\Interfaces;

interface OperatorServiceInterface {
    public function findAvailableOperator(): ?array;
    public function setOperatorStatus(string $operatorPhone, string $status): bool;
}