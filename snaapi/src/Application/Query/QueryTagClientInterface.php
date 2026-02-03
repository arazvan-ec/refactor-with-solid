<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\Query;

use Http\Promise\Promise;

/**
 * Interface for Tag bounded context client.
 *
 * This interface defines the contract for fetching tag data
 * from the Tag microservice.
 */
interface QueryTagClientInterface
{
    /**
     * Finds a tag by its unique identifier.
     *
     * @param string $tagId    The tag ID
     * @param bool   $async    Whether to execute asynchronously (returns Promise)
     * @param bool   $cached   Whether to use cached response if available
     * @param int    $ttlCache Time-to-live for cache in seconds (default: 60)
     *
     * @return array<string, mixed>|Promise Tag data or Promise if async=true
     *
     * @throws \Throwable When the request fails
     */
    public function findTagById(
        string $tagId,
        bool $async = false,
        bool $cached = false,
        int $ttlCache = 60,
    ): array|Promise;
}
