<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Extractor\Strategy;

use App\Orchestrator\Extractor\IdExtractorStrategyInterface;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\MembershipCardButton;

/**
 * Extracts membership card URLs from editorial body tags.
 *
 * Membership cards contain call-to-action buttons with URLs that need to be
 * processed by the membership service (tracking, attribution, etc.).
 *
 * This extractor collects all URLs from membership card buttons for batch
 * processing via the membership service.
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class MembershipLinkExtractor implements IdExtractorStrategyInterface
{
    /**
     * {@inheritDoc}
     *
     * @return array<int, string> Array of membership URLs
     */
    public function extract(Editorial $editorial): array
    {
        $membershipLinks = [];
        $body = $editorial->body();

        /** @var BodyTagMembershipCard[] $membershipCards */
        $membershipCards = $body->bodyElementsOf(BodyTagMembershipCard::class);

        foreach ($membershipCards as $membershipCard) {
            /** @var MembershipCardButton $button */
            foreach ($membershipCard->buttons()->buttons() as $button) {
                // Extract both membership URL and regular URL
                $membershipLinks[] = $button->urlMembership();
                $membershipLinks[] = $button->url();
            }
        }

        // Remove duplicates and empty values, then re-index
        return array_values(array_filter(array_unique($membershipLinks)));
    }

    /**
     * {@inheritDoc}
     */
    public function getManifestKey(): string
    {
        return 'membershipLinks';
    }
}
