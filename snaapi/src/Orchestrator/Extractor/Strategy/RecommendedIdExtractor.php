<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Extractor\Strategy;

use App\Orchestrator\Extractor\IdExtractorStrategyInterface;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;

/**
 * Extracts recommended editorial IDs from an editorial.
 *
 * Recommended editorials are related content suggestions shown at the end
 * of an editorial. Each requires fetching the full editorial data including
 * section, signatures, and multimedia.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class RecommendedIdExtractor implements IdExtractorStrategyInterface
{
    /**
     * {@inheritDoc}
     *
     * @return array<int, string> Array of recommended editorial IDs
     */
    public function extract(Editorial $editorial): array
    {
        $recommendedIds = [];

        $recommendedEditorials = $editorial->recommendedEditorials();

        /** @var EditorialId $recommendedEditorialId */
        foreach ($recommendedEditorials->editorialIds() as $recommendedEditorialId) {
            $recommendedIds[] = $recommendedEditorialId->id();
        }

        return $recommendedIds;
    }

    /**
     * {@inheritDoc}
     */
    public function getManifestKey(): string
    {
        return 'recommendedIds';
    }
}
