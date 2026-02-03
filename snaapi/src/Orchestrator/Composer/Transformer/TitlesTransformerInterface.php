<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

use Ec\Editorial\Domain\Model\Editorial;

/**
 * Interface for transforming editorial titles.
 *
 * Responsible for extracting and formatting all title variants
 * (web, mobile, SEO) from an editorial entity.
 *
 * @author SNAAPI Refactoring Team
 */
interface TitlesTransformerInterface
{
    /**
     * Transforms editorial titles into a normalized array structure.
     *
     * @param Editorial $editorial The editorial entity
     *
     * @return array<string, string> Array with keys: web, mobile, seo
     */
    public function transform(Editorial $editorial): array;
}
