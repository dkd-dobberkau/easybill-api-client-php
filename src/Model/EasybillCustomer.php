<?php

declare(strict_types=1);

namespace Dkd\EasybillClient\Model;

/**
 * Customer from Easybill.
 */
readonly class EasybillCustomer
{
    /**
     * @param list<string> $emails
     */
    public function __construct(
        public int $id,
        public ?string $number = null,
        public ?string $companyName = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
        public array $emails = [],
        public ?string $street = null,
        public ?string $zipCode = null,
        public ?string $city = null,
        public ?string $country = null,
        public ?string $vatIdentifier = null,
        public ?int $paymentOptions = null,
    ) {}

    /**
     * Display name for the customer.
     */
    public function getDisplayName(): string
    {
        if ($this->companyName !== null && $this->companyName !== '') {
            return $this->companyName;
        }
        if ($this->firstName !== null && $this->lastName !== null) {
            return trim($this->firstName . ' ' . $this->lastName);
        }
        return $this->number ?? 'Customer ' . $this->id;
    }

    /**
     * Primary email address.
     */
    public function getPrimaryEmail(): ?string
    {
        return $this->emails[0] ?? null;
    }

    /**
     * Create from API response data.
     */
    public static function fromArray(array $data): self
    {
        $emails = [];
        for ($i = 1; $i <= 3; $i++) {
            $email = $data['emails_' . $i] ?? null;
            if ($email !== null && $email !== '') {
                $emails[] = $email;
            }
        }

        return new self(
            id: (int)($data['id'] ?? 0),
            number: $data['number'] ?? null,
            companyName: $data['company_name'] ?? null,
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            emails: $emails,
            street: $data['street'] ?? null,
            zipCode: $data['zip_code'] ?? null,
            city: $data['city'] ?? null,
            country: $data['country'] ?? null,
            vatIdentifier: $data['vat_identifier'] ?? null,
            paymentOptions: isset($data['payment_options']) ? (int)$data['payment_options'] : null,
        );
    }
}
