<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ParallelFetcher\Resolver;

use App\Application\Query\QueryJournalistClientInterface;
use Http\Promise\Promise;

/**
 * Batch resolver for Journalist bounded context.
 *
 * Fetches multiple journalists in parallel from the Journalist microservice.
 */
final readonly class JournalistBatchResolver implements BatchResolverInterface
{
    public function __construct(
        private QueryJournalistClientInterface $journalistClient,
    ) {
    }

    /**
     * Resolves multiple journalists in parallel.
     *
     * Creates individual promises for each journalist alias ID and aggregates
     * them into a single promise that resolves to an array of journalist data.
     *
     * @param array<int, string> $aliasIds Array of journalist alias IDs to resolve
     *
     * @return Promise Promise resolving to array of journalist data
     */
    public function resolveBatch(array $aliasIds): Promise
    {
        if ([] === $aliasIds) {
            return new \Http\Promise\FulfilledPromise([]);
        }

        $promises = [];
        foreach ($aliasIds as $aliasId) {
            $promises[] = $this->journalistClient->findJournalistByAliasId($aliasId, async: true);
        }

        return new \Http\Promise\FulfilledPromise($promises);
    }
}
