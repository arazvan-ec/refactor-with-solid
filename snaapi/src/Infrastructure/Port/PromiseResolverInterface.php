<?php

declare(strict_types=1);

namespace App\Infrastructure\Port;

use Http\Promise\Promise;

/**
 * Port for promise resolution abstraction.
 *
 * Allows high-level code to resolve multiple promises in parallel
 * without depending on concrete promise libraries (Guzzle, React, etc.).
 */
interface PromiseResolverInterface
{
    /**
     * Settle all promises and return their results.
     *
     * Waits for all promises to complete (fulfilled or rejected).
     * Results are keyed by the same keys as the input array.
     *
     * @param array<string, Promise> $promises Associative array of promises
     *
     * @return array<string, array{state: string, value?: mixed, reason?: \Throwable}>
     *         Each result contains 'state' ('fulfilled' or 'rejected'),
     *         'value' if fulfilled, or 'reason' if rejected
     */
    public function settleAll(array $promises): array;

    /**
     * Filter results to only include fulfilled promises.
     *
     * @param array<string, array{state: string, value?: mixed, reason?: \Throwable}> $results
     *
     * @return array<string, mixed> Fulfilled values keyed by original key
     */
    public function fulfilledOnly(array $results): array;

    /**
     * Wait for all promises and return only their values.
     *
     * Convenience method that combines settleAll() and fulfilledOnly().
     * Ignores rejected promises.
     *
     * @param array<string, Promise> $promises Associative array of promises
     *
     * @return array<string, mixed> Fulfilled values keyed by original key
     */
    public function waitAll(array $promises): array;
}
