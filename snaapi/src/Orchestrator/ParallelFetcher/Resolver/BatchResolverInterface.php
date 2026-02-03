<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ParallelFetcher\Resolver;

use Http\Promise\Promise;

/**
 * Interface for batch resolvers that handle multiple items in parallel.
 *
 * Batch resolvers optimize HTTP calls by fetching multiple items
 * simultaneously, reducing total request time.
 *
 * Implementations should create individual promises for each item
 * and use Utils::all() to aggregate them into a single promise
 * that resolves to an array of results.
 */
interface BatchResolverInterface
{
    /**
     * Resolves multiple items in parallel.
     *
     * @param array<int, string> $ids Array of identifiers to resolve
     *
     * @return Promise Promise that resolves to array of fetched items
     *                 The resolved array maintains the input order.
     *
     * @throws \Throwable When any resolution fails
     */
    public function resolveBatch(array $ids): Promise;
}
