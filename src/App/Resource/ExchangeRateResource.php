<?php

namespace App\Resource;

use App\Dto\ExchangeRateDto;
use App\Helper\NumberHelper;

class ExchangeRateResource extends AbstractResource
{
    /**
     * Transform the exchange rate item to array format
     *
     * @return array
     */
    public function toArray(): array
    {
        /** @var ExchangeRateDto $dto */
        $dto = $this->item;
        
        return [
            'code' => $dto->code,
            'currency' => $dto->currency,
            'mid' => NumberHelper::round2($dto->mid),
            'buy' => $dto->buy !== null ? NumberHelper::round2($dto->buy) : null,
            'sell' => $dto->sell !== null ? NumberHelper::round2($dto->sell) : null,
        ];
    }
} 