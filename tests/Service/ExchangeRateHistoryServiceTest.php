<?php

namespace App\Tests\Service;

use App\Connector\NbpApiConnectorInterface;
use App\Dto\ExchangeRateHistoryDto;
use App\Service\ExchangeRateHistoryService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ExchangeRateHistoryServiceTest extends TestCase
{
    private NbpApiConnectorInterface $connector;
    private ParameterBagInterface $parameterBag;
    private ExchangeRateHistoryService $service;


    protected function setUp(): void
    {
        $this->connector = $this->createMock(NbpApiConnectorInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->parameterBag->method('get')->with('exchange_rate.allowed_currencies')->willReturn(['USD', 'EUR', 'GBP', 'CHF']);

        $this->service = new ExchangeRateHistoryService(
            $this->connector,
            $this->parameterBag
        );
    }

    public function testGetHistoricalRatesReturnsValidData(): void
    {
        $mockData = [
            'rates' => [
                ['effectiveDate' => '2025-01-15', 'mid' => 4.2500],
                ['effectiveDate' => '2025-01-16', 'mid' => 4.2600],
                ['effectiveDate' => '2025-01-17', 'mid' => 4.2400]
            ]
        ];

        $this->connector
            ->expects($this->once())
            ->method('getHistoricalRates')
            ->with('USD', null)
            ->willReturn($mockData);

        $result = $this->service->getHistoricalRates('USD');

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertInstanceOf(ExchangeRateHistoryDto::class, $result[0]);
        $this->assertEquals('2025-01-15', $result[0]->date);
        $this->assertEquals(4.2500, $result[0]->mid);
    }

    public function testGetHistoricalRatesWithCustomDate(): void
    {
        $customDate = new \DateTimeImmutable('2025-01-20');
        $mockData = [
            'rates' => [
                ['effectiveDate' => '2025-01-20', 'mid' => 4.5000]
            ]
        ];

        $this->connector
            ->expects($this->once())
            ->method('getHistoricalRates')
            ->with('EUR', $customDate)
            ->willReturn($mockData);

        $result = $this->service->getHistoricalRates('EUR', $customDate);

        $this->assertCount(1, $result);
        $this->assertEquals('2025-01-20', $result[0]->date);
        $this->assertEquals(4.5000, $result[0]->mid);
    }

    public function testGetHistoricalRatesThrowsExceptionForInvalidCurrency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Currency code 'XYZ' is not allowed.");

        $this->service->getHistoricalRates('XYZ');
    }

    public function testGetHistoricalRatesHandlesEmptyResponse(): void
    {
        $this->connector
            ->method('getHistoricalRates')
            ->with('USD', null)
            ->willReturn([]);

        $result = $this->service->getHistoricalRates('USD');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetHistoricalRatesHandlesResponseWithoutRates(): void
    {
        $mockData = ['table' => 'A', 'currency' => 'dolar amerykański'];

        $this->connector
            ->method('getHistoricalRates')
            ->with('USD', null)
            ->willReturn($mockData);

        $result = $this->service->getHistoricalRates('USD');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetHistoricalRatesHandlesInvalidRatesData(): void
    {
        $mockData = ['rates' => 'not an array'];

        $this->connector
            ->method('getHistoricalRates')
            ->with('USD', null)
            ->willReturn($mockData);

        $result = $this->service->getHistoricalRates('USD');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetHistoricalRatesHandlesEmptyRatesArray(): void
    {
        $mockData = ['rates' => []];

        $this->connector
            ->method('getHistoricalRates')
            ->with('USD', null)
            ->willReturn($mockData);

        $result = $this->service->getHistoricalRates('USD');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testConstructorInitializesAllowedCurrencies(): void
    {
        $customCurrencies = ['PLN', 'USD', 'EUR'];
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        
        $parameterBag
            ->expects($this->once())
            ->method('get')
            ->with('exchange_rate.allowed_currencies')
            ->willReturn($customCurrencies);

        $connector = $this->createMock(NbpApiConnectorInterface::class);

        $service = new ExchangeRateHistoryService($connector, $parameterBag);

        $this->expectException(\InvalidArgumentException::class);
        $service->getHistoricalRates('JPY');
    }

    public function testAllowedCurrenciesValidation(): void
    {
        $allowedCodes = ['USD', 'EUR', 'GBP', 'CHF'];

        foreach ($allowedCodes as $code) {
            $this->connector->method('getHistoricalRates')->willReturn(['rates' => []]);
            
            $result = $this->service->getHistoricalRates($code);
            $this->assertIsArray($result);
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->service->getHistoricalRates('JPY');
    }

    public function testGetHistoricalRatesWithDateTimeObject(): void
    {
        $dateTime = new \DateTime('2025-01-25 12:00:00');
        $mockData = [
            'rates' => [
                ['effectiveDate' => '2025-01-25', 'mid' => 0.8500]
            ]
        ];

        $this->connector
            ->expects($this->once())
            ->method('getHistoricalRates')
            ->with('GBP', $dateTime)
            ->willReturn($mockData);

        $result = $this->service->getHistoricalRates('GBP', $dateTime);

        $this->assertCount(1, $result);
        $this->assertEquals('2025-01-25', $result[0]->date);
        $this->assertEquals(0.8500, $result[0]->mid);
    }

    public function testGetHistoricalRatesCreatesCorrectDtoObjects(): void
    {
        $mockData = [
            'rates' => [
                ['effectiveDate' => '2025-01-01', 'mid' => 1.1000],
                ['effectiveDate' => '2025-01-02', 'mid' => 1.1100],
                ['effectiveDate' => '2025-01-03', 'mid' => 1.0900]
            ]
        ];

        $this->connector->method('getHistoricalRates')->willReturn($mockData);

        $result = $this->service->getHistoricalRates('EUR');

        $this->assertCount(3, $result);
        
        $this->assertInstanceOf(ExchangeRateHistoryDto::class, $result[0]);
        $this->assertInstanceOf(ExchangeRateHistoryDto::class, $result[1]);
        $this->assertInstanceOf(ExchangeRateHistoryDto::class, $result[2]);

        $this->assertEquals('2025-01-01', $result[0]->date);
        $this->assertEquals(1.1000, $result[0]->mid);
        
        $this->assertEquals('2025-01-02', $result[1]->date);
        $this->assertEquals(1.1100, $result[1]->mid);
        
        $this->assertEquals('2025-01-03', $result[2]->date);
        $this->assertEquals(1.0900, $result[2]->mid);
    }
}