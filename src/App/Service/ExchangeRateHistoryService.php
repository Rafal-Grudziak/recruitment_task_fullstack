<?php

namespace App\Service;

use App\Connector\NbpApiConnectorInterface;
use App\Dto\ExchangeRateHistoryDto;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ExchangeRateHistoryService
{
    private array $allowedCurrencies;

    public function __construct(
        private NbpApiConnectorInterface $connector,
        ParameterBagInterface $parameterBag
    ) {
        $this->allowedCurrencies = $parameterBag->get('exchange_rate.allowed_currencies');
    }

    /**
     * @param string $code
     * @param \DateTimeInterface|null $endDate
     * @return ExchangeRateHistoryDto[]
     */
    public function getHistoricalRates(string $code, ?\DateTimeInterface $endDate = null): array
    {
        // Check if currency code is allowed
        if (!in_array($code, $this->allowedCurrencies)) {
            throw new \InvalidArgumentException("Currency code '$code' is not allowed.");
        }

        $data = $this->connector->getHistoricalRates($code, $endDate);

        $result = [];
        
        // Check if response is valid and format data
        if (isset($data['rates']) && is_array($data['rates'])) {
            foreach ($data['rates'] as $rate) {
                $result[] = new ExchangeRateHistoryDto(
                    $rate['effectiveDate'],
                    $rate['mid']
                );
            }
        }

        return $result;
    }
} 