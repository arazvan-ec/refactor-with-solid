<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Adapter;

use App\Infrastructure\Adapter\AsyncHttpClientAdapter;
use App\Infrastructure\Port\HttpClientInterface;
use Http\Client\HttpAsyncClient;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(AsyncHttpClientAdapter::class)]
final class AsyncHttpClientAdapterTest extends TestCase
{
    #[Test]
    public function implements_http_client_interface(): void
    {
        $this->assertTrue(
            is_a(AsyncHttpClientAdapter::class, HttpClientInterface::class, true)
        );
    }

    #[Test]
    public function request_async_returns_promise(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $stream->method('__toString')->willReturn('{"data": "test"}');
        $response->method('getBody')->willReturn($stream);

        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);

        $httpClient = $this->createMock(HttpAsyncClient::class);
        $httpClient->method('sendAsyncRequest')
            ->willReturn(new FulfilledPromise($response));

        $adapter = new AsyncHttpClientAdapter($httpClient, $requestFactory);

        $result = $adapter->requestAsync(
            'GET',
            'http://example.com/api',
            [],
            static fn (ResponseInterface $r) => json_decode($r->getBody()->__toString(), true)
        );

        $this->assertInstanceOf(Promise::class, $result);
    }

    #[Test]
    public function request_waits_for_promise_and_returns_result(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $stream->method('__toString')->willReturn('{"data": "test"}');
        $response->method('getBody')->willReturn($stream);

        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);

        $httpClient = $this->createMock(HttpAsyncClient::class);
        $httpClient->method('sendAsyncRequest')
            ->willReturn(new FulfilledPromise($response));

        $adapter = new AsyncHttpClientAdapter($httpClient, $requestFactory);

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
    public function transformer_is_applied_to_response(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $stream = $this->createMock(StreamInterface::class);

        $stream->method('__toString')->willReturn('raw body');
        $response->method('getBody')->willReturn($stream);
        $response->method('getStatusCode')->willReturn(200);

        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);

        $httpClient = $this->createMock(HttpAsyncClient::class);
        $httpClient->method('sendAsyncRequest')
            ->willReturn(new FulfilledPromise($response));

        $adapter = new AsyncHttpClientAdapter($httpClient, $requestFactory);

        $transformerCalled = false;
        $result = $adapter->request(
            'GET',
            'http://example.com/api',
            [],
            static function (ResponseInterface $r) use (&$transformerCalled) {
                $transformerCalled = true;

                return ['status' => $r->getStatusCode(), 'body' => $r->getBody()->__toString()];
            }
        );

        $this->assertTrue($transformerCalled);
        $this->assertSame(200, $result['status']);
        $this->assertSame('raw body', $result['body']);
    }
}
