<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Infrastructure\Service;

use App\Infrastructure\Enum\SitesEnum;

/**
 * Service responsible for generating URLs with specific format patterns.
 *
 * This service follows SOLID principles:
 * - Single Responsibility Principle (SRP): Exclusively responsible for URL generation
 * - Open/Closed Principle (OCP): Open for extension through inheritance, closed for modification
 * - Liskov Substitution Principle (LSP): Implements UrlGeneratorServiceInterface contract
 * - Interface Segregation Principle (ISP): Small, focused interface with single method
 * - Dependency Inversion Principle (DIP): Depends on SitesEnum abstraction
 *
 * Benefits over UrlGeneratorTrait:
 * - Testability: Can be easily mocked and tested in isolation
 * - No state pollution: Extension is immutable after construction
 * - Explicit dependencies: Dependencies are clear in constructor
 * - Single responsibility: Only generates URLs, no state management
 *
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
final readonly class UrlGeneratorService implements UrlGeneratorServiceInterface
{
    /**
     * @param string $extension The domain extension (e.g., 'com', 'es') injected from configuration
     */
    public function __construct(
        private string $extension,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * Implementation details:
     * - Uses sprintf() for string formatting
     * - Resolves hostname from siteId using SitesEnum::getHostnameById()
     * - Trims leading/trailing slashes from urlPath
     * - All parameters are validated by PHP's type system (strict_types=1)
     *
     * Example:
     * <code>
     * $service = new UrlGeneratorService('com');
     * $url = $service->generateUrl(
     *     'https://%s.%s.%s/%s',
     *     'www',
     *     '1',
     *     '/editorials/article-slug/'
     * );
     * // Returns: 'https://www.elconfidencial.com/editorials/article-slug'
     * </code>
     */
    public function generateUrl(string $format, string $subdomain, string $siteId, string $urlPath): string
    {
        return \sprintf(
            $format,
            $subdomain,
            SitesEnum::getHostnameById($siteId),
            $this->extension,
            trim($urlPath, '/')
        );
    }
}
