<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\Query;

use Http\Promise\Promise;

/**
 * Interface for Section bounded context client.
 *
 * This interface defines the contract for fetching section data
 * from the Section microservice.
 */
interface QuerySectionClientInterface
{
    /**
     * Finds a section by its unique identifier.
     *
     * @param string $sectionId The section ID
     * @param bool   $async     Whether to execute asynchronously (returns Promise)
     * @param bool   $cached    Whether to use cached response if available
     * @param int    $ttlCache  Time-to-live for cache in seconds (default: 60)
     *
     * @return array<string, mixed>|Promise Section data or Promise if async=true
     *
     * @throws \Throwable When the request fails
     */
    public function findSectionById(
        string $sectionId,
        bool $async = false,
        bool $cached = false,
        int $ttlCache = 60,
    ): array|Promise;
}
