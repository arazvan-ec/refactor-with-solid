<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Orchestrator\Handler;

use App\Orchestrator\Exceptions\OrchestratorTypeNotExistException;

/**
 * Generic implementation of Chain Handler using Strategy pattern for type identification.
 *
 * This class demonstrates the power of combining Template Method with Strategy pattern:
 * - Template Method (from AbstractChainHandler): defines the algorithm skeleton
 * - Strategy (OrchestratorTypeIdentifierInterface): allows pluggable type identification logic
 *
 * Dependency Inversion Principle: Depends on OrchestratorTypeIdentifierInterface abstraction,
 * not on concrete type identification implementations.
 *
 * Single Responsibility Principle: Only responsible for coordinating the chain,
 * delegates type identification to the injected strategy.
 *
 * Usage example:
 * ```php
 * $handler = new GenericChainHandler(new RequestTypeIdentifier());
 * $handler->addOrchestrator($articleOrchestrator);
 * $handler->addOrchestrator($videoOrchestrator);
 * $result = $handler->handle($request);
 * ```
 *
 * @template TOrchestrator of object
 * @extends AbstractChainHandler<TOrchestrator>
 *
 * @author SOLID Refactoring Team
 */
final class GenericChainHandler extends AbstractChainHandler
{
    /**
     * @param OrchestratorTypeIdentifierInterface $typeIdentifier Strategy for identifying orchestrator types from input
     */
    public function __construct(
        private readonly OrchestratorTypeIdentifierInterface $typeIdentifier
    ) {
    }

    /**
     * Identifies the orchestrator type by delegating to the injected type identifier strategy.
     *
     * This implementation follows the Strategy pattern, allowing different type identification
     * algorithms to be used without modifying this class (Open/Closed Principle).
     *
     * @param mixed $input The input to identify (type depends on the identifier strategy)
     *
     * @return string The identified orchestrator type
     *
     * @throws OrchestratorTypeNotExistException When the type identifier cannot determine the type
     */
    protected function identifyType(mixed $input): string
    {
        $type = $this->typeIdentifier->identify($input);

        if ($type === null) {
            throw new OrchestratorTypeNotExistException(
                \sprintf(
                    'Cannot identify orchestrator type from input of type %s',
                    \get_debug_type($input)
                )
            );
        }

        return $type;
    }

    /**
     * Executes the orchestrator by calling its execute() method.
     *
     * This implementation assumes all orchestrators follow the convention of having
     * an execute() method that accepts the input and returns an array.
     *
     * Type safety note: PHP's type system doesn't allow enforcing the execute() method
     * signature at compile time with this generic approach. Runtime checks or more
     * specific implementations may be needed for stricter type safety.
     *
     * @param string $type  The orchestrator type (array key)
     * @param mixed  $input The input to pass to the orchestrator's execute method
     *
     * @return array<string, mixed> The result from the orchestrator
     *
     * @throws \BadMethodCallException If the orchestrator doesn't have an execute() method
     */
    protected function executeOrchestrator(string $type, mixed $input): array
    {
        $orchestrator = $this->orchestrators[$type];

        if (!\method_exists($orchestrator, 'execute')) {
            throw new \BadMethodCallException(
                \sprintf(
                    'Orchestrator of type %s (class %s) must have an execute() method',
                    $type,
                    $orchestrator::class
                )
            );
        }

        return $orchestrator->execute($input);
    }

    /**
     * Extracts the registration key by calling canOrchestrate() on the orchestrator.
     *
     * This follows the existing convention where orchestrators declare their type
     * via the canOrchestrate() method.
     *
     * @param object $orchestrator The orchestrator to extract the key from
     *
     * @return string The type key for this orchestrator
     *
     * @throws \BadMethodCallException If the orchestrator doesn't have a canOrchestrate() method
     */
    protected function getOrchestratorKey(object $orchestrator): string
    {
        if (!\method_exists($orchestrator, 'canOrchestrate')) {
            throw new \BadMethodCallException(
                \sprintf(
                    'Orchestrator of class %s must have a canOrchestrate() method',
                    $orchestrator::class
                )
            );
        }

        $key = $orchestrator->canOrchestrate();

        if (!\is_string($key) || $key === '') {
            throw new \InvalidArgumentException(
                \sprintf(
                    'canOrchestrate() must return a non-empty string, got %s',
                    \get_debug_type($key)
                )
            );
        }

        return $key;
    }
}
