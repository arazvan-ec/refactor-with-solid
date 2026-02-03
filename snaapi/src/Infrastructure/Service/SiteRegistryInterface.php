<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Infrastructure\Service;

/**
 * Interface for Site Registry service.
 *
 * This interface replaces the static SitesEnum with a proper abstraction
 * following the Dependency Inversion Principle (SOLID).
 * It allows different implementations (in-memory, database, cache, etc.)
 * and enables proper testing through dependency injection.
 *
 * @author Claude Code Assistant
 */
interface SiteRegistryInterface
{
    /**
     * Retrieves a site by its unique identifier.
     *
     * @param int $id The site ID (e.g., 1 for El Confidencial, 2 for Vanitatis, 5 for Alimente)
     *
     * @return array{
     *     id: int,
     *     code: string,
     *     hostname: string,
     *     encodename: string
     * } Site data structure
     *
     * @throws \InvalidArgumentException When site with given ID is not found
     */
    public function getSiteById(int $id): array;

    /**
     * Retrieves a site by its code name.
     *
     * @param string $code The site code (e.g., 'ELCONFIDENCIAL', 'VANITATIS', 'ALIMENTE')
     *
     * @return array{
     *     id: int,
     *     code: string,
     *     hostname: string,
     *     encodename: string
     * } Site data structure
     *
     * @throws \InvalidArgumentException When site with given code is not found
     */
    public function getSiteByCode(string $code): array;

    /**
     * Retrieves all registered sites.
     *
     * @return array<int, array{
     *     id: int,
     *     code: string,
     *     hostname: string,
     *     encodename: string
     * }> List of all sites with their data
     */
    public function getAllSites(): array;

    /**
     * Gets the hostname for a given site ID.
     *
     * This method provides backward compatibility with SitesEnum::getHostnameById().
     *
     * @param int $id The site ID
     *
     * @return string The hostname (e.g., 'elconfidencial', 'vanitatis.elconfidencial')
     *
     * @throws \InvalidArgumentException When site with given ID is not found
     */
    public function getHostnameById(int $id): string;

    /**
     * Gets the encoded name for a given site ID.
     *
     * This method provides backward compatibility with SitesEnum::getEncodenameById().
     *
     * @param int $id The site ID
     *
     * @return string The encoded name (e.g., 'el-confidencial', 'vanitatis', 'alimente')
     *
     * @throws \InvalidArgumentException When site with given ID is not found
     */
    public function getEncodenameById(int $id): string;

    /**
     * Checks if a site exists by its ID.
     *
     * @param int $id The site ID to check
     *
     * @return bool True if site exists, false otherwise
     */
    public function hasSite(int $id): bool;
}
