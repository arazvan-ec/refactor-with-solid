<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Strategy;

use App\Application\DataTransformer\Apps\AppsDataTransformer;
use App\Infrastructure\Enum\ClossingModeEnum;
use App\Infrastructure\Enum\EditorialTypesEnum;
use App\Infrastructure\Service\UrlGeneratorServiceInterface;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Encode\Encode;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;

/**
 * Composite transformer that orchestrates multiple transformation strategies.
 *
 * This class follows the Composite and Strategy patterns to:
 * - Delegate specific transformations to specialized strategy classes
 * - Maintain backward compatibility with AppsDataTransformer interface
 * - Compose complex transformations from simple, focused strategies
 *
 * Responsibilities:
 * - Coordinate editorial data transformation
 * - Delegate to appropriate strategies based on data type
 * - Aggregate results from multiple strategies
 *
 * @author Claude AI <assistant@anthropic.com>
 */
final class CompositeDetailsTransformer implements AppsDataTransformer
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    private Editorial $editorial;
    private Section $section;

    /** @var Tag[] */
    private array $tags;

    /** @var array<TransformerStrategyInterface> */
    private array $strategies;

    /**
     * @param iterable<TransformerStrategyInterface> $strategies Collection of transformation strategies
     */
    public function __construct(
        private readonly UrlGeneratorServiceInterface $urlGenerator,
        private readonly SectionTransformer $sectionTransformer,
        private readonly TagTransformer $tagTransformer,
        private readonly HierarchyTransformer $hierarchyTransformer,
        private readonly TwitterFormatter $twitterFormatter,
    ) {
        // Register all strategies
        $this->strategies = [
            $this->sectionTransformer,
            $this->tagTransformer,
            $this->hierarchyTransformer,
            $this->twitterFormatter,
        ];
    }

    /**
     * Sets the data to be transformed.
     *
     * @param Tag[] $tags Array of tag domain models
     */
    public function write(
        Editorial $editorial,
        Section $section,
        array $tags,
    ): self {
        $this->editorial = $editorial;
        $this->section = $section;
        $this->tags = $tags;

        return $this;
    }

    /**
     * Reads and transforms all editorial data using registered strategies.
     *
     * @return array<string, mixed> Complete editorial data with all transformations applied
     */
    public function read(): array
    {
        $editorial = $this->transformEditorial();
        $editorial['section'] = $this->applyStrategy('section', [
            'section' => $this->section,
        ]);

        $editorial['tags'] = $this->applyStrategy('tag', [
            'tags' => $this->tags,
            'section' => $this->section,
        ]);

        $editorial['adsOptions'] = $this->applyStrategy('hierarchy', [
            'section' => $this->section,
        ]);

        $editorial['analiticsOptions'] = $this->applyStrategy('hierarchy', [
            'section' => $this->section,
        ]);

        // Optional: Include Twitter metadata if needed
        // $editorial['twitter'] = $this->applyStrategy('twitter', [
        //     'editorial' => $this->editorial,
        //     'url' => $editorial['url'],
        // ]);

        return $editorial;
    }

    /**
     * Applies a specific transformation strategy.
     *
     * @param string $type The type of transformation needed
     * @param array<string, mixed> $data The data to transform
     *
     * @return array<string, mixed> The transformed data
     */
    private function applyStrategy(string $type, array $data): array
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($type)) {
                return $strategy->transform($data);
            }
        }

        return [];
    }

    /**
     * Transforms the core editorial data.
     *
     * This method handles the editorial-specific transformation that doesn't
     * fit into the strategy pattern (core editorial fields).
     *
     * @return array<string, array<string, mixed>|bool|int|string> Editorial data
     */
    private function transformEditorial(): array
    {
        $editorialType = EditorialTypesEnum::getNameById($this->editorial->editorialType());

        return [
            'id' => $this->editorial->id()->id(),
            'url' => $this->buildEditorialUrl(),
            'titles' => [
                'title' => $this->editorial->editorialTitles()->title(),
                'preTitle' => $this->editorial->editorialTitles()->preTitle(),
                'urlTitle' => $this->editorial->editorialTitles()->urlTitle(),
                'mobileTitle' => $this->editorial->editorialTitles()->mobileTitle(),
            ],
            'lead' => $this->editorial->lead(),
            'publicationDate' => $this->editorial->publicationDate()->format(self::DATE_FORMAT),
            'updatedOn' => $this->editorial->publicationDate()->format(self::DATE_FORMAT),
            'endOn' => $this->editorial->endOn()->format(self::DATE_FORMAT),
            'type' => [
                'id' => $editorialType['id'],
                'name' => $editorialType['name'],
            ],
            'indexable' => $this->editorial->indexed(),
            'deleted' => $this->editorial->isDeleted(),
            'published' => $this->editorial->isPublished(),
            'closingModeId' => ClossingModeEnum::getClosingModeById($this->editorial->closingModeId()),
            'commentable' => $this->editorial->canComment(),
            'isBrand' => $this->editorial->isBrand(),
            'isAmazonOnsite' => $this->editorial->isAmazonOnsite(),
            'contentType' => $this->editorial->contentType(),
            'canonicalEditorialId' => $this->editorial->canonicalEditorialId(),
            'urlDate' => $this->editorial->urlDate()->format(self::DATE_FORMAT),
            'countWords' => $this->editorial->body()->countWords(),
        ];
    }

    /**
     * Builds the complete editorial URL.
     *
     * @return string The formatted editorial URL
     */
    private function buildEditorialUrl(): string
    {
        $editorialPath = sprintf(
            '%s/%s/%s_%s',
            $this->section->getPath(),
            $this->editorial->publicationDate()->format('Y-m-d'),
            Encode::encodeUrl($this->editorial->editorialTitles()->urlTitle()),
            $this->editorial->id()->id()
        );

        return $this->urlGenerator->generateUrl(
            'https://%s.%s.%s/%s',
            $this->section->isSubdomainBlog() ? 'blog' : 'www',
            $this->section->siteId(),
            $editorialPath
        );
    }
}
