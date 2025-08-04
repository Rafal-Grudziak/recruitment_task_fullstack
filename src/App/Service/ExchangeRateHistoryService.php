<?php

namespace App\Service;

use App\Connector\NbpApiConnectorInterface;
use App\Dto\ExchangeRateHistoryDto;
use App\Parser\RateParser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ExchangeRateHistoryService
{
    private array $allowedCurrencies;

    public function __construct(
        private NbpApiConnectorInterface $connector,
        private RateParser $rateParser,
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
        if (!in_array($code, $this->allowedCurrencies)) {
            throw new \InvalidArgumentException("Currency code '$code' is not allowed.");
        }

        $data = $this->connector->getHistoricalRates($code, $endDate);

        $rawRates = is_array($data['rates'] ?? null) ? $data['rates'] : [];

        return $this->rateParser->parseHistoricalRates($rawRates, $code);
    }

} 