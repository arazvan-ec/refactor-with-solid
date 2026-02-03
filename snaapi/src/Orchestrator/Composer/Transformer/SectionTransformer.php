<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

use App\Infrastructure\Service\UrlGeneratorServiceInterface;
use Ec\Section\Domain\Model\Section;

/**
 * Transforms section entities into API response format.
 *
 * This transformer follows the Single Responsibility Principle (SRP)
 * by focusing solely on section data transformation.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class SectionTransformer implements SectionTransformerInterface
{
    public function __construct(
        private UrlGeneratorServiceInterface $urlGenerator,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function transform(Section $section, string $siteId): array
    {
        return [
            'id' => $section->id()->id(),
            'name' => $section->name(),
            'path' => $section->getPath(),
            'url' => $this->generateSectionUrl($section, $siteId),
            'color' => $section->color(),
        ];
    }

    /**
     * Generates the canonical URL for a section.
     */
    private function generateSectionUrl(Section $section, string $siteId): string
    {
        return $this->urlGenerator->generateUrl(
            'https://%s.%s.%s/%s',
            'www',
            $siteId,
            trim($section->getPath(), '/')
        );
    }
}
