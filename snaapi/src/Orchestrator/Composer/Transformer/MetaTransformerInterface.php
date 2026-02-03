<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

use App\Orchestrator\ValueObject\ResolvedAggregatorData;
use Ec\Editorial\Domain\Model\Editorial;

/**
 * Interface for transforming editorial metadata.
 *
 * Responsible for generating meta information (SEO, social media, etc.)
 * from editorial and resolved aggregator data.
 *
 * @author SNAAPI Refactoring Team
 */
interface MetaTransformerInterface
{
    /**
     * Transforms editorial and aggregator data into metadata structure.
     *
     * @param Editorial $editorial The editorial entity
     * @param ResolvedAggregatorData $data All resolved aggregator data
     * @param string $siteId The site identifier
     *
     * @return array<string, mixed> Metadata ready for JSON response
     */
    public function transform(
        Editorial $editorial,
        ResolvedAggregatorData $data,
        string $siteId
    ): array;
}
