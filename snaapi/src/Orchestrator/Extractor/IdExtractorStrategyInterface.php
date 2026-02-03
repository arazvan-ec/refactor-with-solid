<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Extractor;

use Ec\Editorial\Domain\Model\Editorial;

/**
 * Strategy interface for extracting specific types of IDs from an Editorial.
 *
 * Each implementation extracts one type of aggregator data (tags, signatures,
 * multimedia, etc.) following the Single Responsibility Principle.
 *
 * Used in the AggregatorIdExtractor composite to build the complete manifest.
 *
 * @author SNAAPI Refactoring Team
 */
interface IdExtractorStrategyInterface
{
    /**
     * Extract IDs or data from the editorial.
     *
     * The return type is mixed to accommodate different data types:
     * - string|null for single IDs (section)
     * - array<int, string> for multiple IDs (tags, signatures, etc.)
     *
     * @param Editorial $editorial The editorial to extract from
     *
     * @return mixed The extracted IDs or data
     */
    public function extract(Editorial $editorial): mixed;

    /**
     * Get the manifest property key for this extractor.
     *
     * This key determines where the extracted data will be stored in the
     * AggregatorManifest (e.g., 'tagIds', 'signatureIds', etc.).
     *
     * @return string The manifest property name
     */
    public function getManifestKey(): string;
}
