<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Composer;

use App\Orchestrator\ValueObject\EditorialResponse;
use App\Orchestrator\ValueObject\ResolvedAggregatorData;
use Ec\Editorial\Domain\Model\Editorial;

/**
 * Interface for composing the final editorial response.
 *
 * This interface defines the contract for Phase 4 of the pipeline:
 * transforming resolved aggregator data into a JSON-ready response.
 *
 * Following SOLID principles:
 * - Interface Segregation (ISP): Single focused interface
 * - Dependency Inversion (DIP): Depend on abstractions
 *
 * @author SNAAPI Refactoring Team
 */
interface EditorialResponseComposerInterface
{
    /**
     * Composes the final editorial response from editorial and resolved data.
     *
     * Delegates transformation to specialized transformers, each with a single responsibility.
     * All transformers receive only the data they need to perform their specific transformation.
     *
     * @param Editorial $editorial The main editorial entity
     * @param ResolvedAggregatorData $data All resolved aggregator data from parallel fetching
     * @param string $siteId The site identifier for URL generation (e.g., '1', '2', '5')
     *
     * @return EditorialResponse The complete response ready for JSON serialization
     */
    public function compose(
        Editorial $editorial,
        ResolvedAggregatorData $data,
        string $siteId
    ): EditorialResponse;
}
