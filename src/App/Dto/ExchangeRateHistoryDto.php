<?php

namespace App\Dto;

class ExchangeRateHistoryDto
{
    public function __construct(
        public string $date,
        public float $mid
    ) {}

    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'mid' => $this->mid,
        ];
    }
} 