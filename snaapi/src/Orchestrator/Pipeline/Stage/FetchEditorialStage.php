<?php

declare(strict_types=1);

namespace App\Orchestrator\Pipeline\Stage;

use App\Orchestrator\Fetcher\QueryEditorialClientInterface;
use App\Orchestrator\Pipeline\PipelineContext;
use App\Orchestrator\Pipeline\PipelineStageInterface;

/**
 * Pipeline stage that fetches and validates the editorial entity.
 *
 * This is the first stage in the editorial processing pipeline.
 * It performs the following operations:
 * 1. Fetches the editorial by ID from the Editorial microservice
 * 2. Validates that the editorial is published and visible
 * 3. Adds the editorial to the pipeline context
 *
 * This stage is intentionally blocking - we must have a valid editorial
 * before proceeding to extract aggregator IDs and fetch related data.
 *
 * @see PipelineStageInterface
 * @see QueryEditorialClientInterface
 */
final class FetchEditorialStage implements PipelineStageInterface
{
    /**
     * @param QueryEditorialClientInterface $editorialClient Client for fetching editorials
     */
    public function __construct(
        private readonly QueryEditorialClientInterface $editorialClient,
    ) {
    }

    /**
     * Execute the editorial fetch stage.
     *
     * @param PipelineContext $context The current pipeline context
     *
     * @return PipelineContext Updated context with editorial entity
     *
     * @throws \App\Orchestrator\Exceptions\EditorialNotPublishedYetException If editorial is not visible
     * @throws \Exception If the editorial cannot be fetched
     */
    public function execute(PipelineContext $context): PipelineContext
    {
        $editorial = $this->editorialClient->findEditorialById($context->getEditorialId());

        if (!$editorial->isVisible()) {
            throw new \App\Orchestrator\Exceptions\EditorialNotPublishedYetException(
                sprintf('Editorial %s is not published yet', $context->getEditorialId())
            );
        }

        return $context->withEditorial($editorial);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'FetchEditorial';
    }
}
