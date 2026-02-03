<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Infrastructure\Service;

/**
 * Interface for URL generation service.
 *
 * This interface defines the contract for generating URLs with specific format patterns.
 * It follows the Single Responsibility Principle (SRP) by focusing exclusively on URL generation.
 *
 * Usage example:
 * <code>
 * $url = $urlGenerator->generateUrl(
 *     'https://%s.%s.%s/%s',
 *     'www',
 *     '1',
 *     'editorials/2024-01-15/article-slug/123456'
 * );
 * // Result: https://www.elconfidencial.com/editorials/2024-01-15/article-slug/123456
 * </code>
 *
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
interface UrlGeneratorServiceInterface
{
    /**
     * Generates a URL using the provided format and parameters.
     *
     * The format string uses sprintf-style placeholders that will be replaced with:
     * 1. %s - subdomain (e.g., 'www', 'blog')
     * 2. %s - hostname (resolved from siteId via SitesEnum)
     * 3. %s - extension (e.g., 'com', 'es')
     * 4. %s - urlPath (trimmed of leading/trailing slashes)
     *
     * @param string $format    The URL format string with sprintf placeholders (e.g., 'https://%s.%s.%s/%s')
     * @param string $subdomain The subdomain to use (e.g., 'www', 'blog')
     * @param string $siteId    The site identifier used to resolve the hostname (e.g., '1', '2', '5')
     * @param string $urlPath   The URL path to append (will be trimmed of slashes)
     *
     * @return string The generated URL
     */
    public function generateUrl(string $format, string $subdomain, string $siteId, string $urlPath): string;
}
