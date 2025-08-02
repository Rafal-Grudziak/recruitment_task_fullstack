<?php

namespace App\Tests\Resource;

use App\Dto\ExchangeRateDto;
use App\Resource\ExchangeRateResource;
use PHPUnit\Framework\TestCase;

class ExchangeRateResourceTest extends TestCase
{
    public function testToArrayReturnsCorrectStructure(): void
    {
        $dto = new ExchangeRateDto('USD', 'US Dollar', 4.1234, 4.01, 4.20);
        $resource = new ExchangeRateResource($dto);

        $result = $resource->toArray();

        $this->assertIsArray($result);
        $this->assertEquals([
            'code' => 'USD',
            'currency' => 'US Dollar',
            'mid' => 4.12, // zaokrąglone przez NumberHelper
            'buy' => 4.01,
            'sell' => 4.2,
        ], $result);
    }

    public function testCollectionTransformsArrayOfDtos(): void
    {
        $dtos = [
            new ExchangeRateDto('USD', 'US Dollar', 4.1234, 4.01, 4.20),
            new ExchangeRateDto('EUR', 'Euro', 4.5432, null, null),
        ];

        $result = ExchangeRateResource::collection($dtos);

        $this->assertCount(2, $result);
        $this->assertEquals('USD', $result[0]['code']);
        $this->assertEquals('EUR', $result[1]['code']);
        $this->assertEquals(4.12, $result[0]['mid']);
        $this->assertNull($result[1]['buy']);
    }
}
