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
    private string $cacheKeyPrefix;

    public function __construct(
        private NbpApiConnectorInterface $connector,
        private CacheInterface $cache,
        private RateParser $rateParser,
        ParameterBagInterface $parameterBag
    ) {
        $this->cacheKeyPrefix = $parameterBag->get('exchange_rate.cache.key_prefix');
    }

    /**
     * @return ExchangeRateDto[]
     */
    public function getTodayRates(): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Warsaw'));
        $cacheKey = $this->getCacheKey($now);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($now) {
            $item->expiresAfter($this->calculateTtlUntilNextUpdate($now));
            return $this->fetchAndProcessRates();
        });
    }

    private function fetchAndProcessRates(): array
    {
        $data = $this->connector->getTodayRates();
        
        return $this->rateParser->parseTodayRates($data);
    }

    private function calculateTtlUntilNextUpdate(\DateTimeImmutable $now): int
    {
        $todayNoon = $now->setTime(12, 0);
        $nextNoon = $now < $todayNoon ? $todayNoon : $todayNoon->modify('+1 day');

        return $nextNoon->getTimestamp() - $now->getTimestamp();
    }

    private function getCacheKey(\DateTimeImmutable $now): string
    {
        $hour = (int) $now->format('H');

        // Adjusting the cache key so that the employee has up-to-date rates
        $dateKey = $hour < 12
            ? $now->modify('-1 day')->format('Y-m-d')
            : $now->format('Y-m-d');

        return $this->cacheKeyPrefix . $dateKey;
    }


}
