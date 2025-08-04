<?php

namespace App\Controller;

use App\Resource\ExchangeRateResource;
use App\Service\ExchangeRateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


class ExchangeRateController extends AbstractController
{

    public function __construct(
        private ExchangeRateService $exchangeRateService
    ) {}

    /**
     * @Route("/api/rates/today", name="api.rates.today", methods={"GET"})
     */
    public function getTodayRates(): JsonResponse
    {
        $rates = $this->exchangeRateService->getTodayRates();
        
        return $this->json(ExchangeRateResource::collection($rates));
    }
}
