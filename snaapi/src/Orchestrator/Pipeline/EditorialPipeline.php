<?php

declare(strict_types=1);

namespace App\Orchestrator\Pipeline;

/**
 * Orchestrates the editorial processing through a series of pipeline stages.
 *
 * The pipeline pattern allows breaking down the complex editorial aggregation
 * process into discrete, testable stages that execute sequentially.
 *
 * Pipeline stages:
 * 1. FetchEditorialStage - Fetches and validates the editorial
 * 2. ExtractAggregatorIdsStage - Extracts IDs from editorial content
 * 3. ParallelFetchStage - Fetches all aggregator data in parallel
 * 4. ComposeResponseStage - Composes the final response
 *
 * Each stage receives an immutable PipelineContext and returns an updated context.
 *
 * Example usage:
 * <code>
 * $pipeline = new EditorialPipeline([
 *     new FetchEditorialStage($editorialClient),
 *     new ExtractAggregatorIdsStage($extractor),
 *     new ParallelFetchStage($fetcher),
 *     new ComposeResponseStage($composer),
 * ]);
 *
 * $response = $pipeline->process('editorial-123', 'site-1');
 * </code>
 *
 * @see PipelineStageInterface
 * @see PipelineContext
 */
final class EditorialPipeline
{
    /**
     * @param PipelineStageInterface[] $stages The ordered list of pipeline stages
     */
    public function __construct(
        private readonly array $stages,
    ) {
        $this->validateStages();
    }

    /**
     * Process an editorial through the entire pipeline.
     *
     * @param string $editorialId The editorial ID to process
     * @param string $siteId The site ID for the request
     *
     * @return mixed The final editorial response
     *
     * @throws \Exception If any stage fails during processing
     */
    public function process(string $editorialId, string $siteId): mixed
    {
        $context = new PipelineContext($editorialId, $siteId);

        foreach ($this->stages as $stage) {
            $context = $stage->execute($context);
        }

        return $context->getResponse();
    }

    /**
     * Validate that all stages implement the correct interface.
     *
     * @throws \InvalidArgumentException If any stage is invalid
     */
    private function validateStages(): void
    {
        if (empty($this->stages)) {
            throw new \InvalidArgumentException('Pipeline must have at least one stage');
        }

        foreach ($this->stages as $index => $stage) {
            if (!$stage instanceof PipelineStageInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Stage at index %d must implement %s, got %s',
                        $index,
                        PipelineStageInterface::class,
                        get_debug_type($stage)
                    )
                );
            }
        }
    }
}
