<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Strategy;

use App\Infrastructure\Trait\UrlGeneratorTrait;
use Ec\Section\Domain\Model\Section;

/**
 * Transforms Section domain models into API response format.
 *
 * Responsible for:
 * - Converting Section objects to arrays
 * - Generating section URLs
 * - Encoding section names
 *
 * @author Claude AI <assistant@anthropic.com>
 */
final class SectionTransformer implements TransformerStrategyInterface
{
    use UrlGeneratorTrait;

    public function __construct(string $extension)
    {
        $this->setExtension($extension);
    }

    /**
     * Transforms a Section object into an array representation.
     *
     * Expected input data structure:
     * - 'section': Section - The section domain model to transform
     *
     * @param array<string, mixed> $data Must contain 'section' key with Section object
     *
     * @return array{
     *     id: string,
     *     name: string,
     *     url: string,
     *     encodeName: string
     * } The transformed section data
     */
    public function transform(array $data): array
    {
        if (!isset($data['section']) || !$data['section'] instanceof Section) {
            return [];
        }

        $section = $data['section'];

        $url = $this->generateUrl(
            'https://%s.%s.%s/%s',
            $section->isSubdomainBlog() ? 'blog' : 'www',
            $section->siteId(),
            $section->getPath()
        );

        return [
            'id' => $section->id()->id(),
            'name' => $section->name(),
            'url' => $url,
            'encodeName' => $section->encodeName(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $type): bool
    {
        return $type === 'section';
    }
}
