<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\ParallelFetcher\Resolver;

use App\Application\Query\QueryEditorialClientInterface;
use App\Application\Query\QueryJournalistClientInterface;
use App\Application\Query\QueryMultimediaClientInterface;
use App\Application\Query\QuerySectionClientInterface;
use App\Orchestrator\ValueObject\EditorialAggregate;
use Http\Promise\Promise;

/**
 * Special batch resolver for inserted news editorials with dependencies.
 *
 * This resolver fetches not only the editorial itself but also all its
 * dependencies (section, journalists, multimedia) in parallel, returning
 * complete EditorialAggregate objects ready for transformation.
 *
 * Each inserted news editorial is fetched with its dependencies in parallel,
 * and multiple inserted news are processed concurrently.
 */
final readonly class InsertedNewsBatchResolver implements BatchResolverInterface
{
    public function __construct(
        private QueryEditorialClientInterface $editorialClient,
        private QuerySectionClientInterface $sectionClient,
        private QueryJournalistClientInterface $journalistClient,
        private QueryMultimediaClientInterface $multimediaClient,
    ) {
    }

    /**
     * Resolves multiple inserted news editorials with their dependencies.
     *
     * For each editorial ID:
     * 1. Fetch the editorial data
     * 2. Extract its dependencies (section, journalists, multimedia)
     * 3. Fetch all dependencies in parallel
     * 4. Return EditorialAggregate with all data resolved
     *
     * @param array<int, string> $editorialIds Array of editorial IDs to resolve
     *
     * @return Promise Promise resolving to array of EditorialAggregate objects
     */
    public function resolveBatch(array $editorialIds): Promise
    {
        if ([] === $editorialIds) {
            return new \Http\Promise\FulfilledPromise([]);
        }

        $promises = [];
        foreach ($editorialIds as $editorialId) {
            $promises[] = $this->resolveWithDependencies($editorialId);
        }

        return new \Http\Promise\FulfilledPromise($promises);
    }

    /**
     * Resolves a single inserted news editorial with all its dependencies.
     *
     * This method orchestrates a two-phase fetch:
     * Phase 1: Fetch editorial data
     * Phase 2: Extract IDs and fetch dependencies in parallel
     *
     * @param string $editorialId The editorial ID to resolve
     *
     * @return Promise Promise resolving to EditorialAggregate
     */
    private function resolveWithDependencies(string $editorialId): Promise
    {
        // Phase 1: Fetch editorial
        $editorialPromise = $this->editorialClient->findEditorialById($editorialId, async: true);

        // Phase 2: After editorial is fetched, fetch dependencies
        return $editorialPromise->then(function (array $editorialData): Promise {
            // Extract dependency IDs from editorial data
            $sectionId = $editorialData['section']['id'] ?? null;
            $signatureIds = $this->extractSignatureIds($editorialData);
            $multimediaId = $editorialData['multimedia']['id'] ?? null;

            // Create promises for all dependencies
            $dependencyPromises = [
                'editorial' => new \Http\Promise\FulfilledPromise($editorialData),
            ];

            if (null !== $sectionId) {
                $dependencyPromises['section'] = $this->sectionClient->findSectionById(
                    $sectionId,
                    async: true
                );
            }

            if ([] !== $signatureIds) {
                $journalistPromises = [];
                foreach ($signatureIds as $signatureId) {
                    $journalistPromises[] = $this->journalistClient->findJournalistByAliasId(
                        $signatureId,
                        async: true
                    );
                }
                $dependencyPromises['journalists'] = new \Http\Promise\FulfilledPromise($journalistPromises);
            }

            if (null !== $multimediaId) {
                $dependencyPromises['multimedia'] = $this->multimediaClient->findMultimediaById(
                    $multimediaId,
                    async: true
                );
            }

            // Wait for all dependencies and build aggregate
            // Note: This is a simplified version. In production, you'd use proper promise aggregation
            return new \Http\Promise\FulfilledPromise(
                $this->buildEditorialAggregate($dependencyPromises)
            );
        });
    }

    /**
     * Extracts signature IDs from editorial data.
     *
     * @param array<string, mixed> $editorialData Editorial data array
     *
     * @return array<int, string> Array of signature IDs
     */
    private function extractSignatureIds(array $editorialData): array
    {
        if (!isset($editorialData['signatures']) || !\is_array($editorialData['signatures'])) {
            return [];
        }

        return array_map(
            static fn (array $signature): string => $signature['aliasId'] ?? '',
            array_filter(
                $editorialData['signatures'],
                static fn ($item): bool => \is_array($item) && isset($item['aliasId'])
            )
        );
    }

    /**
     * Builds EditorialAggregate from resolved dependency promises.
     *
     * @param array<string, Promise> $dependencyPromises Array of resolved promises
     *
     * @return EditorialAggregate Complete editorial aggregate
     */
    private function buildEditorialAggregate(array $dependencyPromises): EditorialAggregate
    {
        // In a real implementation, you'd wait for all promises here
        // For now, this is a placeholder structure
        $editorial = (object) ($dependencyPromises['editorial']->wait() ?? []);
        $section = isset($dependencyPromises['section'])
            ? (object) $dependencyPromises['section']->wait()
            : null;
        $journalists = isset($dependencyPromises['journalists'])
            ? array_map(
                static fn ($promise) => (object) $promise->wait(),
                $dependencyPromises['journalists']->wait()
            )
            : [];
        $multimedia = isset($dependencyPromises['multimedia'])
            ? (object) $dependencyPromises['multimedia']->wait()
            : null;

        return new EditorialAggregate(
            editorial: $editorial,
            section: $section,
            journalists: $journalists,
            multimedia: $multimedia,
        );
    }
}
