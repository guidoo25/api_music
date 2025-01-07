<?php
namespace App\Repositories;

use App\Interfaces\PaymentRepositoryInterface;
use App\Interfaces\DatabaseInterface;

class PaymentRepository implements PaymentRepositoryInterface {
    private $db;

    public function __construct(DatabaseInterface $db) {
        $this->db = $db;
    }

    public function savePreAuthorization(array $data): int {
        $sql = "INSERT INTO preautorizaciones (
            contrato_id, 
            stripe_payment_intent_id, 
            monto, 
            moneda, 
            estado, 
            retain_until
        ) VALUES (?, ?, ?, ?, 'activa', ?)";

        $params = [
            $data['contrato_id'],
            $data['payment_intent_id'],
            $data['amount'],
            $data['currency'],
            $data['retain']
        ];

        return $this->db->query($sql, $params);
    }

    public function saveCharge(array $data): int {
        $sql = "INSERT INTO pagos (
            contrato_id,
            stripe_payment_intent_id,
            tipo,
            monto,
            moneda,
            estado,
            descripcion
        ) VALUES (?, ?, 'INICIAL', ?, ?, 'exitoso', ?)";

        $params = [
            $data['contrato_id'],
            $data['charge_id'],
            $data['amount'],
            $data['currency'],
            $data['description']
        ];

        return $this->db->query($sql, $params);
    }

    public function getContractId(string $did, string $cliente): ?int {
        $sql = "SELECT id FROM contratos 
                WHERE did_id = (SELECT id FROM dids WHERE numero = ?) 
                AND cliente_id = (SELECT id FROM clientes WHERE telefono = ?) 
                LIMIT 1";

        $result = $this->db->query($sql, [$did, $cliente]);
        return $result->fetch_column() ?: null;
    }
}