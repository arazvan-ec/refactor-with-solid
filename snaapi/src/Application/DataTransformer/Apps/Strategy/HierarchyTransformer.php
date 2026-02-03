<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Strategy;

use Ec\Section\Domain\Model\Section;

/**
 * Transforms Section hierarchy into a flat array structure.
 *
 * Responsible for:
 * - Building section parent hierarchy chains
 * - Reversing hierarchy order (child to root)
 * - Delegating section transformation to SectionTransformer
 *
 * This transformer uses recursion to traverse the section tree from
 * the current section up to the root, collecting all ancestor sections.
 *
 * @author Claude AI <assistant@anthropic.com>
 */
final class HierarchyTransformer implements TransformerStrategyInterface
{
    public function __construct(
        private readonly SectionTransformer $sectionTransformer,
    ) {
    }

    /**
     * Transforms a section hierarchy into a flattened array.
     *
     * Expected input data structure:
     * - 'section': Section - The starting section
     *
     * The result is ordered from root to current section (reversed).
     *
     * @param array<string, mixed> $data Must contain 'section' key with Section object
     *
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     url: string,
     *     encodeName: string
     * }> Array of transformed section data in hierarchical order
     */
    public function transform(array $data): array
    {
        if (!isset($data['section']) || !$data['section'] instanceof Section) {
            return [];
        }

        return $this->buildHierarchy($data['section']);
    }

    /**
     * Recursively builds the section hierarchy.
     *
     * @param Section $section The current section in the hierarchy
     * @param array<int, array{
     *     id: string,
     *     name: string,
     *     url: string,
     *     encodeName: string
     * }> $result Accumulated hierarchy data
     *
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     url: string,
     *     encodeName: string
     * }> The complete hierarchy from root to current section
     */
    private function buildHierarchy(Section $section, array $result = []): array
    {
        // Transform current section and add to result
        $transformedSection = $this->sectionTransformer->transform(['section' => $section]);
        $result[] = $transformedSection;

        // Base case: if no parent, reverse and return
        if (null === $section->parent()) {
            return array_reverse($result);
        }

        // Recursive case: process parent
        return $this->buildHierarchy($section->parent(), $result);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $type): bool
    {
        return $type === 'hierarchy';
    }
}
