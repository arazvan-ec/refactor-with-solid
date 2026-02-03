<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Processor;

use App\Application\DataTransformer\Apps\JournalistsDataTransformer;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Editorial\Domain\Model\Signature;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Journalist\Domain\Model\JournalistFactory;
use Ec\Journalist\Domain\Model\QueryJournalistClient;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Infrastructure\Client\Http\QueryMultimediaClient;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;
use Http\Promise\Promise;
use Psr\Log\LoggerInterface;

/**
 * Processes recommended editorials associated with the main editorial.
 *
 * This processor extracts recommended editorial IDs from the editorial's
 * recommendedEditorials collection, fetches their full data (editorial, section,
 * signatures, multimedia), and prepares them for the response.
 *
 * Responsibilities:
 * - Extract recommended editorial IDs
 * - Fetch editorial, section, and journalist data for each recommendation
 * - Process signatures and multimedia for recommended editorials
 * - Filter out non-visible editorials
 * - Handle errors gracefully (logging failures without blocking the entire process)
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class RecommendedEditorialsProcessor implements ProcessorInterface
{
    public function __construct(
        private QueryEditorialClient $queryEditorialClient,
        private QuerySectionClient $querySectionClient,
        private QueryMultimediaClient $queryMultimediaClient,
        private QueryJournalistClient $queryJournalistClient,
        private JournalistFactory $journalistFactory,
        private JournalistsDataTransformer $journalistsDataTransformer,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param Editorial $editorial The editorial to process
     * @param array<string, mixed> $context Context data (unused for this processor)
     *
     * @return array{recommendedEditorials: array<string, array{editorial: Editorial, section: Section, signatures: array<int, array<string, mixed>>, multimediaId: string}>, multimedia: array<int, Promise>, multimediaOpening: array<string, array{resource: mixed, opening: AbstractMultimedia}>}
     */
    public function process(Editorial $editorial, array $context): array
    {
        $result = [
            'recommendedEditorials' => [],
            'multimedia' => [],
            'multimediaOpening' => [],
        ];

        $recommendedEditorials = $editorial->recommendedEditorials();

        /** @var EditorialId $recommendedEditorialId */
        foreach ($recommendedEditorials->editorialIds() as $recommendedEditorialId) {
            $idRecommended = $recommendedEditorialId->id();

            try {
                /** @var Editorial $recommendedEditorial */
                $recommendedEditorial = $this->queryEditorialClient->findEditorialById($idRecommended);

                if (!$recommendedEditorial->isVisible()) {
                    continue;
                }

                /** @var Section $sectionInserted */
                $sectionInserted = $this->querySectionClient->findSectionById($recommendedEditorial->sectionId());

                $signatures = $this->processSignatures($recommendedEditorial, $sectionInserted);

                // Determine multimedia ID (from multimedia or metaImage)
                if (!empty($recommendedEditorial->multimedia()->id()->id())) {
                    $multimediaId = $recommendedEditorial->multimedia()->id()->id();
                    $result['multimedia'][] = $this->queryMultimediaClient->findMultimediaById(
                        $multimediaId,
                        true // ASYNC
                    );
                } else {
                    $multimediaId = $recommendedEditorial->metaImage();
                    $result = $this->processMetaImage($recommendedEditorial, $result);
                }

                $result['recommendedEditorials'][$idRecommended] = [
                    'editorial' => $recommendedEditorial,
                    'section' => $sectionInserted,
                    'signatures' => $signatures,
                    'multimediaId' => $multimediaId,
                ];
            } catch (\Throwable $throwable) {
                $this->logger->error(
                    'Failed to process recommended editorial',
                    [
                        'recommendedEditorialId' => $idRecommended,
                        'error' => $throwable->getMessage(),
                    ]
                );
                continue;
            }
        }

        return $result;
    }

    public function supports(Editorial $editorial): bool
    {
        // This processor supports editorials that have recommendations
        $recommendedEditorials = $editorial->recommendedEditorials();

        return count($recommendedEditorials->editorialIds()) > 0;
    }

    /**
     * Process signatures for an editorial.
     *
     * @param Editorial $editorial The editorial whose signatures to process
     * @param Section $section The section context for URL generation
     *
     * @return array<int, array<string, mixed>> Transformed signatures
     */
    private function processSignatures(Editorial $editorial, Section $section): array
    {
        $signatures = [];

        /** @var Signature $signature */
        foreach ($editorial->signatures()->getArrayCopy() as $signature) {
            $result = $this->retrieveAliasFormat($signature->id()->id(), $section);
            if (!empty($result)) {
                $signatures[] = $result;
            }
        }

        return $signatures;
    }

    /**
     * Retrieve and format journalist alias data.
     *
     * @param string $aliasId The journalist alias ID
     * @param Section $section The section context
     * @param bool $hasTwitter Whether to include Twitter information
     *
     * @return array<string, mixed> Formatted journalist data (empty if not found)
     */
    private function retrieveAliasFormat(string $aliasId, Section $section, bool $hasTwitter = false): array
    {
        try {
            $aliasIdModel = $this->journalistFactory->buildAliasId($aliasId);

            /** @var Journalist $journalist */
            $journalist = $this->queryJournalistClient->findJournalistByAliasId($aliasIdModel);

            return $this->journalistsDataTransformer
                ->write($aliasId, $journalist, $section, $hasTwitter)
                ->read();
        } catch (\Throwable $throwable) {
            $this->logger->error(
                'Failed to retrieve journalist alias',
                [
                    'aliasId' => $aliasId,
                    'error' => $throwable->getMessage(),
                ]
            );

            return [];
        }
    }

    /**
     * Process meta image for an editorial (fallback when no multimedia exists).
     *
     * @param Editorial $editorial The editorial with meta image
     * @param array<string, mixed> $result Current result array to append to
     *
     * @return array<string, mixed> Updated result with meta image data
     */
    private function processMetaImage(Editorial $editorial, array $result): array
    {
        if (empty($editorial->metaImage())) {
            return $result;
        }

        try {
            /** @var AbstractMultimedia $multimedia */
            $multimedia = $this->queryMultimediaClient->findMultimediaById($editorial->metaImage());

            if (!$multimedia instanceof \Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto) {
                return $result;
            }

            $resource = $this->queryMultimediaClient->findPhotoById($multimedia->resourceId());
            $result['multimediaOpening'][$editorial->metaImage()]['resource'] = $resource;
            $result['multimediaOpening'][$editorial->metaImage()]['opening'] = $multimedia;
        } catch (\Throwable $throwable) {
            $this->logger->error(
                'Failed to process meta image',
                [
                    'editorialId' => $editorial->id()->id(),
                    'metaImageId' => $editorial->metaImage(),
                    'error' => $throwable->getMessage(),
                ]
            );
        }

        return $result;
    }
}
