<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Processor;

use App\Infrastructure\Enum\SitesEnum;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\MembershipCardButton;
use Ec\Membership\Infrastructure\Client\Http\QueryMembershipClient;
use Ec\Section\Domain\Model\Section;
use Http\Promise\Promise;
use Psr\Http\Message\UriFactoryInterface;

/**
 * Processes membership card links embedded in editorial body tags.
 *
 * This processor extracts membership links from BodyTagMembershipCard elements,
 * sends them to the membership service for processing/tracking, and returns
 * the processed URLs for inclusion in the response.
 *
 * Responsibilities:
 * - Extract membership links from body tags
 * - Create URI objects for each link
 * - Send links to membership service asynchronously
 * - Resolve promises and combine original links with processed URLs
 * - Handle errors gracefully (returning empty array on failure)
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class MembershipLinksProcessor implements ProcessorInterface
{
    public function __construct(
        private QueryMembershipClient $queryMembershipClient,
        private UriFactoryInterface $uriFactory,
    ) {
    }

    /**
     * @param Editorial $editorial The editorial to process
     * @param array<string, mixed> $context Context data containing 'section' and 'siteId'
     *
     * @return array{membershipLinkCombine: array<string, string>|array<empty, empty>} Mapping of original URLs to processed URLs
     */
    public function process(Editorial $editorial, array $context): array
    {
        /** @var Section|null $section */
        $section = $context['section'] ?? null;

        if (null === $section) {
            return ['membershipLinkCombine' => []];
        }

        $siteId = $section->siteId();
        $linksData = $this->extractLinksFromBody($editorial->body());

        if (empty($linksData)) {
            return ['membershipLinkCombine' => []];
        }

        [$promise, $links] = $this->createMembershipPromise($editorial, $linksData, $siteId);

        return [
            'membershipLinkCombine' => $this->resolvePromiseMembershipLinks($promise, $links),
        ];
    }

    public function supports(Editorial $editorial): bool
    {
        // This processor supports editorials that have membership cards in their body
        $membershipCards = $editorial->body()->bodyElementsOf(BodyTagMembershipCard::class);

        return count($membershipCards) > 0;
    }

    /**
     * Extract all membership links from body tags.
     *
     * @param Body $body The editorial body
     *
     * @return array<int, string> Array of membership URLs
     */
    private function extractLinksFromBody(Body $body): array
    {
        $linksData = [];

        $bodyElementsMembership = $body->bodyElementsOf(BodyTagMembershipCard::class);

        /** @var BodyTagMembershipCard $bodyElement */
        foreach ($bodyElementsMembership as $bodyElement) {
            /** @var MembershipCardButton $button */
            foreach ($bodyElement->buttons()->buttons() as $button) {
                $linksData[] = $button->urlMembership();
                $linksData[] = $button->url();
            }
        }

        return $linksData;
    }

    /**
     * Create a promise for membership link processing.
     *
     * @param Editorial $editorial The editorial
     * @param array<int, string> $linksData The links to process
     * @param string $siteId The site ID
     *
     * @return array{0: Promise|null, 1: array<int, string>} Tuple of [promise, links]
     */
    private function createMembershipPromise(Editorial $editorial, array $linksData, string $siteId): array
    {
        $links = [];
        $uris = [];

        /** @var string $membershipLink */
        foreach ($linksData as $membershipLink) {
            $uris[] = $this->uriFactory->createUri($membershipLink);
            $links[] = $membershipLink;
        }

        /** @var Promise $promise */
        $promise = $this->queryMembershipClient->getMembershipUrl(
            $editorial->id()->id(),
            $uris,
            SitesEnum::getEncodenameById($siteId),
            true // async
        );

        return [$promise, $links];
    }

    /**
     * Resolve the membership links promise and combine with original links.
     *
     * @param Promise|null $promise The promise to resolve
     * @param array<int, string> $links The original links
     *
     * @return array<string, string>|array<empty, empty> Mapping of original URL to processed URL, or empty array on failure
     */
    private function resolvePromiseMembershipLinks(?Promise $promise, array $links): array
    {
        if (null === $promise) {
            return [];
        }

        try {
            /** @var array<string, mixed> $membershipLinkResult */
            $membershipLinkResult = $promise->wait();

            if (empty($membershipLinkResult)) {
                return [];
            }

            return array_combine($links, $membershipLinkResult);
        } catch (\Throwable) {
            return [];
        }
    }
}
