<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

use Ec\Section\Domain\Model\Section;

/**
 * Interface for transforming section entities.
 *
 * Responsible for converting a Section domain entity
 * into its API response representation.
 *
 * @author SNAAPI Refactoring Team
 */
interface SectionTransformerInterface
{
    /**
     * Transforms a section entity into array representation.
     *
     * @param Section $section The section entity to transform
     * @param string $siteId The site identifier for URL generation
     *
     * @return array<string, mixed> Section data ready for JSON response
     */
    public function transform(Section $section, string $siteId): array;
}
