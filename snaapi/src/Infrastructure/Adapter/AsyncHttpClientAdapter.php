<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Infrastructure\Port\HttpClientInterface;
use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Adapter for asynchronous HTTP requests.
 *
 * Uses HttpAsyncClient (PSR-18 async) to execute requests and returns Promises.
 * Implements HttpClientInterface for dependency inversion.
 *
 * @implements HttpClientInterface<mixed>
 */
final readonly class AsyncHttpClientAdapter implements HttpClientInterface
{
    public function __construct(
        private HttpAsyncClient $httpClient,
        private RequestFactoryInterface $requestFactory,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function request(
        string $method,
        string $uri,
        array $options,
        callable $transformer,
    ): mixed {
        return $this->requestAsync($method, $uri, $options, $transformer)->wait();
    }

    /**
     * {@inheritDoc}
     */
    public function requestAsync(
        string $method,
        string $uri,
        array $options,
        callable $transformer,
    ): Promise {
        $request = $this->requestFactory->createRequest($method, $uri);

        return $this->httpClient
            ->sendAsyncRequest($request)
            ->then($transformer);
    }
}
