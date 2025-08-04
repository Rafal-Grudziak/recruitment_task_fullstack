<?php

namespace App\Connector;

interface NbpApiConnectorInterface
{
    /**
     * Get today's exchange rates
     *
     * @return array
     */
    public function getTodayRates(): array;

    /**
     * Get exchange rates for specific currency and date range
     *
     * @param string $code
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getRatesForCurrency(string $code, string $startDate, string $endDate): array;

    /**
     * Get historical rates for a currency with automatic date calculation
     *
     * @param string $code
     * @param \DateTimeInterface|null $endDate
     * @return array
     */
    public function getHistoricalRates(string $code, ?\DateTimeInterface $endDate = null): array;
} 