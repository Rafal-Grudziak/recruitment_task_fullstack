<?php

namespace App\Connector;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class NbpApiConnector implements NbpApiConnectorInterface
{
    private string $tableAUrl;
    private string $ratesUrlTemplate;

    public function __construct(
        private ClientInterface $httpClient,
        private LoggerInterface $logger,
        ParameterBagInterface $parameterBag
    ) {
        $this->tableAUrl = $parameterBag->get('nbp.api.table_a_url');
        $this->ratesUrlTemplate = $parameterBag->get('nbp.api.rates_url_template');
    }

    /**
     * Fetch today's exchange rates (NBP Table A).
     */
    public function getTodayRates(): array
    {
        return $this->getJson($this->tableAUrl);
    }

    /**
     * Fetch historical exchange rates for a currency within a 14-day range.
     */
    public function getHistoricalRates(string $code, ?\DateTimeInterface $endDate = null): array
    {
        $endDate ??= new \DateTimeImmutable('today');

        if (!$endDate instanceof \DateTimeImmutable) {
            $endDate = \DateTimeImmutable::createFromInterface($endDate);
        }

        $startDate = $endDate->sub(new \DateInterval('P14D'));

        return $this->getRatesForCurrency(
            $code,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }

    /**
     * Fetch exchange rates for a currency in a date range.
     */
    public function getRatesForCurrency(string $code, string $startDate, string $endDate): array
    {
        $url = str_replace(
            ['{code}', '{startDate}', '{endDate}'],
            [$code, $startDate, $endDate],
            $this->ratesUrlTemplate
        );

        return $this->getJson($url);
    }

    /**
     * Send GET request and decode JSON safely.
     */
    private function getJson(string $url): array
    {
        try {
            $response = $this->httpClient->request('GET', $url);
            $body = $response->getBody()->getContents();

            $data = json_decode($body, true);
            if (!$this->isValidJson($data)) {
                $this->logger->error('Invalid JSON received from NBP API', [
                    'url' => $url,
                    'error' => json_last_error_msg(),
                    'body' => $body,
                ]);
                return [];
            }

            return $data;
        } catch (RequestException $e) {
            $this->logger->error('HTTP request error to NBP API', [
                'url' => $url,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error during NBP API request', [
                'url' => $url,
                'message' => $e->getMessage(),
                'exception_class' => get_class($e),
                'code' => $e->getCode(),
            ]);
        }

        return [];
    }

    /**
     * Check whether JSON decode succeeded.
     */
    private function isValidJson(mixed $data): bool
    {
        return json_last_error() === JSON_ERROR_NONE && is_array($data);
    }
    
} 