<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\Query;

use Http\Promise\Promise;

/**
 * Interface for Multimedia bounded context client.
 *
 * This interface defines the contract for fetching multimedia data
 * from the Multimedia microservice.
 */
interface QueryMultimediaClientInterface
{
    /**
     * Finds multimedia by its unique identifier.
     *
     * @param string $multimediaId The multimedia ID
     * @param bool   $async        Whether to execute asynchronously (returns Promise)
     * @param bool   $cached       Whether to use cached response if available
     * @param int    $ttlCache     Time-to-live for cache in seconds (default: 60)
     *
     * @return array<string, mixed>|Promise Multimedia data or Promise if async=true
     *
     * @throws \Throwable When the request fails
     */
    public function findMultimediaById(
        string $multimediaId,
        bool $async = false,
        bool $cached = false,
        int $ttlCache = 60,
    ): array|Promise;

    /**
     * Finds a photo by its unique identifier.
     *
     * @param string $photoId  The photo ID
     * @param bool   $async    Whether to execute asynchronously (returns Promise)
     * @param bool   $cached   Whether to use cached response if available
     * @param int    $ttlCache Time-to-live for cache in seconds (default: 60)
     *
     * @return array<string, mixed>|Promise Photo data or Promise if async=true
     *
     * @throws \Throwable When the request fails
     */
    public function findPhotoById(
        string $photoId,
        bool $async = false,
        bool $cached = false,
        int $ttlCache = 60,
    ): array|Promise;
}
