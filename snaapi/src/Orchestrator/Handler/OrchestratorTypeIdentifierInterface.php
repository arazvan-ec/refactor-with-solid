<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Handler;

/**
 * Interface Segregation Principle (ISP) implementation.
 * Segregates the responsibility of identifying orchestrator types from input data.
 *
 * This interface allows different strategies for type identification:
 * - From Request objects (query params, attributes, etc.)
 * - From Domain objects (Multimedia::type(), etc.)
 * - From custom business logic
 *
 * @author SOLID Refactoring Team
 */
interface OrchestratorTypeIdentifierInterface
{
    /**
     * Identifies the orchestrator type from the given input.
     *
     * This method should inspect the input and determine which orchestrator
     * type should handle it. Returns null if the type cannot be determined.
     *
     * Examples:
     * - For Request: extract from query param, route attribute, or body
     * - For Multimedia: call $multimedia->type()
     * - For custom objects: implement domain-specific logic
     *
     * @param mixed $input The input to identify (Request, Multimedia, or other domain object)
     *
     * @return string|null The identified orchestrator type, or null if cannot be determined
     */
    public function identify(mixed $input): ?string;
}
