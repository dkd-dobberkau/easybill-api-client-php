<?php

declare(strict_types=1);

namespace Dkd\EasybillClient\Model;

/**
 * Project from Easybill (for invoice project assignment).
 */
readonly class EasybillProject
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $status = null,
        public ?int $customerId = null,
        public ?string $budgetAmount = null,
        public ?int $budgetTime = null,
        public ?string $consumedAmount = null,
        public ?int $consumedTime = null,
    ) {}

    /**
     * Create from API response data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            name: $data['name'] ?? '',
            status: $data['status'] ?? null,
            customerId: isset($data['customer_id']) ? (int)$data['customer_id'] : null,
            budgetAmount: isset($data['budget_amount']) ? (string)$data['budget_amount'] : null,
            budgetTime: isset($data['budget_time']) ? (int)$data['budget_time'] : null,
            consumedAmount: isset($data['consumed_amount']) ? (string)$data['consumed_amount'] : null,
            consumedTime: isset($data['consumed_time']) ? (int)$data['consumed_time'] : null,
        );
    }
}
