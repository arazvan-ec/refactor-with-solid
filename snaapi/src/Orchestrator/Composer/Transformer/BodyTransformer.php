<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

use App\Application\DataTransformer\BodyDataTransformerInterface;
use Ec\Editorial\Domain\Model\Body\Body;

/**
 * Transforms body content into API response format.
 *
 * This transformer follows the Single Responsibility Principle (SRP)
 * by focusing solely on body content transformation.
 *
 * Delegates actual element transformation to BodyDataTransformer
 * which uses the Strategy pattern for different body element types.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class BodyTransformer implements BodyTransformerInterface
{
    public function __construct(
        private BodyDataTransformerInterface $bodyDataTransformer,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function transform(
        Body $body,
        array $bodyPhotos,
        array $membershipUrls,
        string $siteId
    ): array {
        // Delegate to existing BodyDataTransformer which already handles
        // all body element types via Strategy pattern
        return $this->bodyDataTransformer->transform(
            $body,
            $bodyPhotos,
            $membershipUrls,
            $siteId
        );
    }
}
