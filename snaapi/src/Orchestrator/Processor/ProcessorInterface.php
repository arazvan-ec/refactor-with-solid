<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Processor;

use Ec\Editorial\Domain\Model\Editorial;

/**
 * Interface for specialized processors that handle specific aspects of editorial orchestration.
 *
 * This interface follows the Single Responsibility Principle (SRP) by extracting specific
 * processing logic from EditorialOrchestrator into dedicated processors.
 *
 * Each processor:
 * - Handles one specific concern (inserted news, multimedia, membership links, etc.)
 * - Can be composed via dependency injection
 * - Supports conditional execution via the supports() method
 * - Returns processed data that can be merged into the final response
 *
 * @author SNAAPI Refactoring Team
 */
interface ProcessorInterface
{
    /**
     * Process a specific aspect of the editorial and return the processed data.
     *
     * @param Editorial $editorial The editorial to process
     * @param array<string, mixed> $context Additional context data (sections, tags, multimedia, etc.)
     *
     * @return array<string, mixed> Processed data to be merged into the response
     *
     * @throws \Throwable When processing fails critically
     */
    public function process(Editorial $editorial, array $context): array;

    /**
     * Determine if this processor should handle the given editorial.
     *
     * This method allows processors to opt-in/opt-out based on editorial properties
     * (e.g., only process if editorial has inserted news, or specific editorial types).
     *
     * @param Editorial $editorial The editorial to check
     *
     * @return bool True if this processor should handle the editorial
     */
    public function supports(Editorial $editorial): bool;
}
