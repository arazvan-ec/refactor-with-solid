<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer;

use App\Orchestrator\Composer\Transformer\BodyTransformerInterface;
use App\Orchestrator\Composer\Transformer\InsertedNewsTransformerInterface;
use App\Orchestrator\Composer\Transformer\MetaTransformerInterface;
use App\Orchestrator\Composer\Transformer\MultimediaTransformerInterface;
use App\Orchestrator\Composer\Transformer\RecommendedTransformerInterface;
use App\Orchestrator\Composer\Transformer\SectionTransformerInterface;
use App\Orchestrator\Composer\Transformer\SignaturesTransformerInterface;
use App\Orchestrator\Composer\Transformer\TagsTransformerInterface;
use App\Orchestrator\Composer\Transformer\TitlesTransformerInterface;
use App\Orchestrator\Composer\Transformer\UrlTransformerInterface;
use App\Orchestrator\ValueObject\EditorialResponse;
use App\Orchestrator\ValueObject\ResolvedAggregatorData;
use Ec\Editorial\Domain\Model\Editorial;

/**
 * Fase 4 del Pipeline: Compone la respuesta final usando transformadores especializados.
 *
 * Este compositor orquesta la transformación de datos resueltos en una respuesta JSON lista.
 * Cada transformador tiene una única responsabilidad (SRP) y toda la lógica de transformación
 * está delegada a ellos.
 *
 * Implementa los principios SOLID:
 * - Single Responsibility: Solo compone, no transforma
 * - Open/Closed: Extensible agregando nuevos transformadores
 * - Liskov Substitution: Todos los transformadores son intercambiables
 * - Interface Segregation: Interfaces pequeñas y específicas
 * - Dependency Inversion: Depende de interfaces, no de implementaciones concretas
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class EditorialResponseComposer implements EditorialResponseComposerInterface
{
    public function __construct(
        private UrlTransformerInterface $urlTransformer,
        private TitlesTransformerInterface $titlesTransformer,
        private SectionTransformerInterface $sectionTransformer,
        private TagsTransformerInterface $tagsTransformer,
        private SignaturesTransformerInterface $signaturesTransformer,
        private MultimediaTransformerInterface $multimediaTransformer,
        private BodyTransformerInterface $bodyTransformer,
        private InsertedNewsTransformerInterface $insertedNewsTransformer,
        private RecommendedTransformerInterface $recommendedTransformer,
        private MetaTransformerInterface $metaTransformer,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * Compone la respuesta final delegando cada sección a su transformador especializado.
     * Cada transformador recibe exactamente los datos que necesita, siguiendo el principio
     * de menor conocimiento (Law of Demeter).
     */
    public function compose(
        Editorial $editorial,
        ResolvedAggregatorData $data,
        string $siteId
    ): EditorialResponse {
        return new EditorialResponse(
            id: $editorial->id()->id(),
            url: $this->urlTransformer->transform($editorial, $siteId),
            titles: $this->titlesTransformer->transform($editorial),
            section: null !== $data->section
                ? $this->sectionTransformer->transform($data->section, $siteId)
                : null,
            tags: $this->tagsTransformer->transform($data->tags, $siteId),
            signatures: $this->signaturesTransformer->transform($data->journalists, $siteId),
            openingMedia: null !== $data->openingMedia
                ? $this->multimediaTransformer->transform($data->openingMedia)
                : null,
            body: $this->bodyTransformer->transform(
                $editorial->body(),
                $data->bodyPhotos,
                $data->membershipUrls,
                $siteId
            ),
            insertedNews: $this->insertedNewsTransformer->transform($data->insertedNews, $siteId),
            recommended: $this->recommendedTransformer->transform($data->recommended, $siteId),
            countComments: $data->commentsCount,
            membership: !empty($data->membershipUrls) ? ['urls' => $data->membershipUrls] : null,
            meta: $this->metaTransformer->transform($editorial, $data, $siteId),
        );
    }
}
