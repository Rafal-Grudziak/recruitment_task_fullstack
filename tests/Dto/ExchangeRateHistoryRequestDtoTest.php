<?php

namespace App\Tests\Dto;

use App\Dto\ExchangeRateHistoryRequestDto;
use PHPUnit\Framework\TestCase;

class ExchangeRateHistoryRequestDtoTest extends TestCase
{
    public function testValidData(): void
    {
        $dto = ExchangeRateHistoryRequestDto::fromArray([
            'code' => 'USD',
            'date' => '2025-08-01'
        ], ['USD', 'EUR']);

        $this->assertTrue($dto->isValidCode());
        $this->assertTrue($dto->isValidDate());
        $this->assertCount(0, $dto->validate());
    }

    public function testInvalidCurrencyCode(): void
    {
        $dto = ExchangeRateHistoryRequestDto::fromArray([
            'code' => 'XYZ',
            'date' => '2025-08-01'
        ], ['USD', 'EUR']);

        $this->assertFalse($dto->isValidCode());
        $this->assertEquals([
            'Nieprawidłowy kod waluty "XYZ". Dozwolone wartości: USD, EUR.'
        ], $dto->validate());
    }

    public function testMissingCurrencyCode(): void
    {
        $dto = ExchangeRateHistoryRequestDto::fromArray([
            'date' => '2025-08-01'
        ], ['USD', 'EUR']);

        $this->assertFalse($dto->isValidCode());
        $this->assertEquals([
            'Brak kodu waluty'
        ], $dto->validate());
    }

    public function testInvalidDateFormat(): void
    {
        $dto = ExchangeRateHistoryRequestDto::fromArray([
            'code' => 'USD',
            'date' => '2025/08/01' // niepoprawny format
        ], ['USD']);

        $this->assertFalse($dto->isValidDate());
        $this->assertContains(
            'Nieprawidłowy format daty. Oczekiwany format: RRRR-MM-DD.',
            $dto->validate()
        );
    }

    public function testMissingDateAllowed(): void
    {
        $dto = ExchangeRateHistoryRequestDto::fromArray([
            'code' => 'USD'
        ], ['USD']);

        $this->assertTrue($dto->isValidDate());
        $this->assertCount(0, $dto->validate());
    }
}
