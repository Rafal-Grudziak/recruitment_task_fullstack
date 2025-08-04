<?php

namespace App\Parser;

use App\Dto\ExchangeRateDto;
use App\Dto\ExchangeRateHistoryDto;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RateParser
{
    private array $allowedCurrencies;
    private float $eurUsdBuySpread;
    private float $eurUsdSellSpread;
    private float $otherSellSpread;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->allowedCurrencies = $parameterBag->get('exchange_rate.allowed_currencies');
        $this->eurUsdBuySpread = $parameterBag->get('exchange_rate.eur_usd_buy_spread');
        $this->eurUsdSellSpread = $parameterBag->get('exchange_rate.eur_usd_sell_spread');
        $this->otherSellSpread = $parameterBag->get('exchange_rate.other_sell_spread');
    }

    /**
     * Parses today's exchange rates and returns an array of ExchangeRateDto objects.
     * Filters only allowed currencies and calculates buy/sell values.
     *
     * @param array<string, mixed>[] $data Raw API response data
     * @return ExchangeRateDto[]
     */
    public function parseTodayRates(array $data): array
    {
        if (empty($data) || !isset($data[0]['rates'])) {
            return [];
        }

        $rates = $data[0]['rates'];
        $filtered = [];

        foreach ($rates as $rate) {
            if (!in_array($rate['code'], $this->allowedCurrencies)) {
                continue;
            }

            $filtered[] = $this->createDtoFromRate($rate);
        }

        return $filtered;
    }

    /**
     * Parses historical exchange rates into ExchangeRateHistoryDto objects.
     *
     * @param array<int, array{mid: float, effectiveDate: string}> $rates
     * @param string $code Currency code (e.g. 'USD')
     * @return ExchangeRateHistoryDto[]
     */
    public function parseHistoricalRates(array $rates, string $code): array
    {
        if (!in_array($code, $this->allowedCurrencies)) {
            return [];
        }

        $result = [];

        foreach ($rates as $rate) {
            $result[] = $this->createHistoryDtoFromRate($rate, $code);
        }

        return $result;
    }

    /**
     * Calculates the buy and sell prices based on currency and configured spreads.
     *
     * @param string $code Currency code
     * @param float $mid Mid-market rate
     * @return array{?float, float} [buy, sell]
     */
    private function calculateBuySell(string $code, float $mid): array
    {
        return match ($code) {
            'EUR', 'USD' => [
                round($mid - $this->eurUsdBuySpread, 2),
                round($mid + $this->eurUsdSellSpread, 2),
            ],
            default => [null, round($mid + $this->otherSellSpread, 2)],
        };
    }

    /**
     * Creates an ExchangeRateDto object from a single raw API rate record.
     *
     * @param array<string, mixed> $rate
     * @return ExchangeRateDto
     */
    private function createDtoFromRate(array $rate): ExchangeRateDto
    {
        $code = $rate['code'];
        $mid = $rate['mid'];
        [$buy, $sell] = $this->calculateBuySell($code, $mid);

        return new ExchangeRateDto(
            $code,
            $rate['currency'],
            $mid,
            $buy,
            $sell
        );
    }

    /**
     * Creates an ExchangeRateHistoryDto from a historical rate entry.
     *
     * @param array{effectiveDate: string, mid: float} $rate
     * @param string $code
     * @return ExchangeRateHistoryDto
     */
    private function createHistoryDtoFromRate(array $rate, string $code): ExchangeRateHistoryDto
    {
        $mid = $rate['mid'];
        [$buy, $sell] = $this->calculateBuySell($code, $mid);

        return new ExchangeRateHistoryDto(
            $rate['effectiveDate'],
            round($mid, 2),
            $buy,
            $sell
        );
    }
}
