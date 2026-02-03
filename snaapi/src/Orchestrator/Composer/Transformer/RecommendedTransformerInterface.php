<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

/**
 * Interface for transforming recommended editorial aggregates.
 *
 * Responsible for converting EditorialAggregate entities
 * (recommended editorials with their related data) into their API response representation.
 *
 * @author SNAAPI Refactoring Team
 */
interface RecommendedTransformerInterface
{
    /**
     * Transforms an array of recommended editorial aggregates.
     *
     * Each aggregate contains an editorial with its section, journalists, and multimedia.
     *
     * @param array<int, \App\Orchestrator\ValueObject\EditorialAggregate> $recommended Array of editorial aggregates
     * @param string $siteId The site identifier for URL generation
     *
     * @return array<int, array<string, mixed>> Array of transformed recommended editorials
     */
    public function transform(array $recommended, string $siteId): array;
}
