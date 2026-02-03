<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Extractor\Strategy;

use App\Orchestrator\Extractor\IdExtractorStrategyInterface;
use Ec\Editorial\Domain\Model\Editorial;

/**
 * Extracts the section ID from an editorial.
 *
 * The section ID is required for fetching section data (name, URL, hierarchy, etc.)
 * which is used throughout the editorial response.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class SectionIdExtractor implements IdExtractorStrategyInterface
{
    /**
     * {@inheritDoc}
     *
     * @return string|null The section ID, or null if not set
     */
    public function extract(Editorial $editorial): ?string
    {
        return $editorial->sectionId();
    }

    /**
     * {@inheritDoc}
     */
    public function getManifestKey(): string
    {
        return 'sectionId';
    }
}
