<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExchangeRateControllerTest extends WebTestCase
{
    public function testGetTodayRatesReturnsJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/today');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey(0, $data); // jeśli spodziewasz się tablicy kursów
        $this->assertArrayHasKey('code', $data[0]);
        $this->assertArrayHasKey('currency', $data[0]);
        $this->assertArrayHasKey('mid', $data[0]);
    }
}
