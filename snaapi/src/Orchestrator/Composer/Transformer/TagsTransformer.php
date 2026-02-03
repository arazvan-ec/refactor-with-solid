<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

use App\Infrastructure\Service\UrlGeneratorServiceInterface;
use Ec\Encode\Encode;
use Ec\Tag\Domain\Model\Tag;

/**
 * Transforms tag entities into API response format.
 *
 * This transformer follows the Single Responsibility Principle (SRP)
 * by focusing solely on tag data transformation.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class TagsTransformer implements TagsTransformerInterface
{
    public function __construct(
        private UrlGeneratorServiceInterface $urlGenerator,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function transform(array $tags, string $siteId): array
    {
        $transformed = [];

        foreach ($tags as $tag) {
            if (!$tag instanceof Tag) {
                continue;
            }

            $transformed[] = [
                'id' => $tag->id()->id(),
                'name' => $tag->name(),
                'url' => $this->generateTagUrl($tag, $siteId),
            ];
        }

        return $transformed;
    }

    /**
     * Generates the canonical URL for a tag.
     */
    private function generateTagUrl(Tag $tag, string $siteId): string
    {
        return $this->urlGenerator->generateUrl(
            'https://%s.%s.%s/tags/%s/',
            'www',
            $siteId,
            sprintf('%s-%s', Encode::encodeUrl($tag->name()), $tag->id()->id())
        );
    }
}
