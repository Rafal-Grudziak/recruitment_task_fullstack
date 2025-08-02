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

            return $this->fetchAndProcessRates();
        });
    }

    private function fetchAndProcessRates(): array
    {
        $data = $this->connector->getTodayRates();
        
        return $this->rateParser->parseTodayRates($data);
    }

}
