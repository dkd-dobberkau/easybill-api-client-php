<?php

declare(strict_types=1);

namespace Dkd\EasybillClient;

use DateTimeImmutable;
use Dkd\EasybillClient\Exception\EasybillAuthenticationException;
use Dkd\EasybillClient\Exception\EasybillException;
use Dkd\EasybillClient\Exception\EasybillNotFoundException;
use Dkd\EasybillClient\Exception\EasybillRateLimitException;
use Dkd\EasybillClient\Model\EasybillCustomer;
use Dkd\EasybillClient\Model\EasybillDocument;
use Dkd\EasybillClient\Model\EasybillProject;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * PHP client for the Easybill REST API.
 *
 * Example:
 *     $client = new EasybillClient('your-api-key');
 *     $customers = $client->getCustomers();
 *     $invoices = $client->getInvoices(year: 2025);
 */
class EasybillClient
{
    private const BASE_URL = 'https://api.easybill.de/rest/v1/';

    private Client $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        private readonly string $apiKey,
        private readonly int $timeout = 30,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->httpClient = new Client([
            'base_uri' => self::BASE_URL,
            'timeout' => $this->timeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    // === Customers ===

    /**
     * Get all customers from Easybill.
     *
     * @return list<EasybillCustomer>
     * @throws EasybillException
     */
    public function getCustomers(int $limit = 1000): array
    {
        $this->logger->info('Loading Easybill customers...');
        $customers = [];
        $page = 1;

        while (true) {
            $response = $this->request('GET', 'customers', [
                'query' => ['limit' => $limit, 'page' => $page],
            ]);

            $items = $response['items'] ?? [];
            if (count($items) === 0) {
                break;
            }

            foreach ($items as $data) {
                $customers[] = EasybillCustomer::fromArray($data);
            }

            $page++;
            $totalPages = $response['pages'] ?? 1;
            if ($page > $totalPages) {
                break;
            }
        }

        $this->logger->info('Easybill: ' . count($customers) . ' customers loaded');
        return $customers;
    }

    /**
     * Get a single customer.
     *
     * @throws EasybillException
     * @throws EasybillNotFoundException
     */
    public function getCustomer(int $customerId): EasybillCustomer
    {
        try {
            $data = $this->request('GET', 'customers/' . $customerId);
            return EasybillCustomer::fromArray($data);
        } catch (EasybillNotFoundException $e) {
            throw new EasybillNotFoundException("Customer {$customerId} not found", 404);
        }
    }

    // === Documents ===

    /**
     * Get documents from Easybill.
     *
     * @return list<EasybillDocument>
     * @throws EasybillException
     */
    public function getDocuments(
        ?DocumentType $documentType = null,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null,
        ?int $customerId = null,
        ?string $status = null,
        int $limit = 1000,
    ): array {
        $this->logger->info('Loading Easybill documents (type: ' . ($documentType?->value ?? 'all') . ')...');
        $documents = [];
        $page = 1;

        $query = ['limit' => $limit];
        if ($documentType !== null) {
            $query['type'] = $documentType->value;
        }
        if ($startDate !== null) {
            $dateRange = $startDate->format('Y-m-d');
            if ($endDate !== null) {
                $dateRange .= ',' . $endDate->format('Y-m-d');
            }
            $query['document_date'] = $dateRange;
        }
        if ($customerId !== null) {
            $query['customer_id'] = $customerId;
        }
        if ($status !== null) {
            $query['status'] = $status;
        }

        while (true) {
            $query['page'] = $page;
            $response = $this->request('GET', 'documents', ['query' => $query]);

            $items = $response['items'] ?? [];
            if (count($items) === 0) {
                break;
            }

            foreach ($items as $data) {
                $documents[] = EasybillDocument::fromArray($data);
            }

            $page++;
            $totalPages = $response['pages'] ?? 1;
            if ($page > $totalPages) {
                break;
            }
        }

        $this->logger->info('Easybill: ' . count($documents) . ' documents loaded');
        return $documents;
    }

    /**
     * Get invoices from Easybill.
     *
     * @return list<EasybillDocument>
     * @throws EasybillException
     */
    public function getInvoices(
        ?int $year = null,
        ?DateTimeImmutable $startDate = null,
        ?DateTimeImmutable $endDate = null,
        ?int $customerId = null,
    ): array {
        if ($year !== null && $startDate === null) {
            $startDate = new DateTimeImmutable($year . '-01-01');
            $endDate = new DateTimeImmutable($year . '-12-31');
        }

        return $this->getDocuments(
            documentType: DocumentType::INVOICE,
            startDate: $startDate,
            endDate: $endDate,
            customerId: $customerId,
        );
    }

    /**
     * Get a single document.
     *
     * @throws EasybillException
     * @throws EasybillNotFoundException
     */
    public function getDocument(int $documentId, bool $withItems = true): EasybillDocument
    {
        try {
            $data = $this->request('GET', 'documents/' . $documentId);
            return EasybillDocument::fromArray($data, $withItems);
        } catch (EasybillNotFoundException $e) {
            throw new EasybillNotFoundException("Document {$documentId} not found", 404);
        }
    }

    /**
     * Download the PDF of a document.
     *
     * @throws EasybillException
     * @throws EasybillNotFoundException
     */
    public function getDocumentPdf(int $documentId): string
    {
        try {
            $response = $this->httpClient->request('GET', 'documents/' . $documentId . '/pdf');
            return $response->getBody()->getContents();
        } catch (ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            if ($statusCode === 404) {
                throw new EasybillNotFoundException("Document {$documentId} not found", 404);
            }
            throw $this->handleException($e);
        } catch (GuzzleException $e) {
            throw new EasybillException('Error loading PDF: ' . $e->getMessage(), null, null, $e);
        }
    }

    // === Projects ===

    /**
     * Get projects from Easybill.
     *
     * @return list<EasybillProject>
     * @throws EasybillException
     */
    public function getProjects(?int $customerId = null, int $limit = 1000): array
    {
        $this->logger->info('Loading Easybill projects...');
        $projects = [];
        $page = 1;

        $query = ['limit' => $limit];
        if ($customerId !== null) {
            $query['customer_id'] = $customerId;
        }

        while (true) {
            $query['page'] = $page;
            $response = $this->request('GET', 'projects', ['query' => $query]);

            $items = $response['items'] ?? [];
            if (count($items) === 0) {
                break;
            }

            foreach ($items as $data) {
                $projects[] = EasybillProject::fromArray($data);
            }

            $page++;
            $totalPages = $response['pages'] ?? 1;
            if ($page > $totalPages) {
                break;
            }
        }

        $this->logger->info('Easybill: ' . count($projects) . ' projects loaded');
        return $projects;
    }

    // === Internal ===

    /**
     * Make an API request.
     *
     * @throws EasybillException
     */
    private function request(string $method, string $uri, array $options = []): array
    {
        try {
            $response = $this->httpClient->request($method, $uri, $options);
            $body = $response->getBody()->getContents();
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (ClientException $e) {
            throw $this->handleException($e);
        } catch (GuzzleException $e) {
            throw new EasybillException('API request failed: ' . $e->getMessage(), null, null, $e);
        } catch (\JsonException $e) {
            throw new EasybillException('Failed to parse API response: ' . $e->getMessage(), null, null, $e);
        }
    }

    /**
     * Handle HTTP client exceptions.
     */
    private function handleException(ClientException $e): EasybillException
    {
        $statusCode = $e->getResponse()->getStatusCode();
        $body = $e->getResponse()->getBody()->getContents();
        $response = json_decode($body, true) ?? [];
        $message = $response['message'] ?? $e->getMessage();

        return match ($statusCode) {
            401, 403 => new EasybillAuthenticationException($message, $statusCode, $response, $e),
            404 => new EasybillNotFoundException($message, $statusCode, $response, $e),
            429 => new EasybillRateLimitException($message, $statusCode, $response, $e),
            default => new EasybillException($message, $statusCode, $response, $e),
        };
    }
}
