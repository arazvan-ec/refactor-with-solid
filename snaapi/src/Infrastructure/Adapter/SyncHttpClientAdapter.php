<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Infrastructure\Port\HttpClientInterface;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Adapter for synchronous HTTP requests.
 *
 * Uses PSR-18 ClientInterface to execute requests synchronously.
 * Implements HttpClientInterface for dependency inversion.
 * requestAsync() returns a FulfilledPromise for compatibility.
 *
 * @implements HttpClientInterface<mixed>
 */
final readonly class SyncHttpClientAdapter implements HttpClientInterface
{
    public function __construct(
        private ClientInterface $httpClient,
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
        $request = $this->requestFactory->createRequest($method, $uri);
        $response = $this->httpClient->sendRequest($request);

        return $transformer($response);
    }

    /**
     * {@inheritDoc}
     *
     * Returns a FulfilledPromise with the result for API compatibility.
     * The request is executed immediately (synchronously).
     */
    public function requestAsync(
        string $method,
        string $uri,
        array $options,
        callable $transformer,
    ): Promise {
        $result = $this->request($method, $uri, $options, $transformer);

        return new FulfilledPromise($result);
    }
}
