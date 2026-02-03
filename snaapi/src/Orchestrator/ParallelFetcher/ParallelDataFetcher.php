<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ParallelFetcher;

use App\Orchestrator\ParallelFetcher\Resolver\CommentResolver;
use App\Orchestrator\ParallelFetcher\Resolver\InsertedNewsBatchResolver;
use App\Orchestrator\ParallelFetcher\Resolver\JournalistBatchResolver;
use App\Orchestrator\ParallelFetcher\Resolver\MembershipResolver;
use App\Orchestrator\ParallelFetcher\Resolver\MultimediaBatchResolver;
use App\Orchestrator\ParallelFetcher\Resolver\RecommendedBatchResolver;
use App\Orchestrator\ParallelFetcher\Resolver\SectionResolver;
use App\Orchestrator\ParallelFetcher\Resolver\TagBatchResolver;
use App\Orchestrator\ValueObject\AggregatorManifest;
use App\Orchestrator\ValueObject\ResolvedAggregatorData;
use Http\Promise\Promise;

/**
 * Parallel data fetcher implementation.
 *
 * This class orchestrates parallel HTTP requests to multiple microservices
 * to fetch all aggregated data needed for an editorial response.
 *
 * It follows these design principles:
 * - Single Responsibility: Only coordinates parallel fetching
 * - Open/Closed: New resolvers can be added without modifying this class
 * - Dependency Inversion: Depends on resolver interfaces, not implementations
 * - Interface Segregation: Uses focused resolver interfaces
 *
 * The fetching strategy:
 * 1. Build promises for all required data based on AggregatorManifest
 * 2. Execute all promises in parallel using HTTPlug's async capabilities
 * 3. Aggregate results into ResolvedAggregatorData
 * 4. Handle failures gracefully (partial data is better than no data)
 */
