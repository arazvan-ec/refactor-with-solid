<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Strategy;

use App\Infrastructure\Trait\UrlGeneratorTrait;
use Ec\Encode\Encode;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;

/**
 * Transforms Tag domain models into API response format.
 *
 * Responsible for:
 * - Converting Tag objects to arrays
 * - Generating tag URLs with proper encoding
 * - Formatting tag metadata
 *
 * @author Claude AI <assistant@anthropic.com>
 */
final class TagTransformer implements TransformerStrategyInterface
{
    use UrlGeneratorTrait;

    public function __construct(string $extension)
    {
        $this->setExtension($extension);
    }

    /**
     * Transforms an array of Tag objects into array representations.
     *
     * Expected input data structure:
     * - 'tags': Tag[] - Array of tag domain models
     * - 'section': Section - Section for URL generation
     *
     * @param array<string, mixed> $data Must contain 'tags' and 'section' keys
     *
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     url: string
     * }> Array of transformed tag data
     */
    public function transform(array $data): array
    {
        if (!isset($data['tags']) || !is_array($data['tags'])) {
            return [];
        }

        if (!isset($data['section']) || !$data['section'] instanceof Section) {
            return [];
        }

        $section = $data['section'];
        $result = [];

        /** @var Tag $tag */
        foreach ($data['tags'] as $tag) {
            if (!$tag instanceof Tag) {
                continue;
            }

            $urlPath = sprintf(
                '/tags/%s/%s-%s',
                Encode::encodeUrl($tag->type()->name()),
                Encode::encodeUrl($tag->name()),
                $tag->id()->id(),
            );

            $result[] = [
                'id' => $tag->id()->id(),
                'name' => $tag->name(),
                'url' => $this->generateUrl(
                    'https://%s.%s.%s/%s',
                    'www',
                    $section->siteId(),
                    $urlPath,
                ),
            ];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $type): bool
    {
        return $type === 'tag';
    }
}
