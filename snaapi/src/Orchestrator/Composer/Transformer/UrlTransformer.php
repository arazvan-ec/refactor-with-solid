<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

use App\Infrastructure\Service\UrlGeneratorServiceInterface;
use Ec\Editorial\Domain\Model\Editorial;

/**
 * Transforms an editorial into its canonical URL.
 *
 * This transformer follows the Single Responsibility Principle (SRP)
 * by focusing solely on URL generation for editorials.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class UrlTransformer implements UrlTransformerInterface
{
    public function __construct(
        private UrlGeneratorServiceInterface $urlGenerator,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function transform(Editorial $editorial, string $siteId): string
    {
        return $this->urlGenerator->generateUrl(
            'https://%s.%s.%s/%s',
            'www',
            $siteId,
            trim($editorial->url(), '/')
        );
    }
}
