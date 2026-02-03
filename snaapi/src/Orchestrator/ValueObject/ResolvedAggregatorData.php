<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ValueObject;

/**
 * Value Object con todos los datos resueltos de las peticiones HTTP paralelas.
 *
 * Este objeto inmutable contiene los resultados de todas las peticiones
 * HTTP realizadas en paralelo para obtener los datos agregados de un editorial.
 * Todos los IDs del AggregatorManifest han sido resueltos a sus entidades correspondientes.
 *
 * Soporta construcción incremental mediante métodos "with" que retornan
 * nuevas instancias (patrón immutable builder).
 */
final readonly class ResolvedAggregatorData
{
    /**
     * @param object|null $section Sección resuelta (objeto Section)
     * @param array<int, object> $tags Array de tags resueltos
     * @param array<int, object> $journalists Array de periodistas resueltos
     * @param array<int, object> $multimedia Array de objetos multimedia resueltos
     * @param object|null $openingMedia Multimedia de apertura resuelto
     * @param object|null $metaImage Imagen meta resuelta
     * @param array<int, EditorialAggregate> $insertedNews Array de agregados de noticias insertadas
     * @param array<int, EditorialAggregate> $recommended Array de agregados de editoriales recomendados
     * @param array<int, object> $bodyPhotos Array de fotos del body resueltas
     * @param array<int, string> $membershipUrls Array de URLs de membership resueltas
     * @param int $commentsCount Contador de comentarios
     */
    public function __construct(
        public ?object $section = null,
        public array $tags = [],
        public array $journalists = [],
        public array $multimedia = [],
        public ?object $openingMedia = null,
        public ?object $metaImage = null,
        public array $insertedNews = [],
        public array $recommended = [],
        public array $bodyPhotos = [],
        public array $membershipUrls = [],
        public int $commentsCount = 0,
    ) {
    }

    /**
     * Crea una instancia vacía de ResolvedAggregatorData.
     *
     * Útil como punto de partida para construcción incremental
     * o cuando no hay datos agregados disponibles.
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * Retorna una nueva instancia con la sección actualizada.
     *
     * @param object $section Objeto Section a agregar
     */
    public function withSection(object $section): self
    {
        return new self(
            section: $section,
            tags: $this->tags,
            journalists: $this->journalists,
            multimedia: $this->multimedia,
            openingMedia: $this->openingMedia,
            metaImage: $this->metaImage,
            insertedNews: $this->insertedNews,
            recommended: $this->recommended,
            bodyPhotos: $this->bodyPhotos,
            membershipUrls: $this->membershipUrls,
            commentsCount: $this->commentsCount,
        );
    }

    /**
     * Retorna una nueva instancia con los tags actualizados.
     *
     * @param array<int, object> $tags Array de objetos Tag
     */
    public function withTags(array $tags): self
    {
        return new self(
            section: $this->section,
            tags: $tags,
            journalists: $this->journalists,
            multimedia: $this->multimedia,
            openingMedia: $this->openingMedia,
            metaImage: $this->metaImage,
            insertedNews: $this->insertedNews,
            recommended: $this->recommended,
            bodyPhotos: $this->bodyPhotos,
            membershipUrls: $this->membershipUrls,
            commentsCount: $this->commentsCount,
        );
    }

    /**
     * Retorna una nueva instancia con los periodistas actualizados.
     *
     * @param array<int, object> $journalists Array de objetos Journalist
     */
    public function withJournalists(array $journalists): self
    {
        return new self(
            section: $this->section,
            tags: $this->tags,
            journalists: $journalists,
            multimedia: $this->multimedia,
            openingMedia: $this->openingMedia,
            metaImage: $this->metaImage,
            insertedNews: $this->insertedNews,
            recommended: $this->recommended,
            bodyPhotos: $this->bodyPhotos,
            membershipUrls: $this->membershipUrls,
            commentsCount: $this->commentsCount,
        );
    }

    /**
     * Retorna una nueva instancia con el multimedia actualizado.
     *
     * @param array<int, object> $multimedia Array de objetos Multimedia
     */
    public function withMultimedia(array $multimedia): self
    {
        return new self(
            section: $this->section,
            tags: $this->tags,
            journalists: $this->journalists,
            multimedia: $multimedia,
            openingMedia: $this->openingMedia,
            metaImage: $this->metaImage,
            insertedNews: $this->insertedNews,
            recommended: $this->recommended,
            bodyPhotos: $this->bodyPhotos,
            membershipUrls: $this->membershipUrls,
            commentsCount: $this->commentsCount,
        );
    }

    /**
     * Retorna una nueva instancia con el opening media actualizado.
     *
     * @param object $openingMedia Objeto Multimedia de apertura
     */
    public function withOpeningMedia(object $openingMedia): self
    {
        return new self(
            section: $this->section,
            tags: $this->tags,
            journalists: $this->journalists,
            multimedia: $this->multimedia,
            openingMedia: $openingMedia,
            metaImage: $this->metaImage,
            insertedNews: $this->insertedNews,
            recommended: $this->recommended,
            bodyPhotos: $this->bodyPhotos,
            membershipUrls: $this->membershipUrls,
            commentsCount: $this->commentsCount,
        );
    }

    /**
     * Retorna una nueva instancia con la meta image actualizada.
     *
     * @param object $metaImage Objeto Multimedia de meta image
     */
    public function withMetaImage(object $metaImage): self
    {
        return new self(
            section: $this->section,
            tags: $this->tags,
            journalists: $this->journalists,
            multimedia: $this->multimedia,
            openingMedia: $this->openingMedia,
            metaImage: $metaImage,
            insertedNews: $this->insertedNews,
            recommended: $this->recommended,
            bodyPhotos: $this->bodyPhotos,
            membershipUrls: $this->membershipUrls,
            commentsCount: $this->commentsCount,
        );
    }

    /**
     * Retorna una nueva instancia con las noticias insertadas actualizadas.
     *
     * @param array<int, EditorialAggregate> $insertedNews Array de agregados de noticias
     */
    public function withInsertedNews(array $insertedNews): self
    {
        return new self(
            section: $this->section,
            tags: $this->tags,
            journalists: $this->journalists,
            multimedia: $this->multimedia,
            openingMedia: $this->openingMedia,
            metaImage: $this->metaImage,
            insertedNews: $insertedNews,
            recommended: $this->recommended,
            bodyPhotos: $this->bodyPhotos,
            membershipUrls: $this->membershipUrls,
            commentsCount: $this->commentsCount,
        );
    }

    /**
     * Retorna una nueva instancia con los editoriales recomendados actualizados.
     *
     * @param array<int, EditorialAggregate> $recommended Array de agregados recomendados
     */
    public function withRecommended(array $recommended): self
    {
        return new self(
            section: $this->section,
            tags: $this->tags,
            journalists: $this->journalists,
            multimedia: $this->multimedia,
            openingMedia: $this->openingMedia,
            metaImage: $this->metaImage,
            insertedNews: $this->insertedNews,
            recommended: $recommended,
            bodyPhotos: $this->bodyPhotos,
            membershipUrls: $this->membershipUrls,
            commentsCount: $this->commentsCount,
        );
    }

    /**
     * Retorna una nueva instancia con las fotos del body actualizadas.
     *
     * @param array<int, object> $bodyPhotos Array de objetos Photo
     */
    public function withBodyPhotos(array $bodyPhotos): self
    {
        return new self(
            section: $this->section,
            tags: $this->tags,
            journalists: $this->journalists,
            multimedia: $this->multimedia,
            openingMedia: $this->openingMedia,
            metaImage: $this->metaImage,
            insertedNews: $this->insertedNews,
            recommended: $this->recommended,
            bodyPhotos: $bodyPhotos,
            membershipUrls: $this->membershipUrls,
            commentsCount: $this->commentsCount,
        );
    }

    /**
     * Retorna una nueva instancia con las URLs de membership actualizadas.
     *
     * @param array<int, string> $membershipUrls Array de URLs resueltas
     */
    public function withMembershipUrls(array $membershipUrls): self
    {
        return new self(
            section: $this->section,
            tags: $this->tags,
            journalists: $this->journalists,
            multimedia: $this->multimedia,
            openingMedia: $this->openingMedia,
            metaImage: $this->metaImage,
            insertedNews: $this->insertedNews,
            recommended: $this->recommended,
            bodyPhotos: $this->bodyPhotos,
            membershipUrls: $membershipUrls,
            commentsCount: $this->commentsCount,
        );
    }

    /**
     * Retorna una nueva instancia con el contador de comentarios actualizado.
     */
    public function withCommentsCount(int $commentsCount): self
    {
        return new self(
            section: $this->section,
            tags: $this->tags,
            journalists: $this->journalists,
            multimedia: $this->multimedia,
            openingMedia: $this->openingMedia,
            metaImage: $this->metaImage,
            insertedNews: $this->insertedNews,
            recommended: $this->recommended,
            bodyPhotos: $this->bodyPhotos,
            membershipUrls: $this->membershipUrls,
            commentsCount: $commentsCount,
        );
    }
}
