<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExchangeRateHistoryControllerTest extends WebTestCase
{
    public function testValidRequestReturns200()
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/history?code=USD&date=2025-08-01');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(
            'application/json',
            $client->getResponse()->headers->get('Content-Type')
        );

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('date', $data[0]);
        $this->assertArrayHasKey('mid', $data[0]);
    }

    public function testMissingCodeReturns400()
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/history?date=2025-08-01');

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertStringContainsString('Brak kodu waluty', $data['errors'][0]);
    }

    public function testInvalidCodeReturns400()
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/history?code=ZZZ&date=2025-08-01');

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertStringContainsString('Nieprawidłowy kod waluty', $data['errors'][0]);
    }

    public function testInvalidDateFormatReturns400()
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/history?code=USD&date=01-08-2025');

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
        $this->assertStringContainsString('Nieprawidłowy format daty', $data['errors'][0]);
    }
}
