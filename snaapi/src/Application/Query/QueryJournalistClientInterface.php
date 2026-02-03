<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\Query;

use Http\Promise\Promise;

/**
 * Interface for Journalist bounded context client.
 *
 * This interface defines the contract for fetching journalist data
 * from the Journalist microservice.
 */
interface QueryJournalistClientInterface
{
    /**
     * Finds a journalist by their alias identifier.
     *
     * @param string $aliasId  The journalist alias ID
     * @param bool   $async    Whether to execute asynchronously (returns Promise)
     * @param bool   $cached   Whether to use cached response if available
     * @param int    $ttlCache Time-to-live for cache in seconds (default: 60)
     *
     * @return array<string, mixed>|Promise Journalist data or Promise if async=true
     *
     * @throws \Throwable When the request fails
     */
    public function findJournalistByAliasId(
        string $aliasId,
        bool $async = false,
        bool $cached = false,
        int $ttlCache = 60,
    ): array|Promise;
}
