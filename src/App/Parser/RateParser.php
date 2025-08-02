<?php

namespace App\Parser;

use App\Dto\ExchangeRateDto;
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
     * @param array<string, mixed>[] $data
     * @return ExchangeRateDto[]
     */
    public function parseTodayRates(array $data): array
    {
        if (empty($data) || !isset($data[0]['rates'])) {
            return [];
        }

        $rates = $data[0]['rates'];
        $filteredRates = [];

        foreach ($rates as $rate) {
            if (!in_array($rate['code'], $this->allowedCurrencies)) {
                continue;
            }

            $filteredRates[] = $this->createDtoFromRate($rate);
        }

        return $filteredRates;
    }

    private function createDtoFromRate(array $rate): ExchangeRateDto
    {
        $code = $rate['code'];
        $mid = $rate['mid'];

        [$buy, $sell] = match ($code) {
            'EUR', 'USD' => [
                $mid - $this->eurUsdBuySpread,
                $mid + $this->eurUsdSellSpread
            ],
            default => [null, $mid + $this->otherSellSpread]
        };

        return new ExchangeRateDto(
            $code,
            $rate['currency'],
            $mid,
            $buy,
            $sell
        );
    }
} 