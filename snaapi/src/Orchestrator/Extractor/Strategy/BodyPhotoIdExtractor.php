<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Extractor\Strategy;

use App\Orchestrator\Extractor\IdExtractorStrategyInterface;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Ec\Editorial\Domain\Model\Editorial;

/**
 * Extracts photo IDs from editorial body tags.
 *
 * This includes photos embedded in:
 * - BodyTagPicture: Standard inline photos with captions
 * - BodyTagMembershipCard: Photos within membership card components
 *
 * All photo IDs are collected for parallel batch fetching.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class BodyPhotoIdExtractor implements IdExtractorStrategyInterface
{
    /**
     * {@inheritDoc}
     *
     * @return array<int, string> Array of photo IDs
     */
    public function extract(Editorial $editorial): array
    {
        $photoIds = [];
        $body = $editorial->body();

        // Extract from BodyTagPicture elements
        /** @var BodyTagPicture[] $bodyTagPictures */
        $bodyTagPictures = $body->bodyElementsOf(BodyTagPicture::class);
        foreach ($bodyTagPictures as $bodyTagPicture) {
            $photoIds[] = $bodyTagPicture->id()->id();
        }

        // Extract from BodyTagMembershipCard elements
        /** @var BodyTagMembershipCard[] $membershipCards */
        $membershipCards = $body->bodyElementsOf(BodyTagMembershipCard::class);
        foreach ($membershipCards as $membershipCard) {
            $photoIds[] = $membershipCard->bodyTagPictureMembership()->id()->id();
        }

        // Remove duplicates and re-index
        return array_values(array_unique($photoIds));
    }

    /**
     * {@inheritDoc}
     */
    public function getManifestKey(): string
    {
        return 'bodyPhotoIds';
    }
}
