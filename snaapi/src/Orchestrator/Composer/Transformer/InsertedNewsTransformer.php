<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

use App\Infrastructure\Service\MultimediaServiceInterface;
use App\Infrastructure\Service\UrlGeneratorServiceInterface;
use App\Orchestrator\ValueObject\EditorialAggregate;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Encode\Encode;
use Ec\Section\Domain\Model\Section;

/**
 * Transforms inserted news aggregates into API response format.
 *
 * This transformer follows the Single Responsibility Principle (SRP)
 * by focusing solely on inserted news transformation.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class InsertedNewsTransformer implements InsertedNewsTransformerInterface
{
    private const TYPE = 'insertednews';

    public function __construct(
        private UrlGeneratorServiceInterface $urlGenerator,
        private MultimediaServiceInterface $multimediaService,
        private SignaturesTransformerInterface $signaturesTransformer,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function transform(array $insertedNews, string $siteId): array
    {
        $transformed = [];

        foreach ($insertedNews as $aggregate) {
            if (!$aggregate instanceof EditorialAggregate) {
                continue;
            }

            $editorial = $aggregate->editorial;
            if (!$editorial instanceof Editorial) {
                continue;
            }

            $transformed[] = [
                'type' => self::TYPE,
                'editorialId' => $editorial->id()->id(),
                'title' => $editorial->editorialTitles()->title(),
                'url' => $this->generateEditorialUrl($editorial, $aggregate->section, $siteId),
                'signatures' => $this->signaturesTransformer->transform($aggregate->journalists, $siteId),
                'photo' => $this->getPhotoUrl($aggregate),
                'shots' => $this->getShots($aggregate),
            ];
        }

        return $transformed;
    }

    /**
     * Generates the canonical URL for an editorial.
     */
    private function generateEditorialUrl(Editorial $editorial, ?object $section, string $siteId): string
    {
        if (!$section instanceof Section) {
            return '';
        }

        $editorialPath = sprintf(
            '%s/%s/%s_%s',
            $section->getPath(),
            $editorial->publicationDate()->format('Y-m-d'),
            Encode::encodeUrl($editorial->editorialTitles()->urlTitle()),
            $editorial->id()->id()
        );

        return $this->urlGenerator->generateUrl(
            'https://%s.%s.%s/%s',
            $section->isSubdomainBlog() ? 'blog' : 'www',
            $siteId,
            $editorialPath
        );
    }

    /**
     * Gets the first photo URL from shots.
     *
     * @return string The first photo URL or empty string
     */
    private function getPhotoUrl(EditorialAggregate $aggregate): string
    {
        $shots = $this->getShots($aggregate);

        return empty($shots) ? '' : reset($shots);
    }

    /**
     * Gets all photo shots from multimedia.
     *
     * @return array<string, string> Array of photo URLs
     */
    private function getShots(EditorialAggregate $aggregate): array
    {
        if (null === $aggregate->multimedia) {
            return [];
        }

        return $this->multimediaService->getShotsLandscape($aggregate->multimedia);
    }
}
