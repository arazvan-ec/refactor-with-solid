<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

/**
 * Interface for transforming journalist/signature entities.
 *
 * Responsible for converting an array of Journalist domain entities
 * into their API response representation as signatures.
 *
 * @author SNAAPI Refactoring Team
 */
interface SignaturesTransformerInterface
{
    /**
     * Transforms an array of journalist entities into signatures.
     *
     * @param array<int, object> $journalists Array of Journalist entities
     * @param string $siteId The site identifier for URL generation
     *
     * @return array<int, array<string, mixed>> Array of transformed signatures
     */
    public function transform(array $journalists, string $siteId): array;
}
