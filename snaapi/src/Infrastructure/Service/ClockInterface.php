<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Infrastructure\Service;

use DateTimeImmutable;

/**
 * Abstraction for obtaining current date and time.
 *
 * This interface allows injecting a fixed time in tests, replacing the
 * non-deterministic 'new DateTimeImmutable()' with a controllable implementation.
 *
 * Follows the Dependency Inversion Principle by allowing time-dependent
 * logic to depend on this abstraction instead of the concrete DateTimeImmutable class.
 *
 * Benefits:
 * - Deterministic tests: inject a fixed time to test time-sensitive logic
 * - Time travel: simulate past or future dates in tests
 * - No global state manipulation required
 *
 * @author SOLID Refactoring
 */
interface ClockInterface
{
    /**
     * Returns the current date and time.
     *
     * In production: returns the actual current timestamp.
     * In tests: can return a fixed time for deterministic testing.
     *
     * @return DateTimeImmutable The current date and time
     */
    public function now(): DateTimeImmutable;

    /**
     * Returns the current date at midnight (00:00:00).
     *
     * Useful for date comparisons without time component.
     * Equivalent to now()->setTime(0, 0, 0).
     *
     * In production: returns today's date at 00:00:00.
     * In tests: can return a fixed date for deterministic testing.
     *
     * @return DateTimeImmutable The current date at 00:00:00
     */
    public function today(): DateTimeImmutable;
}
