<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Infrastructure\Port\PromiseResolverInterface;
use GuzzleHttp\Promise\Utils;
use Http\Promise\Promise;

/**
 * Promise resolver implementation using Guzzle Promises.
 *
 * Uses GuzzleHttp\Promise\Utils::settle() to resolve multiple promises in parallel.
 * Implements PromiseResolverInterface for dependency inversion.
 */
final readonly class GuzzlePromiseResolver implements PromiseResolverInterface
{
    /**
     * {@inheritDoc}
     */
    public function settleAll(array $promises): array
    {
        if ([] === $promises) {
            return [];
        }

        /** @var array<string, array{state: string, value?: mixed, reason?: \Throwable}> */
        return Utils::settle($promises)->wait();
    }

    /**
     * {@inheritDoc}
     */
    public function fulfilledOnly(array $results): array
    {
        $fulfilled = [];

        foreach ($results as $key => $result) {
            if (Promise::FULFILLED === $result['state']) {
                $fulfilled[$key] = $result['value'];
            }
        }

        return $fulfilled;
    }

    /**
     * {@inheritDoc}
     */
    public function waitAll(array $promises): array
    {
        $results = $this->settleAll($promises);

        return $this->fulfilledOnly($results);
    }
}
