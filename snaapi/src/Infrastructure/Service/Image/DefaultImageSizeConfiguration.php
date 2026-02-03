<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Image;

use Snaapi\Infrastructure\Service\Image\ImageSizeConfigurationInterface;

/**
 * Default implementation of image size configuration.
 *
 * Provides predefined responsive image sizes for different aspect ratios.
 * This class encapsulates the SOLID principle of Single Responsibility by
 * only managing image size configuration data.
 *
 * Supported aspect ratios:
 * - 16:9 (widescreen landscape)
 * - 4:3 (standard landscape)
 * - 3:2 (photography landscape)
 * - 1:1 (square)
 * - 3:4 (portrait)
 * - 2:3 (photography portrait)
 *
 * @author SNAAPI Team
 */
final class DefaultImageSizeConfiguration implements ImageSizeConfigurationInterface
{
    private const WIDTH = 'width';
    private const HEIGHT = 'height';

    /**
     * Image sizes organized by aspect ratio and viewport breakpoint.
     *
     * @var array<string, array<string, array<string, string>>>
     */
    private const SIZES_RELATIONS = [
        '16:9' => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '810'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '675'],
            '996w' => [self::WIDTH => '996', self::HEIGHT => '560'],
            '640w' => [self::WIDTH => '640', self::HEIGHT => '360'],
            '390w' => [self::WIDTH => '390', self::HEIGHT => '219'],
            '568w' => [self::WIDTH => '568', self::HEIGHT => '320'],
            '382w' => [self::WIDTH => '382', self::HEIGHT => '215'],
            '328w' => [self::WIDTH => '328', self::HEIGHT => '185'],
        ],
        '3:4' => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '1920'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '1600'],
            '996w' => [self::WIDTH => '996', self::HEIGHT => '1328'],
            '560w' => [self::WIDTH => '560', self::HEIGHT => '747'],
            '390w' => [self::WIDTH => '390', self::HEIGHT => '520'],
            '568w' => [self::WIDTH => '568', self::HEIGHT => '757'],
            '382w' => [self::WIDTH => '382', self::HEIGHT => '509'],
            '328w' => [self::WIDTH => '328', self::HEIGHT => '437'],
        ],
        '1:1' => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '1440'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '1200'],
            '996w' => [self::WIDTH => '996', self::HEIGHT => '996'],
            '560w' => [self::WIDTH => '560', self::HEIGHT => '560'],
            '390w' => [self::WIDTH => '390', self::HEIGHT => '390'],
            '568w' => [self::WIDTH => '568', self::HEIGHT => '568'],
            '382w' => [self::WIDTH => '382', self::HEIGHT => '382'],
            '328w' => [self::WIDTH => '328', self::HEIGHT => '328'],
        ],
        '4:3' => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '1080'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '900'],
            '996w' => [self::WIDTH => '996',  self::HEIGHT => '747'],
            '560w' => [self::WIDTH => '560',  self::HEIGHT => '420'],
            '390w' => [self::WIDTH => '390',  self::HEIGHT => '292'],
            '568w' => [self::WIDTH => '568',  self::HEIGHT => '426'],
            '382w' => [self::WIDTH => '382',  self::HEIGHT => '286'],
            '328w' => [self::WIDTH => '328',  self::HEIGHT => '246'],
        ],
        '3:2' => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '960'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '800'],
            '996w' => [self::WIDTH => '996', self::HEIGHT => '664'],
            '640w' => [self::WIDTH => '640', self::HEIGHT => '427'],
            '390w' => [self::WIDTH => '390', self::HEIGHT => '260'],
            '568w' => [self::WIDTH => '568', self::HEIGHT => '379'],
            '382w' => [self::WIDTH => '382', self::HEIGHT => '254'],
            '328w' => [self::WIDTH => '328', self::HEIGHT => '219'],
        ],
        '2:3' => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '2160'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '1800'],
            '996w' => [self::WIDTH => '996', self::HEIGHT => '1494'],
            '560w' => [self::WIDTH => '560', self::HEIGHT => '840'],
            '390w' => [self::WIDTH => '390', self::HEIGHT => '585'],
            '568w' => [self::WIDTH => '568', self::HEIGHT => '852'],
            '382w' => [self::WIDTH => '382', self::HEIGHT => '573'],
            '328w' => [self::WIDTH => '328', self::HEIGHT => '492'],
        ],
    ];

    /**
     * Returns all configured image sizes organized by aspect ratio.
     *
     * @return array<string, array<string, array<string, string>>> All size configurations
     */
    public function getSizes(): array
    {
        return self::SIZES_RELATIONS;
    }

    /**
     * Returns image sizes for a specific aspect ratio.
     *
     * @param string $aspectRatio The aspect ratio (e.g., '16:9', '4:3')
     *
     * @return array<string, array<string, string>> Size configurations for the aspect ratio
     *
     * @throws \InvalidArgumentException If the aspect ratio is not configured
     */
    public function getSizesByAspectRatio(string $aspectRatio): array
    {
        if (!$this->hasAspectRatio($aspectRatio)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Aspect ratio "%s" is not configured. Available ratios: %s',
                    $aspectRatio,
                    implode(', ', $this->getAspectRatios())
                )
            );
        }

        return self::SIZES_RELATIONS[$aspectRatio];
    }

    /**
     * Returns all available aspect ratios.
     *
     * @return array<int, string> List of aspect ratio strings
     */
    public function getAspectRatios(): array
    {
        return array_keys(self::SIZES_RELATIONS);
    }

    /**
     * Returns a specific size configuration by aspect ratio and viewport.
     *
     * @param string $aspectRatio The aspect ratio (e.g., '16:9')
     * @param string $viewport The viewport size identifier (e.g., '1440w')
     *
     * @return array<string, string> The size configuration with 'width' and 'height'
     *
     * @throws \InvalidArgumentException If the aspect ratio or viewport is not configured
     */
    public function getSize(string $aspectRatio, string $viewport): array
    {
        if (!$this->hasAspectRatio($aspectRatio)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Aspect ratio "%s" is not configured. Available ratios: %s',
                    $aspectRatio,
                    implode(', ', $this->getAspectRatios())
                )
            );
        }

        if (!$this->hasViewport($aspectRatio, $viewport)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Viewport "%s" is not configured for aspect ratio "%s". Available viewports: %s',
                    $viewport,
                    $aspectRatio,
                    implode(', ', array_keys(self::SIZES_RELATIONS[$aspectRatio]))
                )
            );
        }

        return self::SIZES_RELATIONS[$aspectRatio][$viewport];
    }

    /**
     * Checks if a specific aspect ratio is configured.
     *
     * @param string $aspectRatio The aspect ratio to check
     *
     * @return bool True if configured, false otherwise
     */
    public function hasAspectRatio(string $aspectRatio): bool
    {
        return isset(self::SIZES_RELATIONS[$aspectRatio]);
    }

    /**
     * Checks if a specific viewport exists for a given aspect ratio.
     *
     * @param string $aspectRatio The aspect ratio (e.g., '16:9')
     * @param string $viewport The viewport size identifier (e.g., '1440w')
     *
     * @return bool True if the viewport exists, false otherwise
     */
    public function hasViewport(string $aspectRatio, string $viewport): bool
    {
        return $this->hasAspectRatio($aspectRatio)
            && isset(self::SIZES_RELATIONS[$aspectRatio][$viewport]);
    }
}
