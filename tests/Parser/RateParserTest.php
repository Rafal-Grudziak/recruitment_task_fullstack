<?php

namespace App\Tests\Parser;

use App\Dto\ExchangeRateDto;
use App\Parser\RateParser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class RateParserTest extends TestCase
{
    private $parameterBag;
    private $parser;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        
        $this->parameterBag->method('get')->willReturnMap([
            ['exchange_rate.allowed_currencies', ['USD', 'EUR', 'GBP', 'CHF']],
            ['exchange_rate.eur_usd_buy_spread', 0.02],
            ['exchange_rate.eur_usd_sell_spread', 0.03],
            ['exchange_rate.other_sell_spread', 0.05]
        ]);

        $this->parser = new RateParser($this->parameterBag);
    }

    public function testParseTodayRatesReturnsValidData(): void
    {
        $inputData = [[
            'table' => 'A',
            'rates' => [
                ['code' => 'USD', 'currency' => 'dolar amerykański', 'mid' => 4.2500],
                ['code' => 'EUR', 'currency' => 'euro', 'mid' => 4.5000],
                ['code' => 'GBP', 'currency' => 'funt szterling', 'mid' => 5.2000],
            ]
        ]];

        $result = $this->parser->parseTodayRates($inputData);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertInstanceOf(ExchangeRateDto::class, $result[0]);
    }

    public function testParseTodayRatesFiltersAllowedCurrencies(): void
    {
        $inputData = [[
            'table' => 'A',
            'rates' => [
                ['code' => 'USD', 'currency' => 'dolar amerykański', 'mid' => 4.2500],
                ['code' => 'JPY', 'currency' => 'jen japoński', 'mid' => 0.0280],
                ['code' => 'EUR', 'currency' => 'euro', 'mid' => 4.5000],
                ['code' => 'CAD', 'currency' => 'dolar kanadyjski', 'mid' => 3.1000]
            ]
        ]];

        $result = $this->parser->parseTodayRates($inputData);

        $this->assertCount(2, $result);
        $this->assertEquals('USD', $result[0]->code);
        $this->assertEquals('EUR', $result[1]->code);
    }

    public function testParseTodayRatesCalculatesEurUsdSpreads(): void
    {
        $inputData = [[
            'table' => 'A',
            'rates' => [
                ['code' => 'USD', 'currency' => 'dolar amerykański', 'mid' => 4.2500],
                ['code' => 'EUR', 'currency' => 'euro', 'mid' => 4.5000]
            ]
        ]];

        $result = $this->parser->parseTodayRates($inputData);

        $usdDto = $result[0];
        $this->assertEquals('USD', $usdDto->code);
        $this->assertEqualsWithDelta(4.25, $usdDto->mid, 0.0001);
        $this->assertEqualsWithDelta(4.23, $usdDto->buy, 0.0001);
        $this->assertEqualsWithDelta(4.28, $usdDto->sell, 0.0001);

        $eurDto = $result[1];
        $this->assertEquals('EUR', $eurDto->code);
        $this->assertEqualsWithDelta(4.5, $eurDto->mid, 0.0001);
        $this->assertEqualsWithDelta(4.48, $eurDto->buy, 0.0001);
        $this->assertEqualsWithDelta(4.53, $eurDto->sell, 0.0001);
    }

    public function testParseTodayRatesCalculatesOtherCurrencySpreads(): void
    {
        $inputData = [[
            'table' => 'A',
            'rates' => [
                ['code' => 'GBP', 'currency' => 'funt szterling', 'mid' => 5.2000],
                ['code' => 'CHF', 'currency' => 'frank szwajcarski', 'mid' => 4.6000]
            ]
        ]];

        $result = $this->parser->parseTodayRates($inputData);

        $gbpDto = $result[0];
        $this->assertEquals('GBP', $gbpDto->code);
        $this->assertEqualsWithDelta(5.2, $gbpDto->mid, 0.0001);
        $this->assertNull($gbpDto->buy);
        $this->assertEqualsWithDelta(5.25, $gbpDto->sell, 0.0001);

        $chfDto = $result[1];
        $this->assertEquals('CHF', $chfDto->code);
        $this->assertEqualsWithDelta(4.6, $chfDto->mid, 0.0001);
        $this->assertNull($chfDto->buy);
        $this->assertEqualsWithDelta(4.65, $chfDto->sell, 0.0001);
    }

    public function testParseTodayRatesHandlesEmptyData(): void
    {
        $result = $this->parser->parseTodayRates([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testParseTodayRatesHandlesDataWithoutRates(): void
    {
        $inputData = [['table' => 'A', 'no' => '001/A/NBP/2025']];
        $result = $this->parser->parseTodayRates($inputData);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testParseTodayRatesHandlesEmptyRatesArray(): void
    {
        $inputData = [['table' => 'A', 'rates' => []]];
        $result = $this->parser->parseTodayRates($inputData);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testConstructorInitializesParametersCorrectly(): void
    {
        $customParameterBag = $this->createMock(ParameterBagInterface::class);
        $customParameterBag->method('get')->willReturnMap([
            ['exchange_rate.allowed_currencies', ['USD', 'EUR']],
            ['exchange_rate.eur_usd_buy_spread', 0.01],
            ['exchange_rate.eur_usd_sell_spread', 0.02],
            ['exchange_rate.other_sell_spread', 0.04]
        ]);

        $parser = new RateParser($customParameterBag);

        $inputData = [[
            'table' => 'A',
            'rates' => [
                ['code' => 'USD', 'currency' => 'dolar amerykański', 'mid' => 4.0000],
                ['code' => 'GBP', 'currency' => 'funt szterling', 'mid' => 5.0000]
            ]
        ]];

        $result = $parser->parseTodayRates($inputData);
        $this->assertCount(1, $result);
        $this->assertEquals('USD', $result[0]->code);
        $this->assertEqualsWithDelta(3.99, $result[0]->buy, 0.0001);
        $this->assertEqualsWithDelta(4.02, $result[0]->sell, 0.0001);
    }

    public function testCreateDtoFromRateForEurCurrency(): void
    {
        $inputData = [[
            'table' => 'A',
            'rates' => [['code' => 'EUR', 'currency' => 'euro', 'mid' => 4.3000]]
        ]];

        $result = $this->parser->parseTodayRates($inputData);
        $dto = $result[0];
        $this->assertEquals('EUR', $dto->code);
        $this->assertEquals('euro', $dto->currency);
        $this->assertEqualsWithDelta(4.3, $dto->mid, 0.0001);
        $this->assertEqualsWithDelta(4.28, $dto->buy, 0.0001);
        $this->assertEqualsWithDelta(4.33, $dto->sell, 0.0001);
    }

    public function testCreateDtoFromRateForNonEurUsdCurrency(): void
    {
        $inputData = [[
            'table' => 'A',
            'rates' => [['code' => 'CHF', 'currency' => 'frank szwajcarski', 'mid' => 4.7000]]
        ]];

        $result = $this->parser->parseTodayRates($inputData);
        $dto = $result[0];
        $this->assertEquals('CHF', $dto->code);
        $this->assertEquals('frank szwajcarski', $dto->currency);
        $this->assertEqualsWithDelta(4.7, $dto->mid, 0.0001);
        $this->assertNull($dto->buy);
        $this->assertEqualsWithDelta(4.75, $dto->sell, 0.0001);
    }

    public function testParseTodayRatesWithMixedAllowedAndDisallowedCurrencies(): void
    {
        $inputData = [[
            'table' => 'A',
            'rates' => [
                ['code' => 'USD', 'currency' => 'dolar amerykański', 'mid' => 4.2500],
                ['code' => 'JPY', 'currency' => 'jen japoński', 'mid' => 0.0280],
                ['code' => 'EUR', 'currency' => 'euro', 'mid' => 4.5000],
                ['code' => 'CAD', 'currency' => 'dolar kanadyjski', 'mid' => 3.1000],
                ['code' => 'GBP', 'currency' => 'funt szterling', 'mid' => 5.2000],
                ['code' => 'AUD', 'currency' => 'dolar australijski', 'mid' => 2.8000],
                ['code' => 'CHF', 'currency' => 'frank szwajcarski', 'mid' => 4.6000] // ← dodane
            ]
        ]];

        $result = $this->parser->parseTodayRates($inputData);

        $this->assertCount(4, $result);

        $codes = array_map(fn($dto) => $dto->code, $result);
        $this->assertEqualsCanonicalizing(['USD', 'EUR', 'GBP', 'CHF'], $codes);
        $this->assertNotContains('JPY', $codes);
        $this->assertNotContains('CAD', $codes);
        $this->assertNotContains('AUD', $codes);
    }
}