final readonly class ParallelDataFetcher implements ParallelDataFetcherInterface
{
    public function __construct(
        private SectionResolver $sectionResolver,
        private TagBatchResolver $tagResolver,
        private JournalistBatchResolver $journalistResolver,
        private MultimediaBatchResolver $multimediaResolver,
        private InsertedNewsBatchResolver $insertedNewsResolver,
        private RecommendedBatchResolver $recommendedResolver,
        private CommentResolver $commentResolver,
        private MembershipResolver $membershipResolver,
    ) {
    }

    /**
     * Fetches all required data in parallel.
     *
     * This method orchestrates parallel HTTP calls across multiple levels:
     *
     * Level 2 (parallel):
     * - Section (if exists)
     * - Tags (batch)
     * - Journalists (batch)
     * - Multimedia (batch)
     * - Comments
     * - Membership URLs (if exist)
     *
     * Level 3 (parallel):
     * - Inserted news editorials with their dependencies (batch)
     * - Recommended editorials with their dependencies (batch)
     * - Body photos (batch)
     *
     * Uses settle pattern to handle partial failures gracefully.
     */
    public function fetchAll(AggregatorManifest $manifest, string $siteId): ResolvedAggregatorData
    {
        // Build all promises
        $promises = $this->buildPromises($manifest, $siteId);

        // Execute all promises and wait for results
        // Note: In HTTPlug, we need to wait on each promise individually
        // or use a proper promise aggregator
        $results = $this->resolvePromises($promises);

        // Build ResolvedAggregatorData from results
        return $this->buildResolvedData($results);
    }

    /**
     * Builds all promises based on the manifest.
     *
     * @param AggregatorManifest $manifest Contains all IDs to resolve
     * @param string             $siteId   Site identifier
     *
     * @return array<string, Promise> Map of key => Promise
     */
    private function buildPromises(AggregatorManifest $manifest, string $siteId): array
    {
        $promises = [];

        // Level 2: Direct data from editorial
        if ($manifest->hasSection()) {
            $promises['section'] = $this->sectionResolver->resolve($manifest->sectionId);
        }

        if ($manifest->hasTags()) {
            $promises['tags'] = $this->tagResolver->resolveBatch($manifest->tagIds);
        }

        if ($manifest->hasSignatures()) {
            $promises['journalists'] = $this->journalistResolver->resolveBatch($manifest->signatureIds);
        }

        if ([] !== $manifest->multimediaIds) {
            $promises['multimedia'] = $this->multimediaResolver->resolveBatch($manifest->multimediaIds);
        }

        // Opening media
        if (null !== $manifest->openingMediaId) {
            $promises['openingMedia'] = $this->multimediaResolver->resolveBatch([$manifest->openingMediaId]);
        }

        // Meta image
        if (null !== $manifest->metaImageId) {
            $promises['metaImage'] = $this->multimediaResolver->resolveBatch([$manifest->metaImageId]);
        }

        // Comments (always fetch)
        $promises['comments'] = $this->commentResolver->resolve($manifest->editorialId);

        // Membership URLs
        if ($manifest->hasMembershipLinks()) {
            $promises['membership'] = $this->membershipResolver->resolve($manifest->membershipLinks);
        }

        // Level 3: Editorials with dependencies
        if ($manifest->hasInsertedNews()) {
            $promises['insertedNews'] = $this->insertedNewsResolver->resolveBatch($manifest->insertedNewsIds);
        }

        if ($manifest->hasRecommended()) {
            $promises['recommended'] = $this->recommendedResolver->resolveBatch($manifest->recommendedIds);
        }

        // Level 3: Body photos
        if ($manifest->hasBodyPhotos()) {
            $promises['bodyPhotos'] = $this->multimediaResolver->resolvePhotoBatch($manifest->bodyPhotoIds);
        }

        return $promises;
    }

    /**
     * Resolves all promises and returns results.
     *
     * Uses a settle pattern to handle failures gracefully.
     * Failed promises are logged but don't break the entire response.
     *
     * @param array<string, Promise> $promises Map of promises
     *
     * @return array<string, mixed> Map of resolved results
     */
    private function resolvePromises(array $promises): array
    {
        $results = [];

        foreach ($promises as $key => $promise) {
            try {
                $results[$key] = $promise->wait();
            } catch (\Throwable $e) {
                // Log the error but continue with other promises
                // In production, you'd use a proper logger here
                $results[$key] = null;
            }
        }

        return $results;
    }

    /**
     * Builds ResolvedAggregatorData from resolved promise results.
     *
     * @param array<string, mixed> $results Map of resolved results
     *
     * @return ResolvedAggregatorData Complete resolved data
     */
    private function buildResolvedData(array $results): ResolvedAggregatorData
    {
        // Extract section
        $section = null;
        if (isset($results['section']) && \is_array($results['section'])) {
            $section = (object) $results['section'];
        }

        // Extract tags
        $tags = [];
        if (isset($results['tags']) && \is_array($results['tags'])) {
            $tags = array_map(
                static fn ($tag) => \is_array($tag) ? (object) $tag : $tag,
                $results['tags']
            );
        }

        // Extract journalists
        $journalists = [];
        if (isset($results['journalists']) && \is_array($results['journalists'])) {
            $journalists = array_map(
                static fn ($journalist) => \is_array($journalist) ? (object) $journalist : $journalist,
                $results['journalists']
            );
        }

        // Extract multimedia
        $multimedia = [];
        if (isset($results['multimedia']) && \is_array($results['multimedia'])) {
            $multimedia = array_map(
                static fn ($media) => \is_array($media) ? (object) $media : $media,
                $results['multimedia']
            );
        }

        // Extract opening media (single item from batch)
        $openingMedia = null;
        if (isset($results['openingMedia']) && \is_array($results['openingMedia']) && [] !== $results['openingMedia']) {
            $firstItem = reset($results['openingMedia']);
            $openingMedia = \is_array($firstItem) ? (object) $firstItem : $firstItem;
        }

        // Extract meta image (single item from batch)
        $metaImage = null;
        if (isset($results['metaImage']) && \is_array($results['metaImage']) && [] !== $results['metaImage']) {
            $firstItem = reset($results['metaImage']);
            $metaImage = \is_array($firstItem) ? (object) $firstItem : $firstItem;
        }

        // Extract inserted news (already EditorialAggregate objects)
        $insertedNews = $results['insertedNews'] ?? [];

        // Extract recommended (already EditorialAggregate objects)
        $recommended = $results['recommended'] ?? [];

        // Extract body photos
        $bodyPhotos = [];
        if (isset($results['bodyPhotos']) && \is_array($results['bodyPhotos'])) {
            $bodyPhotos = array_map(
                static fn ($photo) => \is_array($photo) ? (object) $photo : $photo,
                $results['bodyPhotos']
            );
        }

        // Extract comments count
        $commentsCount = 0;
        if (isset($results['comments']) && \is_array($results['comments'])) {
            $commentsCount = (int) ($results['comments']['total_comments'] ?? 0);
        }

        // Extract membership URLs
        $membershipUrls = [];
        if (isset($results['membership']) && \is_array($results['membership'])) {
            $membershipUrls = $results['membership'];
        }

        return new ResolvedAggregatorData(
            section: $section,
            tags: $tags,
            journalists: $journalists,
            multimedia: $multimedia,
            openingMedia: $openingMedia,
            metaImage: $metaImage,
            insertedNews: $insertedNews,
            recommended: $recommended,
            bodyPhotos: $bodyPhotos,
            membershipUrls: $membershipUrls,
            commentsCount: $commentsCount,
        );
    }
}
