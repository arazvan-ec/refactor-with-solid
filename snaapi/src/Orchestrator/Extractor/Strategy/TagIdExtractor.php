<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Extractor\Strategy;

use App\Orchestrator\Extractor\IdExtractorStrategyInterface;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Tag\Domain\Model\Tag;

/**
 * Extracts all tag IDs from an editorial.
 *
 * Tags are used for categorization, SEO, and related content suggestions.
 * This extractor collects all tag IDs for parallel batch fetching.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class TagIdExtractor implements IdExtractorStrategyInterface
{
    /**
     * {@inheritDoc}
     *
     * @return array<int, string> Array of tag IDs
     */
    public function extract(Editorial $editorial): array
    {
        $tagIds = [];

        /** @var Tag $tag */
        foreach ($editorial->tags()->getArrayCopy() as $tag) {
            $tagIds[] = $tag->id();
        }

        return $tagIds;
    }

    /**
     * {@inheritDoc}
     */
    public function getManifestKey(): string
    {
        return 'tagIds';
    }
}
