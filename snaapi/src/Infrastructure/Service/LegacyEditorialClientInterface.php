<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Infrastructure\Service;

use Http\Promise\Promise;

/**
 * Interface for Legacy Editorial Client.
 *
 * This interface defines the contract for fetching editorial content and comments
 * from the legacy editorial service. It follows the Dependency Inversion Principle (SOLID)
 * by depending on abstractions rather than concrete implementations.
 *
 * Implementations can use different HTTP clients, caching strategies, or even
 * mock responses for testing purposes.
 *
 * @author Claude Code Assistant
 */
interface LegacyEditorialClientInterface
{
    /**
     * Finds an editorial by its unique identifier.
     *
     * This method retrieves editorial content from the legacy service, including
     * all metadata, body elements, multimedia, authors, and related content.
     *
     * @param string $editorialIdString The editorial ID as string
     * @param bool   $async             Whether to execute the request asynchronously (returns Promise)
     * @param bool   $cached            Whether to use cached response if available
     * @param int    $ttlCache          Time-to-live for cache in seconds (default: 60)
     *
     * @return array<string, mixed>|Promise Editorial data structure or Promise if async=true
     *                                      Returns array with editorial fields: id, title, subtitle,
     *                                      body, authors, multimedia, metadata, etc.
     *
     * @throws \Throwable When the request fails or response cannot be parsed
     */
    public function findEditorialById(
        string $editorialIdString,
        bool $async = false,
        bool $cached = false,
        int $ttlCache = 60,
    ): array|Promise;

    /**
     * Finds comments for a given editorial by its ID.
     *
     * This method retrieves all user comments associated with an editorial
     * from the legacy community service.
     *
     * @param string $editorialIdString The editorial ID as string
     * @param bool   $async             Whether to execute the request asynchronously (returns Promise)
     * @param bool   $cached            Whether to use cached response if available
     * @param int    $ttlCache          Time-to-live for cache in seconds (default: 60)
     *
     * @return array<string, mixed>|Promise Comments data structure or Promise if async=true
     *                                      Returns array with comments list and metadata:
     *                                      total_comments, comments[], pagination, etc.
     *
     * @throws \Throwable When the request fails or response cannot be parsed
     */
    public function findCommentsByEditorialId(
        string $editorialIdString,
        bool $async = false,
        bool $cached = false,
        int $ttlCache = 60,
    ): array|Promise;
}
