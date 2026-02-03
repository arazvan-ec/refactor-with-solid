<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ParallelFetcher\Resolver;

use App\Application\Query\QueryMembershipClientInterface;
use Http\Promise\Promise;

/**
 * Resolver for membership URLs.
 *
 * Resolves membership links asynchronously from the Membership microservice.
 */
final readonly class MembershipResolver implements ResolverInterface
{
    public function __construct(
        private QueryMembershipClientInterface $membershipClient,
    ) {
    }

    /**
     * Resolves membership URLs from links.
     *
     * @param mixed $links Array of membership links to resolve
     *
     * @return Promise Promise resolving to membership URLs data
     */
    public function resolve(mixed $links): Promise
    {
        if (!\is_array($links)) {
            throw new \InvalidArgumentException('Membership links must be an array');
        }

        return $this->membershipClient->getMembershipUrl($links, async: true);
    }
}
