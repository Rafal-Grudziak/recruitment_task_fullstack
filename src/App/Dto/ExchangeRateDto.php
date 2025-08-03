<?php

namespace App\Dto;

readonly class ExchangeRateDto
{
    public function __construct(
        public string $code,
        public string $currency,
        public float $mid,
        public ?float $buy = null,
        public ?float $sell = null
    ) {}

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'currency' => $this->currency,
            'mid' => $this->mid,
            'buy' => $this->buy,
            'sell' => $this->sell,
        ];
    }
} 