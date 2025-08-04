<?php

namespace App\Tests\Service;

use App\Connector\NbpApiConnectorInterface;
use App\Dto\ExchangeRateDto;
use App\Parser\RateParser;
use App\Service\ExchangeRateService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ExchangeRateServiceTest extends TestCase
{
    private NbpApiConnectorInterface $connector;
    private CacheInterface $cache;
    private RateParser $rateParser;
    private ParameterBagInterface $parameterBag;
    private ExchangeRateService $exchangeRateService;

    protected function setUp(): void
    {
        $this->connector = $this->createMock(NbpApiConnectorInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->rateParser = $this->createMock(RateParser::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->parameterBag->method('get')->willReturnMap([
            ['exchange_rate.cache.key_prefix', 'exchange_rates_'],
        ]);

        $this->exchangeRateService = new ExchangeRateService(
            $this->connector,
            $this->cache,
            $this->rateParser,
            $this->parameterBag
        );
    }

    public function testGetTodayRatesReturnsCachedData(): void
    {
        $expectedData = [
            new ExchangeRateDto('USD', 'dollar', 4.25),
        ];

        $expectedKey = $this->generateExpectedCacheKey();

        $this->cache->expects($this->once())
            ->method('get')
            ->with($expectedKey)
            ->willReturn($expectedData);

        $result = $this->exchangeRateService->getTodayRates();

        $this->assertIsArray($result);
        $this->assertEquals($expectedData, $result);
    }

    public function testConstructorSetsParameters(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->method('get')->willReturnMap([
            ['exchange_rate.cache.key_prefix', 'exchange_rates_'],
        ]);

        $expectedKey = $this->generateExpectedCacheKey('exchange_rates_');

        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->with($expectedKey)->willReturn([]);

        $service = new ExchangeRateService(
            $this->connector,
            $cache,
            $this->rateParser,
            $parameterBag
        );

        $result = $service->getTodayRates();
        $this->assertIsArray($result);
    }

    public function testCacheCallbackExecution(): void
    {
        $expectedKey = $this->generateExpectedCacheKey();

        $dummyItem = new class implements ItemInterface {
            public function expiresAfter($time): bool|self { return $this; }
            public function getKey() {}
            public function isHit() {}
            public function get() {}
            public function set($value): static { return $this; }
            public function expiresAt($expiration): static { return $this; }
            public function tag($tags): static { return $this; }
            public function getMetadata(): array { return []; }
        };

        $this->connector->method('getTodayRates')->willReturn(['data']);
        $this->rateParser->method('parseTodayRates')->willReturn([
            new ExchangeRateDto('USD', 'dollar', 4.25),
        ]);

        $this->cache->method('get')->with($expectedKey)->willReturnCallback(
            fn ($key, $callback) => $callback($dummyItem)
        );

        $result = $this->exchangeRateService->getTodayRates();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(ExchangeRateDto::class, $result[0]);
    }

    public function testEmptyDataHandling(): void
    {
        $expectedKey = $this->generateExpectedCacheKey();

        $dummyItem = new class implements ItemInterface {
            public function expiresAfter($time): bool|self { return $this; }
            public function getKey() {}
            public function isHit() {}
            public function get() {}
            public function set($value): static { return $this; }
            public function expiresAt($expiration): static { return $this; }
            public function tag($tags): static { return $this; }
            public function getMetadata(): array { return []; }
        };

        $this->connector->method('getTodayRates')->willReturn([]);
        $this->rateParser->method('parseTodayRates')->willReturn([]);

        $this->cache->method('get')->with($expectedKey)->willReturnCallback(
            fn ($key, $callback) => $callback($dummyItem)
        );

        $result = $this->exchangeRateService->getTodayRates();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    private function generateExpectedCacheKey(string $prefix = 'exchange_rates_'): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Warsaw'));
        $hour = (int) $now->format('H');

        $dateKey = $hour < 12
            ? $now->modify('-1 day')->format('Y-m-d')
            : $now->format('Y-m-d');

        return $prefix . $dateKey;
    }
}
