<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

/**
 * Interface for transforming inserted news aggregates.
 *
 * Responsible for converting EditorialAggregate entities
 * (inserted news with their related data) into their API response representation.
 *
 * @author SNAAPI Refactoring Team
 */
interface InsertedNewsTransformerInterface
{
    /**
     * Transforms an array of inserted news aggregates.
     *
     * Each aggregate contains an editorial with its section, journalists, and multimedia.
     *
     * @param array<int, \App\Orchestrator\ValueObject\EditorialAggregate> $insertedNews Array of editorial aggregates
     * @param string $siteId The site identifier for URL generation
     *
     * @return array<int, array<string, mixed>> Array of transformed inserted news
     */
    public function transform(array $insertedNews, string $siteId): array;
}
