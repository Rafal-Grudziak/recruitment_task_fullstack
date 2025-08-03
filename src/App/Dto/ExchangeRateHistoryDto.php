<?php

namespace App\Dto;

readonly class ExchangeRateHistoryDto
{
    public function __construct(
        public string $date,
        public float $mid,
        public ?float $buy = null,
        public ?float $sell = null
    ) {}

    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'mid' => $this->mid,
            'buy' => $this->buy,
            'sell' => $this->sell,
        ];
    }
} 