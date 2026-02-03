<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ParallelFetcher\Resolver;

use Http\Promise\Promise;

/**
 * Base interface for single-item resolvers.
 *
 * Resolvers fetch data from external services asynchronously,
 * returning promises that can be resolved in parallel.
 *
 * This interface follows the Dependency Inversion Principle (SOLID),
 * allowing the ParallelDataFetcher to depend on abstractions rather
 * than concrete implementations.
 */
interface ResolverInterface
{
    /**
     * Resolves a single item asynchronously.
     *
     * @param mixed $input The identifier or data to resolve (e.g., ID, URL)
     *
     * @return Promise Promise that resolves to the fetched data
     *
     * @throws \Throwable When the resolution fails
     */
    public function resolve(mixed $input): Promise;
}
