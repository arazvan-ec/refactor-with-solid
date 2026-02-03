<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ParallelFetcher\Resolver;

use App\Infrastructure\Service\LegacyEditorialClientInterface;
use Http\Promise\Promise;

/**
 * Resolver for editorial comments.
 *
 * Fetches comment data asynchronously from the Legacy service.
 */
final readonly class CommentResolver implements ResolverInterface
{
    public function __construct(
        private LegacyEditorialClientInterface $legacyClient,
    ) {
    }

    /**
     * Resolves comment data for an editorial.
     *
     * @param mixed $editorialId The editorial ID to resolve comments for
     *
     * @return Promise Promise resolving to comments data
     */
    public function resolve(mixed $editorialId): Promise
    {
        if (!\is_string($editorialId)) {
            throw new \InvalidArgumentException('Editorial ID must be a string');
        }

        return $this->legacyClient->findCommentsByEditorialId($editorialId, async: true);
    }
}
