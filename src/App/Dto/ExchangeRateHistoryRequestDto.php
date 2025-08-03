<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ExchangeRateHistoryRequestDto
{
    public string $code;
    public ?\DateTimeImmutable $date = null;
    private bool $hasDateParam = false;
    private array $allowedCodes;

    private function __construct(string $code, ?\DateTimeImmutable $date = null, array $allowedCodes = [])
    {
        $this->code = $code;
        $this->date = $date;
        $this->allowedCodes = $allowedCodes;
    }

    /**
     * Create DTO from HTTP request with parameters
     *
     * @param Request $request
     * @param ParameterBagInterface $parameterBag
     * @return self
     */
    public static function fromRequestWithParameters(Request $request, ParameterBagInterface $parameterBag): self
    {
        $code = $request->query->get('code', '');
        $dateParam = $request->query->get('date');
        $allowedCodes = $parameterBag->get('exchange_rate.allowed_currencies');
    
        $date = null;
        $hasDateParam = false;
    
        if ($dateParam) {
            $hasDateParam = true;
            $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $dateParam);
    
            // Checking the correctness of the format and values
            if ($parsed && $parsed->format('Y-m-d') === $dateParam) {
                $date = $parsed;
            }
        }
    
        $dto = new self($code, $date, $allowedCodes);
        $dto->hasDateParam = $hasDateParam;
    
        return $dto;
    }

    /**
     * Create DTO from array (useful for testing)
     *
     * @param array $data
     * @param array $allowedCodes
     * @return self
     */
    public static function fromArray(array $data, array $allowedCodes = []): self
    {
        $code = $data['code'] ?? '';
        $dateParam = $data['date'] ?? null;

        $date = null;
        $hasDateParam = false;

        if ($dateParam) {
            $hasDateParam = true;
            $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $dateParam);

            if ($parsed && $parsed->format('Y-m-d') === $dateParam) {
                $date = $parsed;
            }
        }

        $dto = new self($code, $date, $allowedCodes);
        $dto->hasDateParam = $hasDateParam;

        return $dto;
    }

    /**
     * Validate the DTO data
     *
     * @return array Array of error messages (empty if valid)
     */
    final public function validate(): array
    {
        $errors = [];

        if (!$this->isValidCode()) {
            $errors[] = $this->getCodeErrorMessage();
        }

        if (!$this->date instanceof \DateTimeImmutable && $this->hasDateParam) {
            $errors[] = 'Nieprawidłowy format daty. Oczekiwany format: RRRR-MM-DD.';
        } elseif ($this->date && $this->date > new \DateTimeImmutable('now', new \DateTimeZone('Europe/Warsaw'))) {
            $errors[] = 'Data nie może być z przyszłości.';
        }

        return $errors;
    }

    /**
     * Check if currency code is valid
     *
     * @return bool
     */
    public function isValidCode(): bool
    {
        return !empty($this->code) && in_array($this->code, $this->allowedCodes);
    }

    /**
     * Check if date is valid
     *
     * @return bool
     */
    public function isValidDate(): bool
    {
        if (!$this->hasDateParam) {
            return true;
        }

        if (!$this->date instanceof \DateTimeImmutable) {
            return false;
        }

        // Sprawdzenie, czy data nie jest z przyszłości
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Warsaw'));
        return $this->date <= $now;
    }

    /**
     * Get error message for invalid code
     *
     * @return string
     */
    private function getCodeErrorMessage(): string
    {
        if (empty($this->code)) {
            return 'Brak kodu waluty';
        }
        
        $allowedCodes = implode(', ', $this->allowedCodes);
        return sprintf('Nieprawidłowy kod waluty "%s". Dozwolone wartości: %s.', $this->code, $allowedCodes);
    }

    /**
     * Get list of allowed currency codes
     *
     * @return array
     */
    public function getAllowedCodes(): array
    {
        return $this->allowedCodes;
    }
} 