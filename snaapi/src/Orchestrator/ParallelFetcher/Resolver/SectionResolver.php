<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ParallelFetcher\Resolver;

use App\Application\Query\QuerySectionClientInterface;
use Http\Promise\Promise;

/**
 * Resolver for Section bounded context.
 *
 * Fetches section data asynchronously from the Section microservice.
 */
final readonly class SectionResolver implements ResolverInterface
{
    public function __construct(
        private QuerySectionClientInterface $sectionClient,
    ) {
    }

    /**
     * Resolves section data by ID.
     *
     * @param mixed $sectionId The section ID to resolve
     *
     * @return Promise Promise resolving to section data
     */
    public function resolve(mixed $sectionId): Promise
    {
        if (!\is_string($sectionId)) {
            throw new \InvalidArgumentException('Section ID must be a string');
        }

        return $this->sectionClient->findSectionById($sectionId, async: true);
    }
}
