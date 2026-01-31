# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PHP client library for the [Easybill REST API](https://www.easybill.de/api/) - a German invoicing and billing service. The library provides typed access to customers, documents (invoices, credits, offers, orders), and projects.

## Development Commands

```bash
# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit

# Static analysis
./vendor/bin/phpstan analyse src

# Code style fix
./vendor/bin/php-cs-fixer fix

# Release a new version
./release.sh 1.0.0
```

## Architecture

### Entry Point
`EasybillClient` is the main class - instantiate with an API key and call methods to fetch data. Uses Guzzle for HTTP and supports PSR-3 logging.

### Models (readonly classes with `fromArray()` factory)
- `EasybillCustomer` - Customer data with `getDisplayName()`, `getPrimaryEmail()` helpers
- `EasybillDocument` - Invoices/documents with `getAmountNetEur()`, `getAmountGrossEur()` (converts from cents)
- `EasybillDocumentItem` - Line items within documents
- `EasybillProject` - Project data for invoice assignment

### Exceptions (all extend `EasybillException`)
- `EasybillAuthenticationException` - 401/403 responses
- `EasybillRateLimitException` - 429 responses
- `EasybillNotFoundException` - 404 responses

### Key Patterns
- Amounts are stored in cents as strings (use `bcdiv` for EUR conversion)
- All list methods handle pagination automatically
- `DocumentType` enum for filtering: `INVOICE`, `CREDIT`, `OFFER`, `ORDER`, `RECURRING`
