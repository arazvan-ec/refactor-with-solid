<?php

declare(strict_types=1);

namespace App\Orchestrator\Pipeline;

/**
 * Represents a single stage in the editorial processing pipeline.
 *
 * Each stage receives a PipelineContext, performs its specific operation,
 * and returns an updated immutable context.
 *
 * This interface enables the Pipeline Pattern for orchestrating
 * the editorial aggregation workflow in a modular and testable way.
 *
 * @see EditorialPipeline
 * @see PipelineContext
 */
interface PipelineStageInterface
{
    /**
     * Execute this pipeline stage with the given context.
     *
     * @param PipelineContext $context The current pipeline context
     *
     * @return PipelineContext A new immutable context with updated data
     *
     * @throws \Exception If the stage fails to execute
     */
    public function execute(PipelineContext $context): PipelineContext;

    /**
     * Get the human-readable name of this stage.
     *
     * Used for logging, debugging, and error reporting.
     *
     * @return string The stage name (e.g., "FetchEditorial", "ExtractAggregatorIds")
     */
    public function getName(): string;
}
