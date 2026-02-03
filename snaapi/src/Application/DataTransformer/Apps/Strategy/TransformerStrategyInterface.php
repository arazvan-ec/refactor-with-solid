<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Apps\Strategy;

/**
 * Strategy interface for data transformation operations.
 *
 * Each strategy is responsible for transforming a specific type of data
 * following the Single Responsibility Principle. Strategies can be composed
 * to build complex transformation pipelines.
 *
 * @author Claude AI <assistant@anthropic.com>
 */
interface TransformerStrategyInterface
{
    /**
     * Transforms the given data according to the strategy's logic.
     *
     * @param array<string, mixed> $data The data to transform
     *
     * @return array<string, mixed> The transformed data
     */
    public function transform(array $data): array;

    /**
     * Determines if this strategy supports the given transformation type.
     *
     * This method allows the composite transformer to dynamically select
     * which strategies to apply based on the type of transformation needed.
     *
     * @param string $type The type identifier (e.g., 'section', 'tag', 'hierarchy')
     *
     * @return bool True if this strategy supports the type, false otherwise
     */
    public function supports(string $type): bool;
}
