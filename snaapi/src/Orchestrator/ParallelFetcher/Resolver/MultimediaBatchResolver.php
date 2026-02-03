<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ParallelFetcher\Resolver;

use App\Application\Query\QueryMultimediaClientInterface;
use Http\Promise\Promise;

/**
 * Batch resolver for Multimedia bounded context.
 *
 * Fetches multiple multimedia items in parallel from the Multimedia microservice.
 */
final readonly class MultimediaBatchResolver implements BatchResolverInterface
{
    public function __construct(
        private QueryMultimediaClientInterface $multimediaClient,
    ) {
    }

    /**
     * Resolves multiple multimedia items in parallel.
     *
     * Creates individual promises for each multimedia ID and aggregates them
     * into a single promise that resolves to an array of multimedia data.
     *
     * @param array<int, string> $multimediaIds Array of multimedia IDs to resolve
     *
     * @return Promise Promise resolving to array of multimedia data
     */
    public function resolveBatch(array $multimediaIds): Promise
    {
        if ([] === $multimediaIds) {
            return new \Http\Promise\FulfilledPromise([]);
        }

        $promises = [];
        foreach ($multimediaIds as $multimediaId) {
            $promises[] = $this->multimediaClient->findMultimediaById($multimediaId, async: true);
        }

        return new \Http\Promise\FulfilledPromise($promises);
    }

    /**
     * Resolves multiple photo items in parallel.
     *
     * @param array<int, string> $photoIds Array of photo IDs to resolve
     *
     * @return Promise Promise resolving to array of photo data
     */
    public function resolvePhotoBatch(array $photoIds): Promise
    {
        if ([] === $photoIds) {
            return new \Http\Promise\FulfilledPromise([]);
        }

        $promises = [];
        foreach ($photoIds as $photoId) {
            $promises[] = $this->multimediaClient->findPhotoById($photoId, async: true);
        }

        return new \Http\Promise\FulfilledPromise($promises);
    }
}
