<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\Query;

use Http\Promise\Promise;

/**
 * Interface for Membership bounded context client.
 *
 * This interface defines the contract for resolving membership URLs
 * from the Membership microservice.
 */
interface QueryMembershipClientInterface
{
    /**
     * Resolves membership URLs from given links.
     *
     * @param array<int, string> $links Array of membership links to resolve
     * @param bool               $async Whether to execute asynchronously (returns Promise)
     *
     * @return array<string, mixed>|Promise Resolved membership data or Promise if async=true
     *
     * @throws \Throwable When the request fails
     */
    public function getMembershipUrl(
        array $links,
        bool $async = false,
    ): array|Promise;
}
