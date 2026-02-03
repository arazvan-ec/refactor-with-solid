<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Infrastructure\Service;

/**
 * Abstraction for environment checking.
 *
 * This interface allows testing by replacing real environment detection
 * with a mock implementation, following the Dependency Inversion Principle.
 *
 * Enables checking application environment (production, development, test)
 * without directly coupling to Symfony's kernel or $_ENV superglobals.
 *
 * @author SOLID Refactoring
 */
interface EnvironmentCheckerInterface
{
    /**
     * Checks if the current environment is production.
     *
     * @return bool True if running in production environment, false otherwise
     */
    public function isProduction(): bool;

    /**
     * Checks if the current environment is development.
     *
     * @return bool True if running in development environment, false otherwise
     */
    public function isDevelopment(): bool;

    /**
     * Checks if the current environment is test.
     *
     * @return bool True if running in test environment, false otherwise
     */
    public function isTest(): bool;

    /**
     * Gets the current environment name.
     *
     * Common values: 'prod', 'dev', 'test'
     * The exact value depends on the APP_ENV configuration.
     *
     * @return string The environment name (e.g., 'prod', 'dev', 'test')
     */
    public function getEnvironment(): string;
}
