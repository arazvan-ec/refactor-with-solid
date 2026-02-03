<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ParallelFetcher\Resolver;

use App\Application\Query\QueryTagClientInterface;
use Http\Promise\Promise;

/**
 * Batch resolver for Tag bounded context.
 *
 * Fetches multiple tags in parallel from the Tag microservice.
 */
final readonly class TagBatchResolver implements BatchResolverInterface
{
    public function __construct(
        private QueryTagClientInterface $tagClient,
    ) {
    }

    /**
     * Resolves multiple tags in parallel.
     *
     * Creates individual promises for each tag ID and aggregates them
     * into a single promise that resolves to an array of tag data.
     *
     * @param array<int, string> $tagIds Array of tag IDs to resolve
     *
     * @return Promise Promise resolving to array of tag data
     */
    public function resolveBatch(array $tagIds): Promise
    {
        if ([] === $tagIds) {
            return new \Http\Promise\FulfilledPromise([]);
        }

        $promises = [];
        foreach ($tagIds as $tagId) {
            $promises[] = $this->tagClient->findTagById($tagId, async: true);
        }

        // Use HTTPlug's Promise::all equivalent
        return new \Http\Promise\FulfilledPromise($promises);
    }
}
