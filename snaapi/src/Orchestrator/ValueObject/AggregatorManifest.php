<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ValueObject;

/**
 * Value Object que contiene todos los IDs extraídos de un editorial
 * que necesitan ser resueltos via HTTP.
 *
 * Este objeto inmutable actúa como manifiesto de agregación, conteniendo
 * todos los identificadores que requieren peticiones HTTP a microservicios
 * externos para resolver datos relacionados.
 */
final readonly class AggregatorManifest
{
    /**
     * @param string $editorialId ID del editorial principal
     * @param string|null $sectionId ID de la sección (opcional)
     * @param array<int, string> $tagIds IDs de tags asociados
     * @param array<int, string> $signatureIds IDs de alias de periodistas
     * @param array<int, string> $multimediaIds IDs de multimedia general
     * @param string|null $openingMediaId ID del multimedia de apertura (opcional)
     * @param string|null $metaImageId ID de la imagen meta (opcional)
     * @param array<int, string> $insertedNewsIds IDs de noticias insertadas (editoriales)
     * @param array<int, string> $recommendedIds IDs de editoriales recomendados
     * @param array<int, string> $bodyPhotoIds IDs de fotos del body
     * @param array<int, string> $membershipLinks Enlaces de membership a resolver
     */
    public function __construct(
        public string $editorialId,
        public ?string $sectionId = null,
        public array $tagIds = [],
        public array $signatureIds = [],
        public array $multimediaIds = [],
        public ?string $openingMediaId = null,
        public ?string $metaImageId = null,
        public array $insertedNewsIds = [],
        public array $recommendedIds = [],
        public array $bodyPhotoIds = [],
        public array $membershipLinks = [],
    ) {
    }

    /**
     * Verifica si hay una sección para resolver.
     */
    public function hasSection(): bool
    {
        return null !== $this->sectionId;
    }

    /**
     * Verifica si hay tags para resolver.
     */
    public function hasTags(): bool
    {
        return [] !== $this->tagIds;
    }

    /**
     * Verifica si hay firmas (periodistas) para resolver.
     */
    public function hasSignatures(): bool
    {
        return [] !== $this->signatureIds;
    }

    /**
     * Verifica si hay noticias insertadas para resolver.
     */
    public function hasInsertedNews(): bool
    {
        return [] !== $this->insertedNewsIds;
    }

    /**
     * Verifica si hay editoriales recomendados para resolver.
     */
    public function hasRecommended(): bool
    {
        return [] !== $this->recommendedIds;
    }

    /**
     * Verifica si hay fotos del body para resolver.
     */
    public function hasBodyPhotos(): bool
    {
        return [] !== $this->bodyPhotoIds;
    }

    /**
     * Verifica si hay enlaces de membership para resolver.
     */
    public function hasMembershipLinks(): bool
    {
        return [] !== $this->membershipLinks;
    }

    /**
     * Calcula el total de llamadas HTTP necesarias para resolver este manifiesto.
     *
     * Cuenta:
     * - 1 llamada por sección (si existe)
     * - N llamadas para tags (o 1 batch call)
     * - N llamadas para firmas (o 1 batch call)
     * - N llamadas para multimedia
     * - 1 llamada para opening media (si existe)
     * - 1 llamada para meta image (si existe)
     * - N llamadas para noticias insertadas (cada una con sus deps)
     * - N llamadas para recomendados (cada uno con sus deps)
     * - N llamadas para fotos del body
     * - N llamadas para membership links
     *
     * @return int Número total de llamadas HTTP estimadas
     */
    public function getTotalHttpCalls(): int
    {
        $total = 0;

        // Sección
        if ($this->hasSection()) {
            ++$total;
        }

        // Tags
        $total += count($this->tagIds);

        // Firmas
        $total += count($this->signatureIds);

        // Multimedia
        $total += count($this->multimediaIds);

        // Opening media
        if (null !== $this->openingMediaId) {
            ++$total;
        }

        // Meta image
        if (null !== $this->metaImageId) {
            ++$total;
        }

        // Noticias insertadas (cada una puede requerir múltiples llamadas)
        $total += count($this->insertedNewsIds);

        // Recomendados
        $total += count($this->recommendedIds);

        // Fotos del body
        $total += count($this->bodyPhotoIds);

        // Membership links
        $total += count($this->membershipLinks);

        return $total;
    }
}
