<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Extractor;

/**
 * Value Object representing all aggregator IDs extracted from an Editorial.
 *
 * This manifest contains all IDs needed to fetch related data from external services:
 * - Section ID
 * - Tag IDs
 * - Signature (journalist) IDs
 * - Multimedia IDs
 * - Inserted news IDs
 * - Recommended editorial IDs
 * - Body photo IDs
 * - Membership card URLs
 *
 * Used in Phase 2 of the editorial pipeline (ID extraction) before parallel fetching.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class AggregatorManifest
{
    /**
     * @param string $editorialId The main editorial ID
     * @param string|null $sectionId The section ID (nullable for edge cases)
     * @param array<int, string> $tagIds Array of tag IDs
     * @param array<int, string> $signatureIds Array of journalist alias IDs
     * @param array<int, string> $multimediaIds Array of multimedia IDs (including metaImage)
     * @param array<int, string> $insertedNewsIds Array of inserted editorial IDs
     * @param array<int, string> $recommendedIds Array of recommended editorial IDs
     * @param array<int, string> $bodyPhotoIds Array of photo IDs from body tags
     * @param array<int, string> $membershipLinks Array of membership card URLs
     */
    public function __construct(
        public string $editorialId,
        public ?string $sectionId = null,
        public array $tagIds = [],
        public array $signatureIds = [],
        public array $multimediaIds = [],
        public array $insertedNewsIds = [],
        public array $recommendedIds = [],
        public array $bodyPhotoIds = [],
        public array $membershipLinks = [],
    ) {
    }
}
