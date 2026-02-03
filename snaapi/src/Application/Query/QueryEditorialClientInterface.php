<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\Query;

use Http\Promise\Promise;

/**
 * Interface for Editorial bounded context client.
 *
 * This interface defines the contract for fetching editorial content
 * from the Editorial microservice.
 */
interface QueryEditorialClientInterface
{
    /**
     * Finds an editorial by its unique identifier.
     *
     * @param string $editorialId The editorial ID
     * @param bool   $async       Whether to execute asynchronously (returns Promise)
     * @param bool   $cached      Whether to use cached response if available
     * @param int    $ttlCache    Time-to-live for cache in seconds (default: 60)
     *
     * @return array<string, mixed>|Promise Editorial data or Promise if async=true
     *
     * @throws \Throwable When the request fails
     */
    public function findEditorialById(
        string $editorialId,
        bool $async = false,
        bool $cached = false,
        int $ttlCache = 60,
    ): array|Promise;
}
