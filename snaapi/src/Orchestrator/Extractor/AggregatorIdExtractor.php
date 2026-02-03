<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Extractor;

use Ec\Editorial\Domain\Model\Editorial;

/**
 * Composite extractor that coordinates multiple ID extraction strategies.
 *
 * This class implements the Composite pattern to delegate ID extraction
 * to specialized strategies, each responsible for one type of data.
 *
 * Phase 2 of the editorial pipeline: Extract all aggregator IDs in a
 * single pass before parallel HTTP fetching.
 *
 * Benefits:
 * - Single Responsibility: Each strategy extracts one type of ID
 * - Open/Closed: Add new extractors without modifying this class
 * - Testability: Each strategy can be tested independently
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class AggregatorIdExtractor implements AggregatorIdExtractorInterface
{
    /**
     * @param iterable<IdExtractorStrategyInterface> $strategies Collection of extraction strategies
     */
    public function __construct(
        private iterable $strategies,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function extract(Editorial $editorial): AggregatorManifest
    {
        // Start with the editorial ID
        $data = [
            'editorialId' => $editorial->id()->id(),
        ];

        // Apply each strategy to extract its specific data
        foreach ($this->strategies as $strategy) {
            $key = $strategy->getManifestKey();
            $data[$key] = $strategy->extract($editorial);
        }

        // Build and return the complete manifest
        return new AggregatorManifest(
            editorialId: $data['editorialId'],
            sectionId: $data['sectionId'] ?? null,
            tagIds: $data['tagIds'] ?? [],
            signatureIds: $data['signatureIds'] ?? [],
            multimediaIds: $data['multimediaIds'] ?? [],
            insertedNewsIds: $data['insertedNewsIds'] ?? [],
            recommendedIds: $data['recommendedIds'] ?? [],
            bodyPhotoIds: $data['bodyPhotoIds'] ?? [],
            membershipLinks: $data['membershipLinks'] ?? [],
        );
    }
}
