<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Extractor;

use Ec\Editorial\Domain\Model\Editorial;

/**
 * Interface for extracting all aggregator IDs from an Editorial.
 *
 * This is the main contract for Phase 2 of the editorial pipeline.
 * It coordinates multiple extraction strategies to build a complete
 * manifest of all IDs needed for parallel data fetching.
 *
 * @author SNAAPI Refactoring Team
 */
interface AggregatorIdExtractorInterface
{
    /**
     * Extract all aggregator IDs from the editorial.
     *
     * This method coordinates multiple extraction strategies to build
     * a complete AggregatorManifest containing all IDs needed to fetch
     * related data from external services.
     *
     * @param Editorial $editorial The editorial to extract IDs from
     *
     * @return AggregatorManifest Complete manifest with all extracted IDs
     */
    public function extract(Editorial $editorial): AggregatorManifest;
}
