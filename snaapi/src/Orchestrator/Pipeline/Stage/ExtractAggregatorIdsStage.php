<?php

declare(strict_types=1);

namespace App\Orchestrator\Pipeline\Stage;

use App\Orchestrator\Extractor\AggregatorIdExtractorInterface;
use App\Orchestrator\Pipeline\PipelineContext;
use App\Orchestrator\Pipeline\PipelineStageInterface;

/**
 * Pipeline stage that extracts aggregator IDs from the editorial.
 *
 * This is the second stage in the editorial processing pipeline.
 * It analyzes the editorial content (body, metadata, signatures, etc.)
 * and extracts all IDs needed for fetching related content:
 *
 * - Section ID
 * - Tag IDs
 * - Signature/Journalist IDs
 * - Multimedia IDs
 * - Inserted news IDs
 * - Recommended editorial IDs
 * - Body photo IDs
 * - Membership links
 *
 * This stage performs only CPU-bound operations (no I/O) and is very fast.
 * The extracted IDs are packaged into an AggregatorManifest for the next stage.
 *
 * @see PipelineStageInterface
 * @see AggregatorIdExtractorInterface
 * @see AggregatorManifest
 */
final class ExtractAggregatorIdsStage implements PipelineStageInterface
{
    /**
     * @param AggregatorIdExtractorInterface $extractor Extracts aggregator IDs from editorial
     */
    public function __construct(
        private readonly AggregatorIdExtractorInterface $extractor,
    ) {
    }

    /**
     * Execute the aggregator ID extraction stage.
     *
     * @param PipelineContext $context The current pipeline context (must have editorial)
     *
     * @return PipelineContext Updated context with aggregator manifest
     *
     * @throws \LogicException If editorial is not set in context
     * @throws \Exception If extraction fails
     */
    public function execute(PipelineContext $context): PipelineContext
    {
        $editorial = $context->getEditorial();
        $manifest = $this->extractor->extract($editorial);

        return $context->withManifest($manifest);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'ExtractAggregatorIds';
    }
}
