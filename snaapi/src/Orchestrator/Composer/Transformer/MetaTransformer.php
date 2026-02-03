<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

use App\Infrastructure\Service\MultimediaServiceInterface;
use App\Orchestrator\ValueObject\ResolvedAggregatorData;
use Ec\Editorial\Domain\Model\Editorial;

/**
 * Transforms editorial metadata into API response format.
 *
 * This transformer follows the Single Responsibility Principle (SRP)
 * by focusing solely on metadata transformation (SEO, social, images).
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class MetaTransformer implements MetaTransformerInterface
{
    public function __construct(
        private MultimediaServiceInterface $multimediaService,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function transform(
        Editorial $editorial,
        ResolvedAggregatorData $data,
        string $siteId
    ): array {
        $meta = [
            'description' => $editorial->lead() ?? '',
            'keywords' => $this->extractKeywords($data),
        ];

        // Add meta image if available
        if (null !== $data->metaImage) {
            $meta['image'] = $this->multimediaService->getShotsLandscape($data->metaImage);
        } elseif (null !== $data->openingMedia) {
            $meta['image'] = $this->multimediaService->getShotsLandscape($data->openingMedia);
        }

        return $meta;
    }

    /**
     * Extracts keywords from tags.
     *
     * @return array<int, string> Array of tag names
     */
    private function extractKeywords(ResolvedAggregatorData $data): array
    {
        $keywords = [];

        foreach ($data->tags as $tag) {
            if (method_exists($tag, 'name')) {
                $keywords[] = $tag->name();
            }
        }

        return $keywords;
    }
}
