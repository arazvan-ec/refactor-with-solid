<?php

declare(strict_types=1);

namespace Snaapi\Infrastructure\Service\Image;

/**
 * Interface for image size configuration.
 *
 * This interface provides access to predefined image sizes and aspect ratios
 * used throughout the application. It follows the Interface Segregation Principle (ISP)
 * by exposing only configuration-related methods without coupling to processing logic.
 *
 * @author SNAAPI Team
 */
interface ImageSizeConfigurationInterface
{
    /**
     * Returns all configured image sizes organized by aspect ratio.
     *
     * The structure should be:
     * [
     *   'aspectRatio' => [
     *     'viewport' => ['width' => '640', 'height' => '360'],
     *     ...
     *   ],
     *   ...
     * ]
     *
     * Example:
     * [
     *   '16:9' => [
     *     '1440w' => ['width' => '1440', 'height' => '810'],
     *     '640w' => ['width' => '640', 'height' => '360'],
     *   ],
     *   '4:3' => [
     *     '1440w' => ['width' => '1440', 'height' => '1080'],
     *   ],
     * ]
     *
     * @return array<string, array<string, array<string, string>>> All size configurations by aspect ratio
     */
    public function getSizes(): array;

    /**
     * Returns image sizes for a specific aspect ratio.
     *
     * @param string $aspectRatio The aspect ratio (e.g., '16:9', '4:3', '1:1', '3:4', '3:2', '2:3')
     *
     * @return array<string, array<string, string>> Size configurations for the specified aspect ratio:
     *                                              [
     *                                                '1440w' => ['width' => '1440', 'height' => '810'],
     *                                                '640w' => ['width' => '640', 'height' => '360'],
     *                                              ]
     *
     * @throws \InvalidArgumentException If the aspect ratio is not configured
     */
    public function getSizesByAspectRatio(string $aspectRatio): array;

    /**
     * Returns all available aspect ratios.
     *
     * @return array<int, string> List of aspect ratio strings (e.g., ['16:9', '4:3', '1:1', '3:4', '3:2', '2:3'])
     */
    public function getAspectRatios(): array;

    /**
     * Returns a specific size configuration by aspect ratio and viewport.
     *
     * @param string $aspectRatio The aspect ratio (e.g., '16:9', '4:3')
     * @param string $viewport The viewport size identifier (e.g., '1440w', '640w')
     *
     * @return array<string, string> The size configuration:
     *                               [
     *                                 'width' => '1440',
     *                                 'height' => '810'
     *                               ]
     *
     * @throws \InvalidArgumentException If the aspect ratio or viewport is not configured
     */
    public function getSize(string $aspectRatio, string $viewport): array;

    /**
     * Checks if a specific aspect ratio is configured.
     *
     * @param string $aspectRatio The aspect ratio to check (e.g., '16:9')
     *
     * @return bool True if the aspect ratio is configured, false otherwise
     */
    public function hasAspectRatio(string $aspectRatio): bool;

    /**
     * Checks if a specific viewport exists for a given aspect ratio.
     *
     * @param string $aspectRatio The aspect ratio (e.g., '16:9')
     * @param string $viewport The viewport size identifier (e.g., '1440w')
     *
     * @return bool True if the viewport exists for the aspect ratio, false otherwise
     */
    public function hasViewport(string $aspectRatio, string $viewport): bool;
}
