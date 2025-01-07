<?php
namespace App\Interfaces;

interface CallServiceInterface {
    public function registerCall(array $data): array;
    public function updateCallDuration(string $remoteUniqueId, int $duration): bool;
    public function hangupCall(string $remoteUniqueId): bool;
    public function getCallStatus(string $remoteUniqueId): array;
}