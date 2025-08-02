<?php

namespace App\Tests\Resource;

use App\Dto\ExchangeRateHistoryDto;
use App\Resource\ExchangeRateHistoryResource;
use PHPUnit\Framework\TestCase;

class ExchangeRateHistoryResourceTest extends TestCase
{
    public function testToArrayReturnsCorrectStructure(): void
    {
        $dto = new ExchangeRateHistoryDto('2025-07-31', 4.5678);
        $resource = new ExchangeRateHistoryResource($dto);

        $result = $resource->toArray();

        $this->assertIsArray($result);
        $this->assertEquals([
            'date' => '2025-07-31',
            'mid' => 4.57, // zakładamy, że NumberHelper::round2() zaokrągla
        ], $result);
    }

    public function testCollectionTransformsArrayOfDtos(): void
    {
        $dtos = [
            new ExchangeRateHistoryDto('2025-07-31', 4.5678),
            new ExchangeRateHistoryDto('2025-07-30', 4.4321),
        ];

        $result = ExchangeRateHistoryResource::collection($dtos);

        $this->assertCount(2, $result);
        $this->assertEquals('2025-07-31', $result[0]['date']);
        $this->assertEquals(4.57, $result[0]['mid']);
        $this->assertEquals('2025-07-30', $result[1]['date']);
        $this->assertEquals(4.43, $result[1]['mid']);
    }
}
