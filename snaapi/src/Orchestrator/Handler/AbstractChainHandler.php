<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Handler;

use App\Orchestrator\Exceptions\DuplicateChainInOrchestratorHandlerException;
use App\Orchestrator\Exceptions\OrchestratorTypeNotExistException;

/**
 * Template Method Pattern implementation for Chain of Responsibility handlers.
 *
 * This abstract class defines the skeleton of the chain handler algorithm:
 * 1. Identify the type from input (hook method)
 * 2. Check if handler can handle this type
 * 3. Execute the appropriate orchestrator (hook method)
 *
 * DRY Principle: Eliminates 95% code duplication between OrchestratorChainHandler
 * and MultimediaOrchestratorHandler by extracting common logic.
 *
 * Open/Closed Principle: Open for extension (subclasses implement hooks),
 * closed for modification (template method is final).
 *
 * @template TOrchestrator of object
 *
 * @author SOLID Refactoring Team
 */
abstract class AbstractChainHandler
{
    /**
     * Registry of orchestrators indexed by their type.
     *
     * @var array<string, TOrchestrator>
     */
    protected array $orchestrators = [];

    /**
     * Template Method: Defines the algorithm skeleton for handling requests.
     *
     * This method is final to prevent subclasses from modifying the algorithm structure.
     * Subclasses customize behavior via hook methods: identifyType() and executeOrchestrator().
     *
     * Algorithm steps:
     * 1. Identify the orchestrator type from input
     * 2. Verify orchestrator exists for this type
     * 3. Delegate execution to the appropriate orchestrator
     *
     * @param mixed $input The input to process (Request, Multimedia, etc.)
     *
     * @return array<string, mixed> The orchestrator execution result
     *
     * @throws OrchestratorTypeNotExistException When no orchestrator handles the identified type
     */
    final public function handle(mixed $input): array
    {
        $type = $this->identifyType($input);

        if (!$this->canHandle($type)) {
            throw new OrchestratorTypeNotExistException(
                \sprintf('Orchestrator %s not exist', $type)
            );
        }

        return $this->executeOrchestrator($type, $input);
    }

    /**
     * Checks if this handler can process the given orchestrator type.
     *
     * @param string $type The orchestrator type to check
     *
     * @return bool True if an orchestrator is registered for this type
     */
    final public function canHandle(string $type): bool
    {
        return \array_key_exists($type, $this->orchestrators);
    }

    /**
     * Registers an orchestrator in the chain.
     *
     * This method enforces the uniqueness constraint: each orchestrator type
     * can only be registered once to prevent ambiguous routing.
     *
     * @param TOrchestrator $orchestrator The orchestrator to register
     *
     * @return static Fluent interface for chaining multiple additions
     *
     * @throws DuplicateChainInOrchestratorHandlerException When attempting to register
     *                                                       an orchestrator for an already registered type
     */
    final public function addOrchestrator(object $orchestrator): static
    {
        $key = $this->getOrchestratorKey($orchestrator);

        if (isset($this->orchestrators[$key])) {
            throw new DuplicateChainInOrchestratorHandlerException(
                \sprintf('%s orchestrator duplicate.', $key)
            );
        }

        $this->orchestrators[$key] = $orchestrator;

        return $this;
    }

    /**
     * Hook Method: Identifies the orchestrator type from the input.
     *
     * Subclasses must implement this method to define how the type is extracted
     * from the specific input object they handle.
     *
     * Examples:
     * - For Request: extract from route parameters or query string
     * - For Multimedia: call $multimedia->type()
     * - For generic: delegate to OrchestratorTypeIdentifierInterface
     *
     * @param mixed $input The input object to analyze
     *
     * @return string The identified orchestrator type
     */
    abstract protected function identifyType(mixed $input): string;

    /**
     * Hook Method: Executes the orchestrator for the given type and input.
     *
     * Subclasses must implement this method to define how to invoke the orchestrator's
     * execution method with the appropriate input type.
     *
     * Note: This method is called after verifying the orchestrator exists,
     * so $this->orchestrators[$type] is guaranteed to be set.
     *
     * @param string $type  The orchestrator type (used as array key)
     * @param mixed  $input The input to pass to the orchestrator
     *
     * @return array<string, mixed> The execution result from the orchestrator
     */
    abstract protected function executeOrchestrator(string $type, mixed $input): array;

    /**
     * Hook Method: Extracts the registration key from an orchestrator.
     *
     * Subclasses must implement this method to define how to obtain the type identifier
     * that will be used as the array key for storing the orchestrator.
     *
     * Typically, this calls $orchestrator->canOrchestrate(), but subclasses may
     * implement alternative strategies if needed.
     *
     * @param TOrchestrator $orchestrator The orchestrator to extract the key from
     *
     * @return string The type key for indexing this orchestrator
     */
    abstract protected function getOrchestratorKey(object $orchestrator): string;
}
