<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Adapter;

use App\Infrastructure\Adapter\SyncHttpClientAdapter;
use App\Infrastructure\Port\HttpClientInterface;
use Http\Promise\Promise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(SyncHttpClientAdapter::class)]
final class SyncHttpClientAdapterTest extends TestCase
{
    #[Test]
    public function implements_http_client_interface(): void
    {
        $this->assertTrue(
            is_a(SyncHttpClientAdapter::class, HttpClientInterface::class, true)
        );
    }

    #[Test]
    public function request_returns_transformed_result_directly(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $stream->method('__toString')->willReturn('{"data": "test"}');
        $response->method('getBody')->willReturn($stream);

        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn($response);

        $adapter = new SyncHttpClientAdapter($httpClient, $requestFactory);

        $result = $adapter->request(
            'GET',
            'http://example.com/api',
            [],
            static fn (ResponseInterface $r) => json_decode($r->getBody()->__toString(), true)
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('test', $result['data']);
    }

    #[Test]
    public function request_async_returns_fulfilled_promise(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $stream->method('__toString')->willReturn('{"async": true}');
        $response->method('getBody')->willReturn($stream);

        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn($response);

        $adapter = new SyncHttpClientAdapter($httpClient, $requestFactory);

        $promise = $adapter->requestAsync(
            'GET',
            'http://example.com/api',
            [],
            static fn (ResponseInterface $r) => json_decode($r->getBody()->__toString(), true)
        );

        $this->assertInstanceOf(Promise::class, $promise);

        $result = $promise->wait();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('async', $result);
    }

    #[Test]
    public function transformer_is_applied_to_response(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $stream->method('__toString')->willReturn('raw body');
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(201);

        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn($response);

        $adapter = new SyncHttpClientAdapter($httpClient, $requestFactory);

        $transformerCalled = false;
        $result = $adapter->request(
            'POST',
            'http://example.com/api',
            [],
            static function (ResponseInterface $r) use (&$transformerCalled) {
                $transformerCalled = true;

                return ['status' => $r->getStatusCode(), 'body' => $r->getBody()->__toString()];
            }
        );

        $this->assertTrue($transformerCalled);
        $this->assertSame(201, $result['status']);
        $this->assertSame('raw body', $result['body']);
    }
}
