<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Image;

use Ec\Editorial\Domain\Model\Body\AbstractPicture;

/**
 * Maps picture orientations to standard aspect ratios.
 *
 * This class has a single responsibility: converting domain-specific
 * orientation values into standardized aspect ratio strings.
 * It follows the Single Responsibility Principle by only handling
 * aspect ratio mapping logic.
 *
 * Supported mappings:
 * - ORIENTATION_LANDSCAPE_169 → 16:9
 * - ORIENTATION_LANDSCAPE → 4:3
 * - ORIENTATION_LANDSCAPE_3_2 → 3:2
 * - ORIENTATION_SQUARE → 1:1
 * - ORIENTATION_PORTRAIT → 3:4
 * - ORIENTATION_PORTRAIT_2_3 → 2:3
 *
 * @author SNAAPI Team
 */
final class AspectRatioMapper
{
    private const ASPECT_RATIO_16_9 = '16:9';
    private const ASPECT_RATIO_4_3 = '4:3';
    private const ASPECT_RATIO_3_2 = '3:2';
    private const ASPECT_RATIO_1_1 = '1:1';
    private const ASPECT_RATIO_3_4 = '3:4';
    private const ASPECT_RATIO_2_3 = '2:3';

    /**
     * Orientation to aspect ratio mapping table.
     *
     * @var array<string, string>
     */
    private const ORIENTATION_MAP = [
        AbstractPicture::ORIENTATION_SQUARE => self::ASPECT_RATIO_1_1,
        AbstractPicture::ORIENTATION_PORTRAIT => self::ASPECT_RATIO_3_4,
        AbstractPicture::ORIENTATION_LANDSCAPE => self::ASPECT_RATIO_4_3,
        AbstractPicture::ORIENTATION_LANDSCAPE_3_2 => self::ASPECT_RATIO_3_2,
        AbstractPicture::ORIENTATION_PORTRAIT_2_3 => self::ASPECT_RATIO_2_3,
    ];

    /**
     * Maps a picture orientation to its corresponding aspect ratio.
     *
     * @param string $orientation Picture orientation constant from AbstractPicture
     *
     * @return string The aspect ratio string (e.g., '16:9', '4:3', '1:1')
     */
    public function mapToStandard(string $orientation): string
    {
        return self::ORIENTATION_MAP[$orientation] ?? self::ASPECT_RATIO_16_9;
    }

    /**
     * Calculates the numeric aspect ratio from width and height.
     *
     * @param int $width Image width in pixels
     * @param int $height Image height in pixels
     *
     * @return float The calculated aspect ratio (width / height)
     *
     * @throws \InvalidArgumentException If width or height is zero or negative
     */
    public function calculateRatio(int $width, int $height): float
    {
        if ($width <= 0 || $height <= 0) {
            throw new \InvalidArgumentException(
                sprintf('Width and height must be positive integers. Got width: %d, height: %d', $width, $height)
            );
        }

        return $width / $height;
    }

    /**
     * Finds the closest standard aspect ratio for given dimensions.
     *
     * This method calculates the numeric ratio and finds the closest match
     * from the standard ratios (16:9, 4:3, 3:2, 1:1, 3:4, 2:3).
     *
     * @param int $width Image width in pixels
     * @param int $height Image height in pixels
     *
     * @return string The closest standard aspect ratio string
     *
     * @throws \InvalidArgumentException If width or height is invalid
     */
    public function getClosestRatio(int $width, int $height): string
    {
        $calculatedRatio = $this->calculateRatio($width, $height);

        $standardRatios = [
            self::ASPECT_RATIO_16_9 => 16 / 9,   // ~1.778
            self::ASPECT_RATIO_4_3 => 4 / 3,     // ~1.333
            self::ASPECT_RATIO_3_2 => 3 / 2,     // 1.5
            self::ASPECT_RATIO_1_1 => 1.0,       // 1.0
            self::ASPECT_RATIO_3_4 => 3 / 4,     // 0.75
            self::ASPECT_RATIO_2_3 => 2 / 3,     // ~0.667
        ];

        $closestRatio = self::ASPECT_RATIO_16_9;
        $minDifference = PHP_FLOAT_MAX;

        foreach ($standardRatios as $ratioName => $ratioValue) {
            $difference = abs($calculatedRatio - $ratioValue);
            if ($difference < $minDifference) {
                $minDifference = $difference;
                $closestRatio = $ratioName;
            }
        }

        return $closestRatio;
    }

    /**
     * Returns all supported aspect ratio strings.
     *
     * @return array<int, string> List of supported aspect ratios
     */
    public function getSupportedRatios(): array
    {
        return [
            self::ASPECT_RATIO_16_9,
            self::ASPECT_RATIO_4_3,
            self::ASPECT_RATIO_3_2,
            self::ASPECT_RATIO_1_1,
            self::ASPECT_RATIO_3_4,
            self::ASPECT_RATIO_2_3,
        ];
    }

    /**
     * Checks if a given aspect ratio string is supported.
     *
     * @param string $aspectRatio The aspect ratio to check
     *
     * @return bool True if supported, false otherwise
     */
    public function isSupported(string $aspectRatio): bool
    {
        return in_array($aspectRatio, $this->getSupportedRatios(), true);
    }
}
