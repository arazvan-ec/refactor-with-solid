<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Extractor\Strategy;

use App\Orchestrator\Extractor\IdExtractorStrategyInterface;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\Editorial;

/**
 * Extracts inserted news IDs from editorial body tags.
 *
 * Inserted news are editorial references embedded within the body content
 * (e.g., "Read also: ..."). Each requires fetching the full editorial data
 * including section, signatures, and multimedia.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class InsertedNewsIdExtractor implements IdExtractorStrategyInterface
{
    /**
     * {@inheritDoc}
     *
     * @return array<int, string> Array of inserted editorial IDs
     */
    public function extract(Editorial $editorial): array
    {
        $insertedNewsIds = [];

        /** @var BodyTagInsertedNews[] $insertedNews */
        $insertedNews = $editorial->body()->bodyElementsOf(BodyTagInsertedNews::class);

        foreach ($insertedNews as $insertedNew) {
            $insertedNewsIds[] = $insertedNew->editorialId()->id();
        }

        return $insertedNewsIds;
    }

    /**
     * {@inheritDoc}
     */
    public function getManifestKey(): string
    {
        return 'insertedNewsIds';
    }
}
