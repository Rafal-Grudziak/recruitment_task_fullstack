<?php

namespace App\Tests\Connector;

use App\Connector\NbpApiConnector;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class NbpApiConnectorTest extends TestCase
{
    private $httpClient;
    private $logger;
    private $parameterBag;
    private $connector;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->parameterBag->method('get')->willReturnMap([
            ['nbp.api.table_a_url', 'https://api.nbp.pl/api/exchangerates/tables/a'],
            ['nbp.api.rates_url_template', 'https://api.nbp.pl/api/exchangerates/rates/a/{code}/{startDate}/{endDate}']
        ]);

        $this->connector = new NbpApiConnector(
            $this->httpClient,
            $this->logger,
            $this->parameterBag
        );
    }

    public function testGetTodayRatesReturnsValidData(): void
    {
        $expectedData = [
            ['table' => 'A', 'no' => '001/A/NBP/2025', 'rates' => []]
        ];

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode($expectedData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.nbp.pl/api/exchangerates/tables/a')
            ->willReturn($response);

        $result = $this->connector->getTodayRates();

        $this->assertEquals($expectedData, $result);
    }

    public function testGetTodayRatesHandlesRequestException(): void
    {
        $exception = new RequestException('Connection failed', $this->createMock(\Psr\Http\Message\RequestInterface::class));

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'HTTP request error to NBP API',
                $this->callback(function ($context) {
                    return isset($context['url']) && 
                           isset($context['message']) && 
                           isset($context['code']);
                })
            );

        $result = $this->connector->getTodayRates();

        $this->assertEquals([], $result);
    }

    public function testGetTodayRatesHandlesInvalidJson(): void
    {
        $invalidJson = 'invalid json response';

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($invalidJson);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->method('request')->willReturn($response);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Invalid JSON received from NBP API',
                $this->callback(function ($context) {
                    return isset($context['url']) && 
                           isset($context['error']) && 
                           isset($context['body']);
                })
            );

        $result = $this->connector->getTodayRates();

        $this->assertEquals([], $result);
    }

    public function testGetHistoricalRatesWithDefaultDate(): void
    {
        $expectedUrl = 'https://api.nbp.pl/api/exchangerates/rates/a/USD/2025-01-19/2025-02-02';
        $expectedData = ['rates' => []];

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode($expectedData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', $expectedUrl)
            ->willReturn($response);

        // Mock today as 2025-02-02 for predictable test
        $today = new \DateTimeImmutable('2025-02-02');
        $result = $this->connector->getHistoricalRates('USD', $today);

        $this->assertEquals($expectedData, $result);
    }

    public function testGetHistoricalRatesWithCustomDate(): void
    {
        $customDate = new \DateTimeImmutable('2025-01-15');
        $expectedUrl = 'https://api.nbp.pl/api/exchangerates/rates/a/EUR/2025-01-01/2025-01-15';
        $expectedData = ['rates' => []];

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode($expectedData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', $expectedUrl)
            ->willReturn($response);

        $result = $this->connector->getHistoricalRates('EUR', $customDate);

        $this->assertEquals($expectedData, $result);
    }

    public function testGetHistoricalRatesWithDateTime(): void
    {
        $dateTime = new \DateTime('2025-01-20 15:30:45');
        $expectedUrl = 'https://api.nbp.pl/api/exchangerates/rates/a/CHF/2025-01-06/2025-01-20';
        $expectedData = ['rates' => []];

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode($expectedData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', $expectedUrl)
            ->willReturn($response);

        $result = $this->connector->getHistoricalRates('CHF', $dateTime);

        $this->assertEquals($expectedData, $result);
    }

    public function testGetRatesForCurrencyBuildsCorrectUrl(): void
    {
        $expectedUrl = 'https://api.nbp.pl/api/exchangerates/rates/a/GBP/2025-01-01/2025-01-31';
        $expectedData = ['rates' => []];

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode($expectedData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', $expectedUrl)
            ->willReturn($response);

        $result = $this->connector->getRatesForCurrency('GBP', '2025-01-01', '2025-01-31');

        $this->assertEquals($expectedData, $result);
    }

    public function testGetRatesForCurrencyHandlesException(): void
    {
        $exception = new RequestException('Timeout', $this->createMock(\Psr\Http\Message\RequestInterface::class));

        $this->httpClient->method('request')->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('HTTP request error to NBP API');

        $result = $this->connector->getRatesForCurrency('USD', '2025-01-01', '2025-01-31');

        $this->assertEquals([], $result);
    }

    public function testGetJsonHandlesGenericException(): void
    {
        $exception = new \RuntimeException('Something went wrong');

        $this->httpClient->method('request')->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected error during NBP API request',
                $this->callback(function ($context) {
                    return isset($context['url']) && 
                           isset($context['message']) && 
                           isset($context['exception_class']) &&
                           $context['exception_class'] === 'RuntimeException';
                })
            );

        $result = $this->connector->getTodayRates();

        $this->assertEquals([], $result);
    }

    public function testConstructorSetsUrlsFromParameterBag(): void
    {
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        
        $parameterBag
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                ['nbp.api.table_a_url'],
                ['nbp.api.rates_url_template']
            )
            ->willReturnOnConsecutiveCalls(
                'https://custom.api.url/table',
                'https://custom.api.url/rates/{code}/{startDate}/{endDate}'
            );

        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn('[]');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'https://custom.api.url/table')
            ->willReturn($response);

        $connector = new NbpApiConnector($httpClient, $this->logger, $parameterBag);
        $connector->getTodayRates();
    }

    public function testIsValidJsonReturnsTrueForValidArray(): void
    {
        $validData = ['key' => 'value'];
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode($validData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->method('request')->willReturn($response);

        $result = $this->connector->getTodayRates();

        $this->assertEquals($validData, $result);
    }

    public function testIsValidJsonReturnsFalseForNonArray(): void
    {
        $invalidData = 'string instead of array';
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn(json_encode($invalidData));

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->method('request')->willReturn($response);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Invalid JSON received from NBP API');

        $result = $this->connector->getTodayRates();

        $this->assertEquals([], $result);
    }

    public function testEmptyResponseHandling(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn('');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        $this->httpClient->method('request')->willReturn($response);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Invalid JSON received from NBP API');

        $result = $this->connector->getTodayRates();

        $this->assertEquals([], $result);
    }
}