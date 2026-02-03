<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ValueObject;

/**
 * Agregado de datos de un editorial relacionado (inserted/recommended)
 * con toda la información ya resuelta.
 *
 * Este Value Object representa un editorial completo con todos sus datos
 * agregados ya resueltos (sección, periodistas, multimedia), utilizado
 * para noticias insertadas y editoriales recomendados.
 *
 * Al contener toda la información pre-resuelta, elimina la necesidad de
 * realizar peticiones HTTP adicionales al momento de construir la respuesta.
 */
final readonly class EditorialAggregate
{
    /**
     * @param object $editorial Objeto Editorial principal
     * @param object|null $section Objeto Section asociado (opcional)
     * @param array<int, object> $journalists Array de objetos Journalist asociados
     * @param object|null $multimedia Objeto Multimedia asociado (opcional)
     */
    public function __construct(
        public object $editorial,
        public ?object $section = null,
        public array $journalists = [],
        public ?object $multimedia = null,
    ) {
    }

    /**
     * Verifica si el agregado tiene una sección asociada.
     */
    public function hasSection(): bool
    {
        return null !== $this->section;
    }

    /**
     * Verifica si el agregado tiene periodistas asociados.
     */
    public function hasJournalists(): bool
    {
        return [] !== $this->journalists;
    }

    /**
     * Verifica si el agregado tiene multimedia asociado.
     */
    public function hasMultimedia(): bool
    {
        return null !== $this->multimedia;
    }

    /**
     * Retorna el número de periodistas asociados.
     */
    public function getJournalistsCount(): int
    {
        return count($this->journalists);
    }
}
