<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ValueObject;

/**
 * Representa una petición batch de múltiples IDs del mismo tipo.
 *
 * Este Value Object encapsula una solicitud de lote (batch) para obtener
 * múltiples entidades del mismo tipo en una sola operación HTTP, optimizando
 * la comunicación con microservicios externos.
 *
 * Soporta tanto peticiones síncronas como asíncronas.
 */
final readonly class BatchRequest
{
    /**
     * @param string $type Tipo de entidad a solicitar (tag, journalist, multimedia, etc.)
     * @param array<int, string> $ids Array de IDs a solicitar en batch
     * @param bool $async Indica si la petición debe ser asíncrona (default: true)
     */
    public function __construct(
        public string $type,
        public array $ids,
        public bool $async = true,
    ) {
    }

    /**
     * Verifica si la petición batch está vacía (sin IDs).
     *
     * @return bool True si no hay IDs para solicitar
     */
    public function isEmpty(): bool
    {
        return [] === $this->ids;
    }

    /**
     * Retorna el número de IDs en esta petición batch.
     *
     * @return int Cantidad de elementos a solicitar
     */
    public function count(): int
    {
        return count($this->ids);
    }

    /**
     * Verifica si esta petición es del tipo especificado.
     *
     * @param string $type Tipo a verificar
     * @return bool True si coincide con el tipo de esta petición
     */
    public function isType(string $type): bool
    {
        return $this->type === $type;
    }

    /**
     * Crea una nueva petición batch con los IDs filtrados según un criterio.
     *
     * @param callable $callback Función de filtrado: function(string $id): bool
     * @return self Nueva instancia con IDs filtrados
     */
    public function filter(callable $callback): self
    {
        return new self(
            type: $this->type,
            ids: array_filter($this->ids, $callback),
            async: $this->async,
        );
    }

    /**
     * Divide esta petición batch en múltiples peticiones de menor tamaño.
     *
     * Útil para evitar timeouts o límites de tamaño en las APIs externas.
     *
     * @param int $chunkSize Tamaño máximo de cada chunk
     * @return array<int, self> Array de BatchRequest de menor tamaño
     */
    public function chunk(int $chunkSize): array
    {
        if ($chunkSize <= 0) {
            throw new \InvalidArgumentException('Chunk size must be greater than 0');
        }

        $chunks = array_chunk($this->ids, $chunkSize);
        $batchRequests = [];

        foreach ($chunks as $chunk) {
            $batchRequests[] = new self(
                type: $this->type,
                ids: $chunk,
                async: $this->async,
            );
        }

        return $batchRequests;
    }
}
