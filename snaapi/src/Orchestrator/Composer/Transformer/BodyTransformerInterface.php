<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

use Ec\Editorial\Domain\Model\Body\Body;

/**
 * Interface for transforming editorial body content.
 *
 * Responsible for converting Body domain entity with all its elements
 * into their API response representation, including photos and membership cards.
 *
 * @author SNAAPI Refactoring Team
 */
interface BodyTransformerInterface
{
    /**
     * Transforms body content with embedded photos and membership data.
     *
     * @param Body $body The body entity containing all content elements
     * @param array<int, object> $bodyPhotos Array of resolved Photo entities
     * @param array<int, string> $membershipUrls Array of resolved membership URLs
     * @param string $siteId The site identifier for URL generation
     *
     * @return array<int, array<string, mixed>> Array of transformed body elements
     */
    public function transform(
        Body $body,
        array $bodyPhotos,
        array $membershipUrls,
        string $siteId
    ): array;
}
