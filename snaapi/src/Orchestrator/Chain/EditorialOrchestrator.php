<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Chain;

use App\Application\DataTransformer\Apps\AppsDataTransformer;
use App\Application\DataTransformer\Apps\JournalistsDataTransformer;
use App\Application\DataTransformer\Apps\RecommendedEditorialsDataTransformer;
use App\Application\DataTransformer\Apps\StandfirstDataTransformer;
use App\Application\DataTransformer\BodyDataTransformer;
use App\Ec\Snaapi\Infrastructure\Client\Http\QueryLegacyClient;
use App\Exception\EditorialNotPublishedYetException;
use App\Orchestrator\Processor\InsertedNewsProcessor;
use App\Orchestrator\Processor\MembershipLinksProcessor;
use App\Orchestrator\Processor\MultimediaProcessor;
use App\Orchestrator\Processor\RecommendedEditorialsProcessor;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialBlog;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Editorial\Domain\Model\QueryEditorialClient;
use Ec\Editorial\Domain\Model\Signature;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Journalist\Domain\Model\JournalistFactory;
use Ec\Journalist\Domain\Model\QueryJournalistClient;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\QueryTagClient;
use Ec\Tag\Domain\Model\Tag;
use GuzzleHttp\Promise\Utils;
use Http\Promise\Promise;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Editorial Orchestrator - Coordinates editorial content aggregation.
 *
 * Refactored to follow SOLID principles by delegating specific responsibilities
 * to specialized processors (SRP) while maintaining the same public interface.
 *
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 * @author SNAAPI Refactoring Team
 */
class EditorialOrchestrator implements EditorialOrchestratorInterface
{
    public const TWITTER_TYPES = [EditorialBlog::EDITORIAL_TYPE];
    public const UNWRAPPED = true;

    public function __construct(
        private readonly QueryLegacyClient $queryLegacyClient,
        private readonly QueryEditorialClient $queryEditorialClient,
        private readonly QuerySectionClient $querySectionClient,
        private readonly AppsDataTransformer $detailsAppsDataTransformer,
        private readonly QueryTagClient $queryTagClient,
        private readonly BodyDataTransformer $bodyDataTransformer,
        private readonly LoggerInterface $logger,
        private readonly JournalistsDataTransformer $journalistsDataTransformer,
        private readonly QueryJournalistClient $queryJournalistClient,
        private readonly JournalistFactory $journalistFactory,
        private readonly StandfirstDataTransformer $standFirstDataTransformer,
        private readonly RecommendedEditorialsDataTransformer $recommendedEditorialsDataTransformer,
        private readonly InsertedNewsProcessor $insertedNewsProcessor,
        private readonly RecommendedEditorialsProcessor $recommendedEditorialsProcessor,
        private readonly MultimediaProcessor $multimediaProcessor,
        private readonly MembershipLinksProcessor $membershipLinksProcessor,
    ) {
    }

