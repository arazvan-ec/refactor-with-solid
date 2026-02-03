<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ValueObject;

/**
 * DTO que representa la respuesta final del editorial.
 * Preparado para serialización JSON.
 *
 * Este objeto es el resultado final del pipeline de agregación,
 * conteniendo toda la información del editorial con sus datos relacionados
 * ya transformados y listos para ser serializados a JSON.
 *
 * Estructura diseñada para la API v1 de SNAAPI.
 */
final readonly class EditorialResponse
{
    /**
     * @param string $id ID del editorial
     * @param string $url URL canónica del editorial
     * @param array<string, string> $titles Array asociativo de títulos (title, short, social, etc.)
     * @param array<string, mixed>|null $section Datos de la sección transformados
     * @param array<int, array<string, mixed>> $tags Array de tags transformados
     * @param array<int, array<string, mixed>> $signatures Array de firmas/periodistas transformados
     * @param array<string, mixed>|null $openingMedia Datos del multimedia de apertura
     * @param array<int, array<string, mixed>> $body Array de elementos del body transformados
     * @param array<int, array<string, mixed>> $insertedNews Array de noticias insertadas transformadas
     * @param array<int, array<string, mixed>> $recommended Array de editoriales recomendados transformados
     * @param int $countComments Número de comentarios
     * @param array<string, mixed>|null $membership Datos de membership (si aplica)
     * @param array<string, mixed> $meta Metadatos adicionales (SEO, social, etc.)
     */
    public function __construct(
        public string $id,
        public string $url,
        public array $titles,
        public ?array $section,
        public array $tags,
        public array $signatures,
        public ?array $openingMedia,
        public array $body,
        public array $insertedNews,
        public array $recommended,
        public int $countComments,
        public ?array $membership,
        public array $meta,
    ) {
    }

    /**
     * Convierte la respuesta a un array asociativo listo para JSON.
     *
     * Mantiene la estructura esperada por los clientes de la API v1.
     *
     * @return array<string, mixed> Representación en array del editorial
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'titles' => $this->titles,
            'section' => $this->section,
            'tags' => $this->tags,
            'signatures' => $this->signatures,
            'openingMedia' => $this->openingMedia,
            'body' => $this->body,
            'insertedNews' => $this->insertedNews,
            'recommended' => $this->recommended,
            'countComments' => $this->countComments,
            'membership' => $this->membership,
            'meta' => $this->meta,
        ];
    }

    /**
     * Verifica si el editorial tiene una sección.
     */
    public function hasSection(): bool
    {
        return null !== $this->section;
    }

    /**
     * Verifica si el editorial tiene tags.
     */
    public function hasTags(): bool
    {
        return [] !== $this->tags;
    }

    /**
     * Verifica si el editorial tiene firmas.
     */
    public function hasSignatures(): bool
    {
        return [] !== $this->signatures;
    }

    /**
     * Verifica si el editorial tiene multimedia de apertura.
     */
    public function hasOpeningMedia(): bool
    {
        return null !== $this->openingMedia;
    }

    /**
     * Verifica si el editorial tiene noticias insertadas.
     */
    public function hasInsertedNews(): bool
    {
        return [] !== $this->insertedNews;
    }

    /**
     * Verifica si el editorial tiene editoriales recomendados.
     */
    public function hasRecommended(): bool
    {
        return [] !== $this->recommended;
    }

    /**
     * Verifica si el editorial tiene información de membership.
     */
    public function hasMembership(): bool
    {
        return null !== $this->membership;
    }

    /**
     * Verifica si el editorial tiene comentarios.
     */
    public function hasComments(): bool
    {
        return $this->countComments > 0;
    }
}
