# Easybill API Client (PHP)

PHP client for the [Easybill REST API](https://www.easybill.de/api/).

## Requirements

- PHP 8.2+
- Composer

## Installation

```bash
composer require dkd/easybill-api-client
```

## Usage

```php
use Dkd\EasybillClient\EasybillClient;
use Dkd\EasybillClient\DocumentType;

$client = new EasybillClient(apiKey: 'your-api-key');

// Get all customers
$customers = $client->getCustomers();

// Get invoices for a year
$invoices = $client->getInvoices(year: 2025);

// Get all documents
$documents = $client->getDocuments();

// Get documents of specific type
$offers = $client->getDocuments(documentType: DocumentType::OFFER);

// Get projects
$projects = $client->getProjects();
```

## API

### EasybillClient

- `getCustomers(int $limit = 1000)` - Get all customers
- `getCustomer(int $customerId)` - Get single customer
- `getDocuments(?DocumentType $documentType, ?DateTimeImmutable $startDate, ?DateTimeImmutable $endDate, ?int $customerId, ?string $status, int $limit)` - Get documents
- `getInvoices(?int $year, ?DateTimeImmutable $startDate, ?DateTimeImmutable $endDate, ?int $customerId)` - Get invoices
- `getDocument(int $documentId, bool $withItems = true)` - Get single document
- `getDocumentPdf(int $documentId)` - Download document PDF
- `getProjects(?int $customerId, int $limit)` - Get projects

### Document Types

```php
use Dkd\EasybillClient\DocumentType;

DocumentType::INVOICE
DocumentType::CREDIT
DocumentType::OFFER
DocumentType::ORDER
DocumentType::RECURRING
```

### Exceptions

- `EasybillException` - Base exception
- `EasybillAuthenticationException` - Authentication failed (401/403)
- `EasybillRateLimitException` - Rate limit exceeded (429)
- `EasybillNotFoundException` - Resource not found (404)

### Models

- `EasybillCustomer` - Customer data
- `EasybillDocument` - Invoice/document data
- `EasybillDocumentItem` - Line item in document
- `EasybillProject` - Project data

All models are `readonly` classes with a static `fromArray()` factory method.

## License

MIT
