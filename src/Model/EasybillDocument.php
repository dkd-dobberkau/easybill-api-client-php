<?php

declare(strict_types=1);

namespace Dkd\EasybillClient\Model;

use DateTimeImmutable;

/**
 * Document (invoice, credit note, etc.) from Easybill.
 */
readonly class EasybillDocument
{
    /**
     * @param list<EasybillDocumentItem> $items
     */
    public function __construct(
        public int $id,
        public string $type,
        public ?string $number = null,
        public ?DateTimeImmutable $documentDate = null,
        public ?DateTimeImmutable $dueDate = null,
        public ?string $status = null,
        public ?int $customerId = null,
        public ?int $projectId = null,
        public string $amountNet = '0',
        public string $amountGross = '0',
        public string $amount = '0',
        public string $currency = 'EUR',
        public bool $isDraft = false,
        public ?DateTimeImmutable $paidAt = null,
        public ?string $title = null,
        public ?string $text = null,
        public ?string $textPrefix = null,
        public array $items = [],
    ) {}

    /**
     * Net amount in EUR (converted from cents).
     */
    public function getAmountNetEur(): string
    {
        return bcdiv($this->amountNet, '100', 2);
    }

    /**
     * Gross amount in EUR (converted from cents).
     */
    public function getAmountGrossEur(): string
    {
        $gross = $this->amountGross !== '0' ? $this->amountGross : $this->amount;
        return bcdiv($gross, '100', 2);
    }

    /**
     * Is the document paid?
     */
    public function isPaid(): bool
    {
        return $this->paidAt !== null;
    }

    /**
     * Description of the first line item (as title fallback).
     */
    public function getFirstItemDescription(): ?string
    {
        if (count($this->items) === 0) {
            return null;
        }
        $desc = $this->items[0]->description;
        if ($desc === null) {
            return null;
        }
        $firstLine = trim(explode("\n", $desc)[0]);
        return strlen($firstLine) > 100 ? substr($firstLine, 0, 100) : $firstLine;
    }

    /**
     * Create from API response data.
     */
    public static function fromArray(array $data, bool $withItems = true): self
    {
        $items = [];
        if ($withItems && isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemData) {
                $items[] = EasybillDocumentItem::fromArray($itemData);
            }
        }

        $documentDate = null;
        if (isset($data['document_date']) && $data['document_date'] !== '') {
            $documentDate = new DateTimeImmutable($data['document_date']);
        }

        $dueDate = null;
        if (isset($data['due_date']) && $data['due_date'] !== '') {
            $dueDate = new DateTimeImmutable($data['due_date']);
        }

        $paidAt = null;
        if (isset($data['paid_at']) && $data['paid_at'] !== '') {
            $dateStr = explode('T', $data['paid_at'])[0];
            $paidAt = new DateTimeImmutable($dateStr);
        }

        return new self(
            id: (int)($data['id'] ?? 0),
            type: $data['type'] ?? 'INVOICE',
            number: $data['number'] ?? null,
            documentDate: $documentDate,
            dueDate: $dueDate,
            status: $data['status'] ?? null,
            customerId: isset($data['customer_id']) ? (int)$data['customer_id'] : null,
            projectId: isset($data['project_id']) ? (int)$data['project_id'] : null,
            amountNet: (string)($data['amount_net'] ?? '0'),
            amountGross: (string)($data['amount_gross'] ?? '0'),
            amount: (string)($data['amount'] ?? '0'),
            currency: $data['currency'] ?? 'EUR',
            isDraft: (bool)($data['is_draft'] ?? false),
            paidAt: $paidAt,
            title: $data['title'] ?? null,
            text: $data['text'] ?? null,
            textPrefix: $data['text_prefix'] ?? null,
            items: $items,
        );
    }
}
