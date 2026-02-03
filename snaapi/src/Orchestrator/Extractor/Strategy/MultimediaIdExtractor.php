<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Extractor\Strategy;

use App\Orchestrator\Extractor\IdExtractorStrategyInterface;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\NewsBase;

/**
 * Extracts multimedia IDs from an editorial.
 *
 * This includes:
 * - Main editorial multimedia (photo, video, or widget)
 * - Opening multimedia
 * - Meta image (fallback when no multimedia exists)
 *
 * All IDs are collected for parallel multimedia fetching.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class MultimediaIdExtractor implements IdExtractorStrategyInterface
{
    /**
     * {@inheritDoc}
     *
     * @return array<int, string> Array of multimedia IDs (may include duplicates)
     */
    public function extract(Editorial $editorial): array
    {
        $multimediaIds = [];

        // Extract main multimedia ID
        $mainMultimediaId = $editorial->multimedia()->id()->id();
        if (!empty($mainMultimediaId)) {
            $multimediaIds[] = $mainMultimediaId;
        }

        // Extract opening multimedia ID
        /** @var NewsBase $editorial */
        $openingMultimediaId = $editorial->opening()->multimediaId();
        if (!empty($openingMultimediaId)) {
            $multimediaIds[] = $openingMultimediaId;
        }

        // Extract meta image as fallback
        $metaImageId = $editorial->metaImage();
        if (!empty($metaImageId)) {
            $multimediaIds[] = $metaImageId;
        }

        // Remove duplicates and re-index
        return array_values(array_unique($multimediaIds));
    }

    /**
     * {@inheritDoc}
     */
    public function getManifestKey(): string
    {
        return 'multimediaIds';
    }
}
