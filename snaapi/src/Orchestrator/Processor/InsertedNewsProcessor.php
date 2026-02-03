<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Processor;

use App\Application\DataTransformer\Apps\JournalistsDataTransformer;
use Ec\Editorial\Domain\Model\Body\BodyTagInsertedNews;
use Ec\Editorial\Domain\Model\Editorial;
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
 * Processes inserted news references found in editorial body tags.
 *
 * This processor extracts BodyTagInsertedNews elements from the editorial body,
 * fetches the referenced editorials, their sections, signatures, and multimedia,
 * and prepares them for inclusion in the response.
 *
 * Responsibilities:
 * - Extract inserted news IDs from body tags
 * - Fetch editorial, section, and journalist data for each inserted news
 * - Process signatures and multimedia for inserted editorials
 * - Filter out non-visible editorials
 * - Handle errors gracefully (logging failures without blocking)
 *
 * @author SNAAPI Refactoring Team
 */
final readonly class InsertedNewsProcessor implements ProcessorInterface
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
     * @return array{insertedNews: array<string, array{editorial: Editorial, section: Section, signatures: array<int, array<string, mixed>>, multimediaId: string}>, multimedia: array<int, Promise>, multimediaOpening: array<string, array{resource: mixed, opening: AbstractMultimedia}>}
     */
    public function process(Editorial $editorial, array $context): array
    {
        $result = [
            'insertedNews' => [],
            'multimedia' => [],
            'multimediaOpening' => [],
        ];

        /** @var BodyTagInsertedNews[] $insertedNews */
        $insertedNews = $editorial->body()->bodyElementsOf(BodyTagInsertedNews::class);

        foreach ($insertedNews as $insertedNew) {
            $idInserted = $insertedNew->editorialId()->id();

            try {
                /** @var Editorial $insertedEditorial */
                $insertedEditorial = $this->queryEditorialClient->findEditorialById($idInserted);

                if (!$insertedEditorial->isVisible()) {
                    continue;
                }

                /** @var Section $sectionInserted */
                $sectionInserted = $this->querySectionClient->findSectionById($insertedEditorial->sectionId());

                $signatures = $this->processSignatures($insertedEditorial, $sectionInserted);

                // Determine multimedia ID (from multimedia or metaImage)
                if (!empty($insertedEditorial->multimedia()->id()->id())) {
                    $multimediaId = $insertedEditorial->multimedia()->id()->id();
                    $result['multimedia'][] = $this->queryMultimediaClient->findMultimediaById(
                        $multimediaId,
                        true // ASYNC
                    );
                } else {
                    $multimediaId = $insertedEditorial->metaImage();
                    $result = $this->processMetaImage($insertedEditorial, $result);
                }

                $result['insertedNews'][$idInserted] = [
                    'editorial' => $insertedEditorial,
                    'section' => $sectionInserted,
                    'signatures' => $signatures,
                    'multimediaId' => $multimediaId,
                ];
            } catch (\Throwable $throwable) {
                $this->logger->error(
                    'Failed to process inserted news',
                    [
                        'insertedNewsId' => $idInserted,
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
        // This processor supports editorials that have inserted news in their body
        $insertedNews = $editorial->body()->bodyElementsOf(BodyTagInsertedNews::class);

        return count($insertedNews) > 0;
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
