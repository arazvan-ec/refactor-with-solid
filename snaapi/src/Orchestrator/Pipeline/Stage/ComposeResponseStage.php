<?php

declare(strict_types=1);

namespace App\Orchestrator\Pipeline\Stage;

use App\Orchestrator\Composer\EditorialResponseComposerInterface;
use App\Orchestrator\Pipeline\PipelineContext;
use App\Orchestrator\Pipeline\PipelineStageInterface;

/**
 * Pipeline stage that composes the final editorial response.
 *
 * This is the fourth and final stage in the editorial processing pipeline.
 * It takes the editorial entity and all resolved aggregator data, and
 * transforms them into the final API response format.
 *
 * The composer delegates to specialized transformers:
 * - SectionTransformer - Transforms section data
 * - TagsTransformer - Transforms tags data
 * - SignaturesTransformer - Transforms journalist signatures
 * - MultimediaTransformer - Transforms multimedia content
 * - BodyTransformer - Transforms body elements (paragraphs, photos, etc.)
 * - InsertedNewsTransformer - Transforms inserted news blocks
 * - RecommendedTransformer - Transforms recommended editorials
 *
 * Each transformer follows SRP and can be tested independently.
 *
 * @see PipelineStageInterface
 * @see EditorialResponseComposerInterface
 * @see EditorialResponse
 */
final class ComposeResponseStage implements PipelineStageInterface
{
    /**
     * @param EditorialResponseComposerInterface $composer Composes the final response
     */
    public function __construct(
        private readonly EditorialResponseComposerInterface $composer,
    ) {
    }

    /**
     * Execute the response composition stage.
     *
     * @param PipelineContext $context The current pipeline context (must have editorial and resolved data)
     *
     * @return PipelineContext Updated context with final editorial response
     *
     * @throws \LogicException If editorial or resolved data is not set in context
     * @throws \Exception If composition fails
     */
    public function execute(PipelineContext $context): PipelineContext
    {
        $editorial = $context->getEditorial();
        $resolvedData = $context->getResolvedData();

        // Extract siteId from resolved Section (fallback to context siteId if no section)
        $siteId = $context->getSiteId();
        if (null !== $resolvedData->section && method_exists($resolvedData->section, 'siteId')) {
            $siteId = $resolvedData->section->siteId();
        }

        $response = $this->composer->compose($editorial, $resolvedData, $siteId);

        return $context->withResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'ComposeResponse';
    }
}
