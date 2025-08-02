<?php

namespace App\Dto;

class ExchangeRateHistoryDto
{
    public function __construct(
        public string $date,
        public float $mid
    ) {}
} 