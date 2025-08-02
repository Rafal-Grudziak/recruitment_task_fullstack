<?php

namespace App\Resource;

use App\Dto\ExchangeRateHistoryDto;
use App\Helper\NumberHelper;

class ExchangeRateHistoryResource extends AbstractResource
{
    /**
     * Transform the historical exchange rate item to array format
     *
     * @return array
     */
    public function toArray(): array
    {
        /** @var ExchangeRateHistoryDto $dto */
        $dto = $this->item;
        
        return [
            'date' => $dto->date,
            'mid' => NumberHelper::round2($dto->mid),
        ];
    }
} 