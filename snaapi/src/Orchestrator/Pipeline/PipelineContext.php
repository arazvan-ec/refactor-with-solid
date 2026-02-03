<?php

declare(strict_types=1);

namespace App\Orchestrator\Pipeline;

use App\Orchestrator\Extractor\AggregatorManifest;
use App\Orchestrator\ParallelFetcher\ResolvedAggregatorData;

/**
 * Immutable value object that carries data through the editorial pipeline.
 *
 * This context is passed between pipeline stages and is updated immutably
 * using "wither" methods. Each stage receives a context, performs its work,
 * and returns a new context with updated data.
 *
 * Pipeline flow:
 * 1. Initial context with editorialId + siteId
 * 2. FetchEditorialStage -> adds Editorial
 * 3. ExtractAggregatorIdsStage -> adds AggregatorManifest
 * 4. ParallelFetchStage -> adds ResolvedAggregatorData
 * 5. ComposeResponseStage -> adds EditorialResponse
 *
 * @see PipelineStageInterface
 * @see EditorialPipeline
 */
final class PipelineContext
{
    /**
     * @param string $editorialId The editorial ID being processed
     * @param string $siteId The site ID for the request
     * @param mixed|null $editorial The fetched editorial entity
     * @param AggregatorManifest|null $manifest The extracted aggregator IDs
     * @param ResolvedAggregatorData|null $resolvedData The fetched aggregator data
     * @param mixed|null $response The final editorial response
     */
    public function __construct(
        private readonly string $editorialId,
        private readonly string $siteId,
        private mixed $editorial = null,
        private ?AggregatorManifest $manifest = null,
        private ?ResolvedAggregatorData $resolvedData = null,
        private mixed $response = null,
    ) {
    }

    /**
     * Get the editorial ID being processed.
     */
    public function getEditorialId(): string
    {
        return $this->editorialId;
    }

    /**
     * Get the site ID for the request.
     */
    public function getSiteId(): string
    {
        return $this->siteId;
    }

    /**
     * Get the fetched editorial entity.
     *
     * @throws \LogicException If editorial has not been set yet
     */
    public function getEditorial(): mixed
    {
        if ($this->editorial === null) {
            throw new \LogicException('Editorial has not been set in the pipeline context yet');
        }

        return $this->editorial;
    }

    /**
     * Get the extracted aggregator manifest.
     *
     * @throws \LogicException If manifest has not been set yet
     */
    public function getManifest(): AggregatorManifest
    {
        if ($this->manifest === null) {
            throw new \LogicException('AggregatorManifest has not been set in the pipeline context yet');
        }

        return $this->manifest;
    }

    /**
     * Get the resolved aggregator data.
     *
     * @throws \LogicException If resolved data has not been set yet
     */
    public function getResolvedData(): ResolvedAggregatorData
    {
        if ($this->resolvedData === null) {
            throw new \LogicException('ResolvedAggregatorData has not been set in the pipeline context yet');
        }

        return $this->resolvedData;
    }

    /**
     * Get the final editorial response.
     *
     * @throws \LogicException If response has not been set yet
     */
    public function getResponse(): mixed
    {
        if ($this->response === null) {
            throw new \LogicException('EditorialResponse has not been set in the pipeline context yet');
        }

        return $this->response;
    }

    /**
     * Create a new context with the editorial entity.
     *
     * @param mixed $editorial The editorial entity
     *
     * @return self A new immutable context instance
     */
    public function withEditorial(mixed $editorial): self
    {
        return new self(
            $this->editorialId,
            $this->siteId,
            $editorial,
            $this->manifest,
            $this->resolvedData,
            $this->response,
        );
    }

    /**
     * Create a new context with the aggregator manifest.
     *
     * @param AggregatorManifest $manifest The aggregator manifest
     *
     * @return self A new immutable context instance
     */
    public function withManifest(AggregatorManifest $manifest): self
    {
        return new self(
            $this->editorialId,
            $this->siteId,
            $this->editorial,
            $manifest,
            $this->resolvedData,
            $this->response,
        );
    }

    /**
     * Create a new context with the resolved aggregator data.
     *
     * @param ResolvedAggregatorData $data The resolved data
     *
     * @return self A new immutable context instance
     */
    public function withResolvedData(ResolvedAggregatorData $data): self
    {
        return new self(
            $this->editorialId,
            $this->siteId,
            $this->editorial,
            $this->manifest,
            $data,
            $this->response,
        );
    }

    /**
     * Create a new context with the final editorial response.
     *
     * @param mixed $response The editorial response
     *
     * @return self A new immutable context instance
     */
    public function withResponse(mixed $response): self
    {
        return new self(
            $this->editorialId,
            $this->siteId,
            $this->editorial,
            $this->manifest,
            $this->resolvedData,
            $response,
        );
    }
}
