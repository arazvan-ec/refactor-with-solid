<?php

declare(strict_types=1);

/**
 * @copyright
 */

namespace App\Infrastructure\Service;

use App\Infrastructure\Service\Image\ThumborUrlFactory;
use Snaapi\Infrastructure\Service\Image\ImageProcessorInterface;

/**
 * Thumbor image processing service.
 *
 * This class acts as a facade to ThumborUrlFactory, implementing
 * ImageProcessorInterface and maintaining backward compatibility
 * with legacy method signatures.
 *
 * Following SOLID principles, this class delegates all operations
 * to ThumborUrlFactory, which coordinates the specialized components
 * (ImagePathBuilder, ImageFilterApplier).
 *
 * Responsibilities:
 * - Implement ImageProcessorInterface contract
 * - Maintain backward compatibility with legacy methods
 * - Delegate all operations to ThumborUrlFactory (composition over inheritance)
 *
 * @author Juanma Santos <jmsantos@elconfidencial.com>
 * @author SNAAPI Team
 */
class Thumbor implements ImageProcessorInterface
{
    /**
     * @param ThumborUrlFactory $urlFactory The URL factory handling all image operations
     */
    public function __construct(
        private readonly ThumborUrlFactory $urlFactory
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function generateUrl(string $path, array $options = []): string
    {
        return $this->urlFactory->generateUrl($path, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function applyFilters(string $url, array $filters): string
    {
        return $this->urlFactory->applyFilters($url, $filters);
    }

    /**
     * {@inheritDoc}
     */
    public function generateCroppedUrl(
        string $path,
        int $width,
        int $height,
        int $topX,
        int $topY,
        int $bottomX,
        int $bottomY,
        array $options = []
    ): string {
        return $this->urlFactory->generateCroppedUrl(
            $path,
            $width,
            $height,
            $topX,
            $topY,
            $bottomX,
            $bottomY,
            $options
        );
    }

    /**
     * {@inheritDoc}
     */
    public function generateJournalistImageUrl(string $fileName): string
    {
        return $this->urlFactory->generateJournalistImageUrl($fileName);
    }

    /**
     * Creates a journalist profile image URL.
     *
     * Legacy method maintained for backward compatibility.
     * Delegates to generateJournalistImageUrl().
     *
     * @param string $fileImage The image filename
     *
     * @return string The generated journalist image URL
     */
    public function createJournalistImage(string $fileImage): string
    {
        return $this->generateJournalistImageUrl($fileImage);
    }

    /**
     * Creates a cropped and resized image URL for body tag pictures.
     *
     * Legacy method maintained for backward compatibility.
     * Delegates to generateCroppedUrl().
     *
     * @param string $fileImage The image filename
     * @param string $width Target width in pixels (passed as string for BC)
     * @param string $height Target height in pixels (passed as string for BC)
     * @param int $topX Top-left X coordinate for crop
     * @param int $topY Top-left Y coordinate for crop
     * @param int $bottomX Bottom-right X coordinate for crop
     * @param int $bottomY Bottom-right Y coordinate for crop
     *
     * @return string The generated cropped image URL
     */
    public function retriveCropBodyTagPicture(
        string $fileImage,
        string $width,
        string $height,
        int $topX,
        int $topY,
        int $bottomX,
        int $bottomY,
    ): string {
        return $this->generateCroppedUrl(
            $fileImage,
            (int) $width,
            (int) $height,
            $topX,
            $topY,
            $bottomX,
            $bottomY
        );
    }
}
