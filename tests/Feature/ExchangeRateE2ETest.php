<?php

namespace App\Tests\Feature;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExchangeRateE2ETest extends WebTestCase
{
    public function testGetTodayRatesReturns200(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/rates/today');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        $sample = $data[0];
        $this->assertArrayHasKey('code', $sample);
        $this->assertArrayHasKey('currency', $sample);
        $this->assertArrayHasKey('mid', $sample);
    }

    public function testGetHistoryWithValidParams(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/rates/history?code=USD&date=2024-01-01');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertIsArray($data);
        if (!empty($data)) {
            $sample = $data[0];
            $this->assertArrayHasKey('date', $sample);
            $this->assertArrayHasKey('mid', $sample);
        }
    }

    public function testGetHistoryWithInvalidCurrency(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/rates/history?code=INVALID');

        $this->assertResponseStatusCodeSame(400);

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('errors', $data);
    }


}
