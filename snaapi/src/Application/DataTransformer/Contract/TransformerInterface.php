<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Application\DataTransformer\Contract;

/**
 * Base transformer interface following Interface Segregation Principle (ISP).
 *
 * This minimal interface defines the core transformation contract without
 * imposing unnecessary methods on implementers.
 *
 * @author SOLID Refactoring Team
 */
interface TransformerInterface
{
    /**
     * Transforms input data into a normalized array structure.
     *
     * @param mixed $data The data to transform (can be domain model, DTO, or primitive)
     *
     * @return array<string, mixed> The transformed data as an associative array
     */
    public function transform(mixed $data): array;
}
