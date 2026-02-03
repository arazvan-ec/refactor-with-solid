<?php

declare(strict_types=1);

namespace App\Infrastructure\Port;

use Http\Promise\Promise;

/**
 * Port for HTTP client abstraction.
 *
 * Allows high-level code to depend on this interface instead of concrete HTTP clients.
 * Supports both sync and async execution strategies.
 *
 * @template T
 */
interface HttpClientInterface
{
    /**
     * Execute a synchronous HTTP request.
     *
     * @template TResult
     *
     * @param string               $method      HTTP method (GET, POST, etc.)
     * @param string               $uri         Request URI
     * @param array<string, mixed> $options     Request options (headers, body, etc.)
     * @param callable             $transformer Function to transform ResponseInterface to desired type
     *
     * @return TResult The transformed response
     */
    public function request(
        string $method,
        string $uri,
        array $options,
        callable $transformer,
    ): mixed;

    /**
     * Execute an asynchronous HTTP request.
     *
     * @template TResult
     *
     * @param string               $method      HTTP method (GET, POST, etc.)
     * @param string               $uri         Request URI
     * @param array<string, mixed> $options     Request options (headers, body, etc.)
     * @param callable             $transformer Function to transform ResponseInterface to desired type
     *
     * @return Promise<TResult> Promise that resolves to the transformed response
     */
    public function requestAsync(
        string $method,
        string $uri,
        array $options,
        callable $transformer,
    ): Promise;
}
