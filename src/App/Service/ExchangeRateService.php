<?php

namespace App\Service;

use App\Connector\NbpApiConnectorInterface;
use App\Dto\ExchangeRateDto;
use App\Parser\RateParser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ExchangeRateService
{
    private string $cacheKey;
    private int $cacheTtl;

    public function __construct(
        private NbpApiConnectorInterface $connector,
        private CacheInterface $cache,
        private RateParser $rateParser,
        ParameterBagInterface $parameterBag
    ) {
        $this->cacheKey = $parameterBag->get('exchange_rate.cache.key');
        $this->cacheTtl = $parameterBag->get('exchange_rate.cache.ttl');
    }

    /**
     * @return ExchangeRateDto[]
     */
    public function getTodayRates(): array
    {
        return $this->cache->get($this->cacheKey, function (ItemInterface $item) {
            $item->expiresAfter($this->cacheTtl);

            $dtos = $this->fetchAndProcessRates();
            return array_map(fn(ExchangeRateDto $dto) => $dto->toArray(), $dtos);
        });

        return $this->convertArraysToDtos($cachedData);
    }

    private function fetchAndProcessRates(): array
    {
        $data = $this->connector->getTodayRates();
        
        return $this->rateParser->parseTodayRates($data);
    }

    /**
     * Convert cached arrays back to DTOs
     *
     * @param array $cachedData
     * @return ExchangeRateDto[]
     */
    private function convertArraysToDtos(array $cachedData): array
    {
        $dtos = [];
        foreach ($cachedData as $item) {
            $dtos[] = new ExchangeRateDto(
                $item['code'],
                $item['currency'],
                $item['mid'],
                $item['buy'] ?? null,
                $item['sell'] ?? null
            );
        }
        return $dtos;
    }
}
