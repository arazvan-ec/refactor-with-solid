<?php

declare(strict_types=1);

namespace App\Orchestrator\Pipeline\Stage;

use App\Orchestrator\ParallelFetcher\ParallelDataFetcherInterface;
use App\Orchestrator\Pipeline\PipelineContext;
use App\Orchestrator\Pipeline\PipelineStageInterface;

/**
 * Pipeline stage that fetches all aggregator data in parallel.
 *
 * This is the third stage in the editorial processing pipeline and the most
 * performance-critical one. It takes the AggregatorManifest (containing all
 * extracted IDs) and fetches all related data in parallel using HTTP promises.
 *
 * Parallel fetch strategy:
 * - BATCH 1 (Level 2): Direct editorial dependencies
 *   - Section, Tags, Journalists, Multimedia, Opening, Comments, Membership
 * - BATCH 2 (Level 3): Inserted news with their dependencies
 * - BATCH 3 (Level 3): Recommended editorials with their dependencies
 * - BATCH 4: Body photos
 *
 * All batches are executed concurrently using Guzzle promises, providing
 * 40-60% improvement in response time compared to sequential fetching.
 *
 * @see PipelineStageInterface
 * @see ParallelDataFetcherInterface
 * @see ResolvedAggregatorData
 */
final class ParallelFetchStage implements PipelineStageInterface
{
    /**
     * @param ParallelDataFetcherInterface $fetcher Fetches aggregator data in parallel
     */
    public function __construct(
        private readonly ParallelDataFetcherInterface $fetcher,
    ) {
    }

    /**
     * Execute the parallel fetch stage.
     *
     * @param PipelineContext $context The current pipeline context (must have manifest)
     *
     * @return PipelineContext Updated context with resolved aggregator data
     *
     * @throws \LogicException If manifest is not set in context
     * @throws \Exception If any fetch operation fails
     */
    public function execute(PipelineContext $context): PipelineContext
    {
        $manifest = $context->getManifest();
        $siteId = $context->getSiteId();

        $resolvedData = $this->fetcher->fetchAll($manifest, $siteId);

        return $context->withResolvedData($resolvedData);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'ParallelFetch';
    }
}
