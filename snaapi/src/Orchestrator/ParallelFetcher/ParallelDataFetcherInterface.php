<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ParallelFetcher;

use App\Orchestrator\ValueObject\AggregatorManifest;
use App\Orchestrator\ValueObject\ResolvedAggregatorData;

/**
 * Interface for parallel data fetching system.
 *
 * This interface defines the contract for fetching all aggregated data
 * in parallel from multiple microservices based on an AggregatorManifest.
 *
 * The implementation orchestrates multiple HTTP requests concurrently
 * using promises, significantly reducing total response time compared
 * to sequential execution.
 *
 * Follows the Interface Segregation Principle (SOLID) by providing
 * a focused contract for parallel data fetching operations.
 */
interface ParallelDataFetcherInterface
{
    /**
     * Fetches all required data in parallel based on the manifest.
     *
     * This method orchestrates multiple HTTP calls concurrently:
     * - Level 2: Direct data (section, tags, journalists, multimedia, comments, membership)
     * - Level 3: Related editorials (inserted news, recommended) with their dependencies
     * - Level 3: Body photos
     *
     * All promises are resolved using Utils::settle() to handle partial failures
     * gracefully, ensuring that one failing request doesn't break the entire response.
     *
     * @param AggregatorManifest $manifest Contains all IDs to be resolved
     * @param string             $siteId   Site identifier for context-specific queries
     *
     * @return ResolvedAggregatorData Contains all resolved data from microservices
     *
     * @throws \Throwable When critical errors occur during fetching
     */
    public function fetchAll(AggregatorManifest $manifest, string $siteId): ResolvedAggregatorData;
}
