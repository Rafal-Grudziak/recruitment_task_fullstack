<?php

namespace App\Tests\Resource;

use App\Dto\ExchangeRateHistoryDto;
use App\Resource\ExchangeRateHistoryResource;
use PHPUnit\Framework\TestCase;

class ExchangeRateHistoryResourceTest extends TestCase
{
    public function testToArrayReturnsCorrectStructure(): void
    {
        $dto = new ExchangeRateHistoryDto('2025-07-31', 4.5678, null, null);
        $resource = new ExchangeRateHistoryResource($dto);

        $result = $resource->toArray();

        $this->assertIsArray($result);
        $this->assertEquals([
            'date' => '2025-07-31',
            'mid' => 4.57,
            'buy' => null,
            'sell' => null,
        ], $result);
    }

    public function testCollectionTransformsArrayOfDtos(): void
    {
        $dtos = [
            new ExchangeRateHistoryDto('2025-07-31', 4.5678, 4.50, 4.60),
            new ExchangeRateHistoryDto('2025-07-30', 4.4321, null, 4.55),
        ];

        $result = ExchangeRateHistoryResource::collection($dtos);

        $this->assertCount(2, $result);

        $this->assertEquals([
            'date' => '2025-07-31',
            'mid' => 4.57,
            'buy' => 4.50,
            'sell' => 4.60,
        ], $result[0]);

        $this->assertEquals([
            'date' => '2025-07-30',
            'mid' => 4.43,
            'buy' => null,
            'sell' => 4.55,
        ], $result[1]);
    }
}