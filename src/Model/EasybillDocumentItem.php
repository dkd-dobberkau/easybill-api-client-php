<?php

declare(strict_types=1);

namespace Dkd\EasybillClient\Model;

/**
 * Single line item in a document.
 */
readonly class EasybillDocumentItem
{
    public function __construct(
        public ?int $id = null,
        public ?string $number = null,
        public ?string $description = null,
        public string $quantity = '1',
        public ?string $unit = null,
        public string $singlePriceNet = '0',
        public string $totalPriceNet = '0',
        public string $vatPercent = '19',
        public ?string $positionKind = null,
    ) {}

    /**
     * Create from API response data.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int)$data['id'] : null,
            number: $data['number'] ?? null,
            description: $data['description'] ?? null,
            quantity: (string)($data['quantity'] ?? '1'),
            unit: $data['unit'] ?? null,
            singlePriceNet: (string)($data['single_price_net'] ?? '0'),
            totalPriceNet: (string)($data['total_price_net'] ?? '0'),
            vatPercent: (string)($data['vat_percent'] ?? '19'),
            positionKind: $data['position_kind'] ?? null,
        );
    }
}
