<?php

namespace App\Controller;

use App\Dto\ExchangeRateHistoryRequestDto;
use App\Resource\ExchangeRateHistoryResource;
use App\Service\ExchangeRateHistoryService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ExchangeRateHistoryController extends AbstractController
{

    public function __construct(
        private ExchangeRateHistoryService $exchangeRateHistoryService,
        private ParameterBagInterface $parameterBag
    ) {}

    /**
     * @Route("/api/rates/history", name="api.rates.history", methods={"GET"})
     */
    public function getHistory(Request $request): JsonResponse
    {
        $dto = ExchangeRateHistoryRequestDto::fromRequestWithParameters($request, $this->parameterBag);
        
        $errors = $dto->validate();
        
        if (!empty($errors)) {
            return $this->json(['errors' => $errors], 400);
        }

        $rates = $this->exchangeRateHistoryService->getHistoricalRates($dto->code, $dto->date);

        return $this->json(ExchangeRateHistoryResource::collection($rates));
    }
} 