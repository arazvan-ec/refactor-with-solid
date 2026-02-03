<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

use Ec\Editorial\Domain\Model\Editorial;

/**
 * Interface for transforming editorial to canonical URL.
 *
 * Responsible for generating the canonical URL of an editorial
 * based on its properties and the site configuration.
 *
 * @author SNAAPI Refactoring Team
 */
interface UrlTransformerInterface
{
    /**
     * Transforms an editorial into its canonical URL.
     *
     * @param Editorial $editorial The editorial entity
     * @param string $siteId The site identifier for URL generation
     *
     * @return string The canonical URL
     */
    public function transform(Editorial $editorial, string $siteId): string;
}
