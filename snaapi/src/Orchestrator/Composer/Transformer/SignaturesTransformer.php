<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer\Transformer;

use App\Infrastructure\Service\Thumbor;
use App\Infrastructure\Service\UrlGeneratorServiceInterface;
use Ec\Encode\Encode;
use Ec\Journalist\Domain\Model\Journalist;

/**
 * Transforms journalist entities into signature format.
 *
 * This transformer follows the Single Responsibility Principle (SRP)
 * by focusing solely on journalist/signature data transformation.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class SignaturesTransformer implements SignaturesTransformerInterface
{
    public function __construct(
        private UrlGeneratorServiceInterface $urlGenerator,
        private Thumbor $thumbor,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function transform(array $journalists, string $siteId): array
    {
        $signatures = [];

        foreach ($journalists as $journalist) {
            if (!$journalist instanceof Journalist) {
                continue;
            }

            $signatures[] = [
                'id' => $journalist->id()->id(),
                'name' => $journalist->name(),
                'url' => $journalist->isVisible() ? $this->generateJournalistUrl($journalist, $siteId) : '',
                'photo' => $this->generatePhotoUrl($journalist),
            ];
        }

        return $signatures;
    }

    /**
     * Generates the canonical URL for a journalist.
     */
    private function generateJournalistUrl(Journalist $journalist, string $siteId): string
    {
        return $this->urlGenerator->generateUrl(
            'https://%s.%s.%s/autores/%s/',
            'www',
            $siteId,
            sprintf('%s-%s', Encode::encodeUrl($journalist->name()), $journalist->id()->id())
        );
    }

    /**
     * Generates the photo URL for a journalist.
     */
    private function generatePhotoUrl(Journalist $journalist): string
    {
        if (!empty($journalist->blogPhoto())) {
            return $this->thumbor->createJournalistImage($journalist->blogPhoto());
        }

        if (!empty($journalist->photo())) {
            return $this->thumbor->createJournalistImage($journalist->photo());
        }

        return '';
    }
}