    /**
     * Execute editorial orchestration - aggregate all editorial content.
     *
     * This method coordinates multiple processors to fetch and transform
     * editorial data, inserted news, recommended editorials, multimedia,
     * and membership links.
     *
     * @return array<string, mixed> Complete editorial response
     *
     * @throws EditorialNotPublishedYetException When editorial is not visible
     * @throws \Throwable When critical errors occur
     */
    public function execute(Request $request): array
    {
        /** @var string $id */
        $id = $request->get('id');

        /** @var NewsBase $editorial */
        $editorial = $this->queryEditorialClient->findEditorialById($id);

        // Fallback to legacy system if no source editorial
        if (null === $editorial->sourceEditorial()) {
            return $this->queryLegacyClient->findEditorialById($id);
        }

        if (!$editorial->isVisible()) {
            throw new EditorialNotPublishedYetException();
        }

        /** @var Section $section */
        $section = $this->querySectionClient->findSectionById($editorial->sectionId());

        // Initialize result data array
        $resolveData = [
            'multimedia' => [],
            'multimediaOpening' => [],
            'insertedNews' => [],
            'recommendedEditorials' => [],
            'photoFromBodyTags' => [],
            'membershipLinkCombine' => [],
        ];

        // Process inserted news with InsertedNewsProcessor
        if ($this->insertedNewsProcessor->supports($editorial)) {
            $insertedNewsData = $this->insertedNewsProcessor->process($editorial, []);
            $resolveData['insertedNews'] = $insertedNewsData['insertedNews'];
            $resolveData['multimedia'] = array_merge($resolveData['multimedia'], $insertedNewsData['multimedia']);
            $resolveData['multimediaOpening'] = array_merge($resolveData['multimediaOpening'], $insertedNewsData['multimediaOpening']);
        }

        // Process recommended editorials with RecommendedEditorialsProcessor
        $recommendedNews = [];
        if ($this->recommendedEditorialsProcessor->supports($editorial)) {
            $recommendedData = $this->recommendedEditorialsProcessor->process($editorial, []);
            $resolveData['recommendedEditorials'] = $recommendedData['recommendedEditorials'];
            $resolveData['multimedia'] = array_merge($resolveData['multimedia'], $recommendedData['multimedia']);
            $resolveData['multimediaOpening'] = array_merge($resolveData['multimediaOpening'], $recommendedData['multimediaOpening']);

            // Extract Editorial objects for transformer
            foreach ($resolveData['recommendedEditorials'] as $recData) {
                $recommendedNews[] = $recData['editorial'];
            }
        }

        // Resolve inserted/recommended multimedia promises
        if (!empty($resolveData['multimedia'])) {
            $resolveData['multimedia'] = Utils::settle($resolveData['multimedia'])
                ->then($this->createCallback([$this, 'fulfilledMultimedia']))
                ->wait(self::UNWRAPPED);
        }

        // Process main editorial multimedia with MultimediaProcessor
        $multimediaData = $this->multimediaProcessor->process($editorial, []);
        $resolveData['multimedia'] = array_merge($resolveData['multimedia'], $multimediaData['multimedia']);
        $resolveData['multimediaOpening'] = array_merge($resolveData['multimediaOpening'], $multimediaData['multimediaOpening']);
        $resolveData['photoFromBodyTags'] = $multimediaData['photoFromBodyTags'];

        // Process membership links with MembershipLinksProcessor
        if ($this->membershipLinksProcessor->supports($editorial)) {
            $membershipData = $this->membershipLinksProcessor->process($editorial, ['section' => $section]);
            $resolveData['membershipLinkCombine'] = $membershipData['membershipLinkCombine'];
        }

        // Fetch tags
        $tags = $this->fetchTags($editorial);

        // Build editorial result with base transformer
        $editorialResult = $this->detailsAppsDataTransformer->write(
            $editorial,
            $section,
            $tags
        )->read();

        // Add comments count from legacy system
        /** @var array{options: array{totalrecords?:int}} $comments */
        $comments = $this->queryLegacyClient->findCommentsByEditorialId($id);
        $editorialResult['countComments'] = $comments['options']['totalrecords'] ?? 0;

        // Process signatures
        $editorialResult['signatures'] = $this->processSignatures($editorial, $section);

        // Transform body with all resolved data
        $editorialResult['body'] = $this->bodyDataTransformer->execute(
            $editorial->body(),
            $resolveData
        );

        // Transform multimedia using MultimediaProcessor's transformer
        /** @var array{multimedia: array<string, array<string, mixed>>} $resolveData */
        $editorialResult['multimedia'] = $this->multimediaProcessor->transformMultimedia($editorial, $resolveData);

        // Transform standfirst
        $editorialResult['standfirst'] = $this->standFirstDataTransformer
            ->write($editorial->standFirst())
            ->read();

        // Transform recommended editorials
        /** @var array<string, array<string, array<string, mixed>>> $resolveData */
        $editorialResult['recommendedEditorials'] = $this->recommendedEditorialsDataTransformer
            ->write($recommendedNews, $resolveData)
            ->read();

        return $editorialResult;
    }

    public function canOrchestrate(): string
    {
        return 'editorial';
    }

    /**
     * Fetch all tags for the editorial.
     *
     * @param Editorial $editorial The editorial
     *
     * @return array<int, Tag> Array of Tag objects
     */
    private function fetchTags(Editorial $editorial): array
    {
        $tags = [];
        foreach ($editorial->tags()->getArrayCopy() as $tag) {
            try {
                /** @var Tag $fetchedTag */
                $fetchedTag = $this->queryTagClient->findTagById($tag->id());
                $tags[] = $fetchedTag;
            } catch (\Throwable $exception) {
                $this->logger->error(
                    'Failed to fetch tag',
                    [
                        'tagId' => $tag->id(),
                        'error' => $exception->getMessage(),
                    ]
                );
            }
        }

        return $tags;
    }

    /**
     * Process signatures for the editorial.
     *
     * @param Editorial $editorial The editorial
     * @param Section $section The section context
     *
     * @return array<int, array<string, mixed>> Array of transformed signatures
     */
    private function processSignatures(Editorial $editorial, Section $section): array
    {
        $signatures = [];

        foreach ($editorial->signatures()->getArrayCopy() as $signature) {
            $hasTwitter = \in_array($editorial->editorialType(), self::TWITTER_TYPES);
            $result = $this->retrieveAliasFormat(
                $signature->id()->id(),
                $section,
                $hasTwitter
            );
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
     * Create a callback wrapper for promise resolution.
     *
     * @param callable $callable The callback function
     * @param array<string, string> ...$parameters Additional parameters
     *
     * @return \Closure The wrapped callback
     */
    private function createCallback(callable $callable, ...$parameters): \Closure
    {
        return static function ($element) use ($callable, $parameters) {
            return $callable($element, ...$parameters);
        };
    }

    /**
     * Process fulfilled multimedia promises.
     *
     * @param array<string, mixed> $promises The resolved promises
     *
     * @return array<string, \Ec\Multimedia\Domain\Model\Multimedia> Fulfilled multimedia indexed by ID
     */
    private function fulfilledMultimedia(array $promises): array
    {
        $result = [];

        /** @var array{state: string, value?: \Ec\Multimedia\Domain\Model\Multimedia} $promise */
        foreach ($promises as $promise) {
            if (Promise::FULFILLED === $promise['state']) {
                /** @var \Ec\Multimedia\Domain\Model\Multimedia $multimedia */
                $multimedia = $promise['value'];
                $result[$multimedia->id()] = $multimedia;
            }
        }

        return $result;
    }
}
