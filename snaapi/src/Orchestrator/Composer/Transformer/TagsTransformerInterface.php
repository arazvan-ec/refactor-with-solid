<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

/**
 * Interface for transforming tag entities.
 *
 * Responsible for converting an array of Tag domain entities
 * into their API response representation.
 *
 * @author SNAAPI Refactoring Team
 */
interface TagsTransformerInterface
{
    /**
     * Transforms an array of tag entities.
     *
     * @param array<int, object> $tags Array of Tag entities
     * @param string $siteId The site identifier for URL generation
     *
     * @return array<int, array<string, mixed>> Array of transformed tags
     */
    public function transform(array $tags, string $siteId): array;
}
